<?php

namespace App\Services;

use App\DTOs\BookingDTO;
use App\DTOs\RecurringBookingDTO;
use App\DTOs\RecurringBookingResult;
use App\DTOs\RecurringPreviewDTO;
use App\Enums\BookingStatus;
use App\Exceptions\BookingConflictException;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Booking;
use App\Models\MaintenanceSchedule;
use App\Repositories\BookingRepository;
use App\Repositories\RoomRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private BookingRepository $bookingRepo,
        private RoomRepository $roomRepo,
        private NotificationService $notificationService,
        private AuditService $auditService,
    ) {}

    public function create(BookingDTO $dto): Booking
    {
        return DB::transaction(function () use ($dto) {
            $this->bookingRepo->lockRoom($dto->roomId);

            $room = $this->roomRepo->findOrFail($dto->roomId);

            if (! $room->isAvailable()) {
                throw new RoomNotAvailableException('Ruangan sedang tidak tersedia untuk dipesan');
            }

            // Defense-in-depth: aturan yang sama sudah ditegakkan di StoreBookingRequest,
            // guard ini melindungi bila ada pemanggil yang melewati FormRequest.
            $hours = config('booking.operating_hours');
            if ($dto->startTime < $hours['open'] || $dto->endTime > $hours['close']) {
                throw new RoomNotAvailableException("Waktu booking di luar jam operasional ({$hours['open']}–{$hours['close']})");
            }

            if ($dto->expectedAttendees !== null && (int) $dto->expectedAttendees > (int) $room->capacity) {
                throw new RoomNotAvailableException("Jumlah peserta melebihi kapasitas ruangan ({$room->capacity} orang)");
            }

            $minDate = now()->addDays(config('booking.min_advance_days'))->toDateString();
            $maxDate = now()->addDays(config('booking.max_advance_days'))->toDateString();
            if ($dto->bookingDate < $minDate || $dto->bookingDate > $maxDate) {
                throw new RoomNotAvailableException('Tanggal booking di luar rentang yang diizinkan (H+'.config('booking.min_advance_days').' s/d H+'.config('booking.max_advance_days').' dari hari ini)');
            }

            $blockReason = $this->isSlotBlocked($dto->roomId, $dto->bookingDate, $dto->startTime, $dto->endTime, lockForUpdate: true);
            if ($blockReason === 'maintenance') {
                throw new RoomNotAvailableException('Ruangan sedang dalam jadwal perbaikan');
            }
            if ($blockReason === 'conflict') {
                throw new BookingConflictException('Waktu yang dipilih bertabrakan dengan booking lain yang sudah disetujui');
            }

            $booking = $this->bookingRepo->create([
                'user_id' => auth()->id(),
                'room_id' => $dto->roomId,
                'title' => $dto->title,
                'description' => $dto->description,
                'booking_date' => $dto->bookingDate,
                'start_time' => $dto->startTime,
                'end_time' => $dto->endTime,
                'purpose_type' => $dto->purposeType,
                'expected_attendees' => $dto->expectedAttendees,
                'contact_person' => auth()->user()->phone,
                'status' => BookingStatus::PENDING->value,
                'notes' => $dto->notes,
            ]);

            $this->auditService->log('booking.created', $booking);
            $this->notificationService->bookingCreated($booking);
            $this->roomRepo->clearAvailabilityCache();

            return $booking;
        });
    }

    /**
     * Cek apakah slot ruangan pada tanggal+waktu tertentu terhalang (maintenance atau
     * bentrok booking lain). Dipakai baik oleh create() (yang melempar exception saat
     * terhalang) maupun createRecurring() (yang melewati tanggal tersebut alih-alih gagal).
     */
    private function isSlotBlocked(string $roomId, string $date, string $startTime, string $endTime, bool $lockForUpdate = false): ?string
    {
        $isUnderMaintenance = MaintenanceSchedule::forRoom($roomId, $date)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('is_all_day', true)
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                    });
            })
            ->exists();

        if ($isUnderMaintenance) {
            return 'maintenance';
        }

        $hasConflict = $this->bookingRepo->hasConflict(
            roomId: $roomId,
            date: $date,
            startTime: $startTime,
            endTime: $endTime,
            excludeBookingId: null,
            lockForUpdate: $lockForUpdate,
        );

        return $hasConflict ? 'conflict' : null;
    }

    /**
     * Cek ketersediaan tiap tanggal occurrence SEBELUM booking dibuat — dipakai frontend
     * untuk menampilkan jadwal + status per tanggal, supaya pemohon bisa mengganti tanggal
     * yang bentrok dengan tanggal lain di bulan yang sama sebelum submit. Read-only, tidak
     * mengunci/membuat apa pun.
     *
     * @return array<int, array{date: string, available: bool, reason: ?string}>
     */
    public function previewRecurring(RecurringPreviewDTO $dto): array
    {
        $occurrenceDates = $this->generateOccurrenceDates($dto->firstDate, $dto->pattern, $dto->durationMonths);

        return array_map(function (string $date) use ($dto) {
            $blockReason = $this->isSlotBlocked($dto->roomId, $date, $dto->startTime, $dto->endTime);

            return [
                'date' => $date,
                'available' => $blockReason === null,
                'reason' => $blockReason,
            ];
        }, $occurrenceDates);
    }

    /**
     * Booking rutin: SATU baris Booking mewakili seluruh seri. $dto->dates adalah daftar
     * tanggal yang OTORITATIF — sudah diresolusi pemohon di frontend lewat previewRecurring()
     * (termasuk mengganti tanggal yang bentrok dengan tanggal lain di bulan yang sama).
     * Approve/reject berlaku sekali untuk baris ini, otomatis untuk seluruh seri — tidak ada
     * perubahan sama sekali di ApprovalService/ApprovalController/BookingPolicy.
     *
     * Tetap re-cek tiap tanggal di sini (bukan cuma percaya hasil preview) sebagai jaring
     * pengaman race-condition — kalau ternyata ada yang berubah jadi bentrok di antara waktu
     * preview & submit, tanggal itu dilewati (bukan menggagalkan seluruh pengajuan).
     *
     * H+30 (max_advance_days) SENGAJA tidak dicek di sini — aturan itu cuma relevan untuk
     * booking sekali; H+7 (min_advance_days) tetap wajib untuk tanggal paling awal saja.
     */
    public function createRecurring(RecurringBookingDTO $dto): RecurringBookingResult
    {
        return DB::transaction(function () use ($dto) {
            $this->bookingRepo->lockRoom($dto->roomId);

            $room = $this->roomRepo->findOrFail($dto->roomId);

            if (! $room->isAvailable()) {
                throw new RoomNotAvailableException('Ruangan sedang tidak tersedia untuk dipesan');
            }

            $hours = config('booking.operating_hours');
            if ($dto->startTime < $hours['open'] || $dto->endTime > $hours['close']) {
                throw new RoomNotAvailableException("Waktu booking di luar jam operasional ({$hours['open']}–{$hours['close']})");
            }

            if ($dto->expectedAttendees !== null && (int) $dto->expectedAttendees > (int) $room->capacity) {
                throw new RoomNotAvailableException("Jumlah peserta melebihi kapasitas ruangan ({$room->capacity} orang)");
            }

            $sortedDates = collect($dto->dates)->unique()->sort()->values()->all();

            $minDate = now()->addDays(config('booking.min_advance_days'))->toDateString();
            if (($sortedDates[0] ?? null) < $minDate) {
                throw new RoomNotAvailableException('Tanggal booking paling awal minimal H+'.config('booking.min_advance_days').' dari hari ini');
            }

            $confirmedDates = [];
            $skipped = [];

            foreach ($sortedDates as $date) {
                $blockReason = $this->isSlotBlocked($dto->roomId, $date, $dto->startTime, $dto->endTime, lockForUpdate: true);

                if ($blockReason !== null) {
                    $skipped[] = ['date' => $date, 'reason' => $blockReason];

                    continue;
                }

                $confirmedDates[] = $date;
            }

            if (empty($confirmedDates)) {
                throw new BookingConflictException('Semua tanggal pada jadwal rutin ini bertabrakan dengan booking lain atau jadwal perbaikan ruangan');
            }

            $booking = $this->bookingRepo->create([
                'user_id' => auth()->id(),
                'room_id' => $dto->roomId,
                'title' => $dto->title,
                'description' => $dto->description,
                'booking_date' => $confirmedDates[0],
                'start_time' => $dto->startTime,
                'end_time' => $dto->endTime,
                'purpose_type' => $dto->purposeType,
                'expected_attendees' => $dto->expectedAttendees,
                'contact_person' => auth()->user()->phone,
                'status' => BookingStatus::PENDING->value,
                'notes' => $dto->notes,
                'booking_type' => 'rutin',
                'recurring_pattern' => $dto->pattern,
                'recurring_dates' => $confirmedDates,
            ]);

            $this->auditService->log('booking.created', $booking);
            $this->notificationService->recurringBookingCreated($booking, count($skipped));
            $this->roomRepo->clearAvailabilityCache();

            return new RecurringBookingResult($booking, $skipped);
        });
    }

    /**
     * @return array<int, string> tanggal (Y-m-d) tiap occurrence, dihitung dari anchor
     *                            (tanggal pertama) supaya tak "drift" pada pola bulanan
     *                            saat ada kliping akhir-bulan (mis. 31 Jan -> 28 Feb).
     */
    private function generateOccurrenceDates(string $firstDate, string $pattern, int $durationMonths): array
    {
        $anchor = Carbon::parse($firstDate);
        $endDate = $anchor->copy()->addMonths($durationMonths);
        $dates = [];
        $maxOccurrences = 60;

        if ($pattern === 'weekly') {
            $cursor = $anchor->copy();
            while ($cursor->lte($endDate) && count($dates) < $maxOccurrences) {
                $dates[] = $cursor->toDateString();
                $cursor = $cursor->copy()->addDays(7);
            }
        } else {
            $n = 0;
            while ($n < $maxOccurrences) {
                $occurrence = $anchor->copy()->addMonths($n);
                if ($occurrence->gt($endDate)) {
                    break;
                }
                $dates[] = $occurrence->toDateString();
                $n++;
            }
        }

        return $dates;
    }

    /**
     * Update booking fields (termasuk realokasi ruangan/tanggal oleh sekretariat, atau
     * resubmit pemohon setelah revisi). Re-validates room availability (maintenance
     * schedule + conflicts) under a per-room advisory lock whenever room, tanggal, atau
     * waktu berubah, mirroring the checks create() performs, and invalidates the
     * availability cache so other users don't see stale slot data.
     */
    public function updateTime(string $id, array $data): Booking
    {
        return DB::transaction(function () use ($id, $data) {
            $booking = $this->bookingRepo->findOrFail($id);

            $roomId = $data['room_id'] ?? $booking->room_id;
            $bookingDate = $data['booking_date'] ?? $booking->booking_date->format('Y-m-d');
            $startTime = $data['start_time'] ?? $booking->start_time;
            $endTime = $data['end_time'] ?? $booking->end_time;
            $slotChanged = isset($data['room_id']) || isset($data['booking_date'])
                || isset($data['start_time']) || isset($data['end_time']);

            if ($slotChanged) {
                $this->bookingRepo->lockRoom($roomId);

                $isUnderMaintenance = MaintenanceSchedule::forRoom($roomId, $bookingDate)
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->where('is_all_day', true)
                            ->orWhere(function ($q) use ($startTime, $endTime) {
                                $q->where('start_time', '<', $endTime)
                                    ->where('end_time', '>', $startTime);
                            });
                    })
                    ->exists();

                if ($isUnderMaintenance) {
                    throw new RoomNotAvailableException('Ruangan sedang dalam jadwal perbaikan pada waktu tersebut');
                }

                $hasConflict = $this->bookingRepo->hasConflict(
                    roomId: $roomId,
                    date: $bookingDate,
                    startTime: $startTime,
                    endTime: $endTime,
                    excludeBookingId: $booking->id,
                );

                if ($hasConflict) {
                    throw new BookingConflictException('Waktu yang dipilih bertabrakan dengan booking lain');
                }
            }

            $booking->update($data);

            if ($slotChanged) {
                $this->roomRepo->clearAvailabilityCache();
            }

            return $booking;
        });
    }

    public function cancel(string $id): Booking
    {
        return DB::transaction(function () use ($id) {
            $booking = $this->bookingRepo->findOrFail($id);

            if (! $booking->isCancellable()) {
                throw new \InvalidArgumentException('Booking tidak dapat dibatalkan');
            }

            $booking->update([
                'status' => BookingStatus::CANCELLED->value,
                'cancelled_at' => now(),
            ]);

            $this->auditService->log('booking.cancelled', $booking);
            $this->notificationService->bookingCancelled($booking);
            $this->roomRepo->clearAvailabilityCache();

            return $booking;
        });
    }

    public function getUserBookings(?string $status = null, int $page = 1, ?string $search = null, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->bookingRepo->getUserBookings(auth()->id(), $status, $page, $search, $perPage);
    }

    public function getCalendarData(string $start, string $end, ?string $roomId = null): Collection
    {
        return $this->bookingRepo->getCalendarData($start, $end, $roomId);
    }
}
