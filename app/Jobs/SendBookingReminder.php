<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingReminder implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $tomorrowBookings = Booking::with(['user', 'room'])
            ->where('status', BookingStatus::APPROVED->value)
            ->where('booking_date', now()->addDay()->toDateString())
            ->get();

        foreach ($tomorrowBookings as $booking) {
            $booking->user->notify(new BookingReminder($booking));
        }
    }
}
