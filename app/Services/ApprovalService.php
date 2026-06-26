<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\BookingLog;
use App\Repositories\BookingRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function __construct(
        private BookingRepository $bookingRepo,
        private NotificationService $notificationService,
        private AuditService $auditService,
    ) {}

    public function approve(string $bookingId, string $approverId, ?string $notes = null): Booking
    {
        return DB::transaction(function () use ($bookingId, $approverId, $notes) {
            $booking = $this->bookingRepo->findOrFail($bookingId);

            if ($booking->status !== BookingStatus::PENDING->value) {
                throw new \InvalidArgumentException('Booking sudah tidak dalam status pending');
            }

            $booking->update(['status' => BookingStatus::APPROVED->value]);

            BookingApproval::create([
                'booking_id' => $booking->id,
                'approver_id' => $approverId,
                'action' => 'approved',
                'notes' => $notes,
            ]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => $approverId,
                'action' => 'approved',
                'description' => 'Booking disetujui' . ($notes ? ': ' . $notes : ''),
            ]);

            $this->auditService->log('booking.approved', $booking);
            $this->notificationService->bookingApproved($booking);

            return $booking;
        });
    }

    public function reject(string $bookingId, string $approverId, string $reason): Booking
    {
        return DB::transaction(function () use ($bookingId, $approverId, $reason) {
            $booking = $this->bookingRepo->findOrFail($bookingId);

            if ($booking->status !== BookingStatus::PENDING->value) {
                throw new \InvalidArgumentException('Booking sudah tidak dalam status pending');
            }

            $booking->update(['status' => BookingStatus::REJECTED->value]);

            BookingApproval::create([
                'booking_id' => $booking->id,
                'approver_id' => $approverId,
                'action' => 'rejected',
                'notes' => $reason,
            ]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => $approverId,
                'action' => 'rejected',
                'description' => 'Booking ditolak: ' . $reason,
            ]);

            $this->auditService->log('booking.rejected', $booking);
            $this->notificationService->bookingRejected($booking);

            return $booking;
        });
    }

    public function getPendingBookings(): LengthAwarePaginator
    {
        return $this->bookingRepo->getPendingBookings();
    }
}
