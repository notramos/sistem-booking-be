<?php

namespace App\Services;

use App\DTOs\BookingDTO;
use App\DTOs\ManualBookingDTO;
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
            if ($dto->bookingDate < $minDate) {
                throw new RoomNotAvailableException('Tanggal booking minimal H+'.config('booking.min_advance_days').' dari hari ini');
            }

            $maxDate = $this->maxBookableDate()->toDateString();
            if ($dto->bookingDate > $maxDate) {
                throw new RoomNotAvailableException('Tanggal booking maksimal sampai '.$this->maxBookableDate()->locale('id')->translatedFormat('d F Y'));
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
     * Input manual oleh staf (sekretariat/admin) — untuk data historis/pra-sepakat di
     * luar sistem (mis. impor dari catatan lama) atau booking yang langsung ingin
     * dicatat berstatus final. Beda dari create(): TIDAK menegakkan H+7/batas akhir
     * tahun/jam operasional (staf yang menjamin datanya, bukan pengajuan yang perlu
     * divalidasi ke pemohon) — tapi tetap menolak kalau ruangan sedang maintenance
     * atau bentrok booking lain, supaya tidak menimpa data yang sudah ada.
     */
    public function createManual(ManualBookingDTO $dto): Booking
    {
        return DB::transaction(function () use ($dto) {
            $this->bookingRepo->lockRoom($dto->roomId);

            $room = $this->roomRepo->findOrFail($dto->roomId);

            if ($dto->expectedAttendees !== null && $dto->expectedAttendees > (int) $room->capacity) {
                throw new RoomNotAvailableException("Jumlah peserta melebihi kapasitas ruangan ({$room->capacity} orang)");
            }

            $blockReason = $this->isSlotBlocked($dto->roomId, $dto->bookingDate, $dto->startTime, $dto->endTime, lockForUpdate: true);
            if ($blockReason === 'maintenance') {
                throw new RoomNotAvailableException('Ruangan sedang dalam jadwal perbaikan');
            }
            if ($blockReason === 'conflict') {
                throw new BookingConflictException('Waktu yang dipilih bertabrakan dengan booking lain yang sudah disetujui/menunggu');
            }

            $booking = $this->bookingRepo->create([
                'user_id' => $dto->userId,
                'room_id' => $dto->roomId,
                'title' => $dto->title,
                'description' => $dto->description,
                'booking_date' => $dto->bookingDate,
                'start_time' => $dto->startTime,
                'end_time' => $dto->endTime,
                'expected_attendees' => $dto->expectedAttendees,
                'contact_person' => $dto->contactPerson,
                'status' => $dto->status,
                'booking_type' => 'reguler',
            ]);

            $this->auditService->log('booking.created_manual', $booking);
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
     * Tidak ada batas atas tanggal — H+7 (min_advance_days) tetap wajib untuk tanggal
     * paling awal saja.
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
     * Booking biasa: batas atas tanggal booking. Akhir tahun berjalan, KECUALI
     * mulai November — pindah ke akhir tahun DEPAN supaya user tidak mentok
     * cuma bisa booking 1-2 bulan ke depan pas akhir tahun.
     */
    private function maxBookableDate(): Carbon
    {
        $now = now();

        return $now->month >= 11 ? $now->copy()->addYear()->endOfYear() : $now->copy()->endOfYear();
    }

    /**
     * @return array<int, string> tanggal (Y-m-d) tiap occurrence, dihitung dari anchor
     *                            (tanggal pertama) supaya tak "drift" pada pola bulanan
     *                            saat ada kliping akhir-bulan (mis. 31 Jan -> 28 Feb).
     *                            Booking rutin SELALU berhenti di akhir tahun kalender
     *                            dari tanggal pertamanya — beda dari batas booking biasa
     *                            di atas (yang bisa maju ke tahun depan mulai November) —
     *                            supaya tiap seri rutin tetap terkandung dalam satu tahun;
     *                            lanjut ke tahun berikutnya berarti bikin seri baru.
     */
    private function generateOccurrenceDates(string $firstDate, string $pattern, int $durationMonths): array
    {
        $anchor = Carbon::parse($firstDate);
        // -1 hari: tanggal pertama pengajuan dihitung sebagai bulan ke-1, bukan bulan ke-0.
        // Tanpa ini, durasi "3 bulan" dari anchor.addMonths(3) tetap termasuk tanggal pas
        // 3 bulan kemudian sebagai kemunculan tambahan, jadi rentangnya jadi 4 bulan.
        $endDate = $anchor->copy()->addMonths($durationMonths)->subDay()->min($anchor->copy()->endOfYear());
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

                $minDate = now()->addDays(config('booking.min_advance_days'))->toDateString();
                if ($bookingDate < $minDate) {
                    throw new RoomNotAvailableException('Tanggal booking minimal H+'.config('booking.min_advance_days').' dari hari ini');
                }

                $maxDate = $this->maxBookableDate()->toDateString();
                if ($bookingDate > $maxDate) {
                    throw new RoomNotAvailableException('Tanggal booking maksimal sampai '.$this->maxBookableDate()->locale('id')->translatedFormat('d F Y'));
                }

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

    /**
     * Ganti SATU tanggal dalam seri booking rutin (dipakai sekretariat/staff dari
     * kartu "Jadwal Rutin"), tanpa mengubah tanggal lain di seri yang sama. Tanggal
     * pengganti wajib di tahun yang sama dengan tanggal pertama seri — konsisten
     * dengan aturan "booking rutin tidak lintas tahun" di generateOccurrenceDates().
     */
    public function updateRecurringDate(string $bookingId, string $oldDate, string $newDate): Booking
    {
        return DB::transaction(function () use ($bookingId, $oldDate, $newDate) {
            $booking = $this->bookingRepo->findOrFail($bookingId);
            $existingDates = $booking->recurring_dates ?? [];

            if ($booking->booking_type !== 'rutin' || ! in_array($oldDate, $existingDates, true)) {
                throw new \InvalidArgumentException('Tanggal tidak ditemukan di booking rutin ini');
            }

            $this->bookingRepo->lockRoom($booking->room_id);

            $minDate = now()->addDays(config('booking.min_advance_days'))->toDateString();
            if ($newDate < $minDate) {
                throw new RoomNotAvailableException('Tanggal pengganti minimal H+'.config('booking.min_advance_days').' dari hari ini');
            }

            $anchorYear = Carbon::parse($existingDates[0])->year;
            if (Carbon::parse($newDate)->year !== $anchorYear) {
                throw new RoomNotAvailableException("Tanggal pengganti harus di tahun yang sama ({$anchorYear}) — booking rutin tidak bisa lintas tahun");
            }

            if (in_array($newDate, $existingDates, true)) {
                throw new BookingConflictException('Tanggal tersebut sudah ada di seri booking rutin ini');
            }

            $blockReason = $this->isSlotBlocked($booking->room_id, $newDate, $booking->start_time, $booking->end_time, lockForUpdate: true);
            if ($blockReason === 'maintenance') {
                throw new RoomNotAvailableException('Ruangan sedang dalam jadwal perbaikan pada tanggal tersebut');
            }
            if ($blockReason === 'conflict') {
                throw new BookingConflictException('Waktu yang dipilih bertabrakan dengan booking lain pada tanggal tersebut');
            }

            $newDates = collect($existingDates)
                ->reject(fn ($d) => $d === $oldDate)
                ->push($newDate)
                ->sort()
                ->values()
                ->all();

            $booking->update([
                'recurring_dates' => $newDates,
                'booking_date' => $newDates[0],
            ]);

            $this->roomRepo->clearAvailabilityCache();

            return $booking->fresh();
        });
    }

    /**
     * Hapus SATU tanggal dari seri booking rutin tanpa menggantinya — beda dari
     * updateRecurringDate() yang mengganti dengan tanggal lain. Tidak perlu cek
     * ketersediaan ruangan/H+7 seperti update, karena menghapus tidak mungkin
     * menyebabkan konflik jadwal baru.
     */
    public function deleteRecurringDate(string $bookingId, string $date): Booking
    {
        return DB::transaction(function () use ($bookingId, $date) {
            $booking = $this->bookingRepo->findOrFail($bookingId);
            $existingDates = $booking->recurring_dates ?? [];

            if ($booking->booking_type !== 'rutin' || ! in_array($date, $existingDates, true)) {
                throw new \InvalidArgumentException('Tanggal tidak ditemukan di booking rutin ini');
            }

            if (count($existingDates) === 1) {
                throw new \InvalidArgumentException('Tidak bisa menghapus satu-satunya tanggal tersisa. Batalkan booking ini jika ingin menghentikan seluruh seri.');
            }

            $this->bookingRepo->lockRoom($booking->room_id);

            $newDates = collect($existingDates)
                ->reject(fn ($d) => $d === $date)
                ->sort()
                ->values()
                ->all();

            $booking->update([
                'recurring_dates' => $newDates,
                'booking_date' => $newDates[0],
            ]);

            $this->roomRepo->clearAvailabilityCache();

            return $booking->fresh();
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
