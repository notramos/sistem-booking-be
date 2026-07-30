<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\User;
use App\Repositories\BookingRepository;
use App\Repositories\RoomRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function __construct(
        private BookingRepository $bookingRepo,
        private RoomRepository $roomRepo,
        private BookingService $bookingService,
        private NotificationService $notificationService,
        private AuditService $auditService,
    ) {}

    public function startReview(string $bookingId): Booking
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = $this->bookingRepo->findOrFail($bookingId);

            if ($booking->status !== BookingStatus::PENDING->value) {
                throw new \InvalidArgumentException('Booking sudah tidak dalam status menunggu review');
            }

            $booking->update(['status' => BookingStatus::SEKRETARIAT_REVIEW->value]);

            $this->auditService->log('booking.review_started', $booking);

            return $booking;
        });
    }

    /**
     * Approve dari Sekretariat meneruskan booking ke tahap Admin (belum final).
     * Approve dari Admin selalu final — berlaku juga sebagai override, bisa
     * langsung dari PENDING/SEKRETARIAT_REVIEW (skip tahap sekretariat).
     */
    public function approve(string $bookingId, User $approver, ?string $notes = null): Booking
    {
        return DB::transaction(function () use ($bookingId, $approver, $notes) {
            $booking = $this->bookingRepo->findOrFail($bookingId);
            $isAdmin = $approver->hasAnyRole(['p2', 'pastor', 'it_admin']);

            if (! $isAdmin && ! in_array($booking->status, [BookingStatus::PENDING->value, BookingStatus::SEKRETARIAT_REVIEW->value])) {
                throw new \InvalidArgumentException('Booking sudah melewati tahap sekretariat');
            }

            if ($isAdmin && ! in_array($booking->status, BookingStatus::nonFinal())) {
                throw new \InvalidArgumentException('Booking sudah tidak dalam status yang bisa disetujui');
            }

            $stage = $isAdmin ? 'admin' : 'sekretariat';
            $newStatus = $isAdmin ? BookingStatus::APPROVED->value : BookingStatus::ADMIN_REVIEW->value;

            $booking->update(['status' => $newStatus]);

            BookingApproval::updateOrCreate(
                ['booking_id' => $booking->id, 'stage' => $stage],
                ['approver_id' => $approver->id, 'action' => 'approved', 'notes' => $notes]
            );

            $this->auditService->log('booking.approved', $booking);

            if ($isAdmin) {
                $this->roomRepo->clearAvailabilityCache();
                $this->notificationService->bookingApproved($booking);
            } else {
                $this->notificationService->bookingMovedToAdminReview($booking);
            }

            return $booking;
        });
    }

    /**
     * Reject bisa dilakukan sekretariat maupun admin, dari status apa pun yang
     * belum final (admin juga bisa override/reject langsung dari tahap sekretariat).
     */
    public function reject(string $bookingId, User $approver, string $reason): Booking
    {
        return DB::transaction(function () use ($bookingId, $approver, $reason) {
            $booking = $this->bookingRepo->findOrFail($bookingId);

            if (! in_array($booking->status, BookingStatus::nonFinal())) {
                throw new \InvalidArgumentException('Booking sudah tidak dalam status yang bisa ditolak');
            }

            $stage = $approver->hasAnyRole(['p2', 'pastor', 'it_admin']) ? 'admin' : 'sekretariat';

            $booking->update([
                'status' => BookingStatus::REJECTED->value,
                'reject_reason' => $reason,
            ]);

            $this->roomRepo->clearAvailabilityCache();

            BookingApproval::updateOrCreate(
                ['booking_id' => $booking->id, 'stage' => $stage],
                ['approver_id' => $approver->id, 'action' => 'rejected', 'notes' => $reason]
            );

            $this->auditService->log('booking.rejected', $booking);
            $this->notificationService->bookingRejected($booking);

            return $booking;
        });
    }

    /**
     * Pemohon mengedit & mengajukan ulang booking yang diminta revisi — kembali
     * ke tahap yang sama yang memintanya (bukan selalu ke awal).
     */
    public function resubmit(string $bookingId, User $owner, array $data): Booking
    {
        $booking = $this->bookingRepo->findOrFail($bookingId);

        if ($booking->user_id !== $owner->id) {
            throw new \InvalidArgumentException('Anda tidak berhak mengubah booking ini');
        }

        if (! in_array($booking->status, [BookingStatus::REVISION_SEKRETARIAT->value, BookingStatus::REVISION_ADMIN->value])) {
            throw new \InvalidArgumentException('Booking tidak dalam status revisi');
        }

        $backTo = $booking->status === BookingStatus::REVISION_SEKRETARIAT->value
            ? BookingStatus::SEKRETARIAT_REVIEW->value
            : BookingStatus::ADMIN_REVIEW->value;

        return $this->bookingService->updateTime($bookingId, [...$data, 'status' => $backTo]);
    }

    public function getPendingBookings(User $user): LengthAwarePaginator
    {
        return $this->bookingRepo->getPendingBookings($user);
    }
}
