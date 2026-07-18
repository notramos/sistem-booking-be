<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingCreated;
use App\Notifications\BookingMovedToAdminReview;
use App\Notifications\BookingRejected;
use App\Notifications\BookingRevisionRequested;
use App\Notifications\RecurringBookingCreated;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function bookingCreated(Booking $booking): void
    {
        $sekretariat = User::role('sekretariat')->get();
        Notification::send($sekretariat, new BookingCreated($booking));
    }

    public function bookingApproved(Booking $booking): void
    {
        $booking->user->notify(new BookingApproved($booking));
    }

    public function bookingRejected(Booking $booking): void
    {
        $booking->user->notify(new BookingRejected($booking));
    }

    public function recurringBookingCreated(Booking $booking, int $skippedCount): void
    {
        $sekretariat = User::role('sekretariat')->get();
        Notification::send($sekretariat, new RecurringBookingCreated($booking, $skippedCount));
    }

    public function bookingMovedToAdminReview(Booking $booking): void
    {
        $admins = User::role('admin')->get();
        Notification::send($admins, new BookingMovedToAdminReview($booking));
    }

    public function bookingRevisionRequested(Booking $booking, string $reason): void
    {
        $booking->user->notify(new BookingRevisionRequested($booking, $reason));
    }

    public function bookingCancelled(Booking $booking): void
    {
        $approval = $booking->adminApproval ?? $booking->sekretariatApproval;

        if ($approval && $approval->approver_id) {
            $approver = User::find($approval->approver_id);
            if ($approver) {
                $approver->notify(new BookingCancelled($booking));
            }
        }
    }
}
