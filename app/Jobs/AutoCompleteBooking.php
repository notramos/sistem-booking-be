<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoCompleteBooking implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Booking::where('status', BookingStatus::APPROVED->value)
            ->where(function ($q) {
                $q->where('booking_date', '<', now()->toDateString())
                  ->orWhere(function ($q) {
                      $q->where('booking_date', '=', now()->toDateString())
                        ->where('end_time', '<=', now()->format('H:i:s'));
                  });
            })
            ->update(['status' => BookingStatus::COMPLETED->value, 'completed_at' => now()]);
    }
}
