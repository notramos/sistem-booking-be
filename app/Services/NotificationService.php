<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingCreated;
use App\Notifications\BookingRejected;
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

    public function bookingCancelled(Booking $booking): void
    {
        if ($booking->approval && $booking->approval->approver_id) {
            $approver = User::find($booking->approval->approver_id);
            if ($approver) {
                $approver->notify(new BookingCancelled($booking));
            }
        }
    }
}
