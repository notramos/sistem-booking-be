<?php

namespace App\Services;

use App\DTOs\BookingDTO;
use App\Enums\BookingStatus;
use App\Exceptions\BookingConflictException;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Booking;
use App\Models\MaintenanceSchedule;
use App\Repositories\BookingRepository;
use App\Repositories\RoomRepository;
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

            $isUnderMaintenance = MaintenanceSchedule::forRoom($dto->roomId, $dto->bookingDate)
                ->where(function ($q) use ($dto) {
                    $q->where('is_all_day', true)
                        ->orWhere(function ($q) use ($dto) {
                            $q->where('start_time', '<', $dto->endTime)
                                ->where('end_time', '>', $dto->startTime);
                        });
                })
                ->exists();

            if ($isUnderMaintenance) {
                throw new RoomNotAvailableException('Ruangan sedang dalam jadwal perbaikan');
            }

            $hasConflict = $this->bookingRepo->hasConflict(
                roomId: $dto->roomId,
                date: $dto->bookingDate,
                startTime: $dto->startTime,
                endTime: $dto->endTime,
                excludeBookingId: null,
                lockForUpdate: true
            );

            if ($hasConflict) {
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
     * Update a pending booking's fields. Re-validates room availability (maintenance
     * schedule + conflicts) under a per-room advisory lock whenever the time changes,
     * mirroring the checks create() performs, and invalidates the availability cache
     * so other users don't see stale slot data.
     */
    public function updateTime(string $id, array $data): Booking
    {
        return DB::transaction(function () use ($id, $data) {
            $booking = $this->bookingRepo->findOrFail($id);

            $startTime = $data['start_time'] ?? $booking->start_time;
            $endTime = $data['end_time'] ?? $booking->end_time;
            $timeChanged = isset($data['start_time']) || isset($data['end_time']);

            if ($timeChanged) {
                $this->bookingRepo->lockRoom($booking->room_id);

                $isUnderMaintenance = MaintenanceSchedule::forRoom($booking->room_id, $booking->booking_date)
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
                    roomId: $booking->room_id,
                    date: $booking->booking_date,
                    startTime: $startTime,
                    endTime: $endTime,
                    excludeBookingId: $booking->id,
                );

                if ($hasConflict) {
                    throw new BookingConflictException('Waktu yang dipilih bertabrakan dengan booking lain');
                }
            }

            $booking->update($data);

            if ($timeChanged) {
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
