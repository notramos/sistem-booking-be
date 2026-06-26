<?php

namespace App\Services;

use App\DTOs\BookingDTO;
use App\Enums\BookingStatus;
use App\Exceptions\BookingConflictException;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Booking;
use App\Models\BookingLog;
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
            $room = $this->roomRepo->findOrFail($dto->roomId);

            if (!$room->isAvailable()) {
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

            return $booking;
        });
    }

    public function cancel(string $id): Booking
    {
        return DB::transaction(function () use ($id) {
            $booking = $this->bookingRepo->findOrFail($id);

            if (!$booking->isCancellable()) {
                throw new \InvalidArgumentException('Booking tidak dapat dibatalkan');
            }

            $booking->update([
                'status' => BookingStatus::CANCELLED->value,
                'cancelled_at' => now(),
            ]);

            $this->auditService->log('booking.cancelled', $booking);
            $this->notificationService->bookingCancelled($booking);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'action' => 'cancelled',
                'description' => 'Booking dibatalkan oleh ' . auth()->user()->name,
            ]);

            return $booking;
        });
    }

    public function getUserBookings(?string $status = null): Collection
    {
        return $this->bookingRepo->getUserBookings(auth()->id(), $status);
    }

    public function getCalendarData(string $start, string $end, ?string $roomId = null): Collection
    {
        return $this->bookingRepo->getCalendarData($start, $end, $roomId);
    }
}
