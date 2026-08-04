<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminder;

/**
 * SENGAJA tidak implements ShouldQueue — lihat penjelasan di AutoCompleteBooking.
 */
class SendBookingReminder
{
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
