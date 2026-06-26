<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\BookingLog;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'action' => 'created',
            'description' => 'Booking dibuat oleh ' . ($booking->user->name ?? 'Unknown'),
        ]);
    }

    public function updated(Booking $booking): void
    {
        if ($booking->isDirty('status')) {
            $oldStatus = $booking->getOriginal('status');
            $newStatus = $booking->status;

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => auth()->id() ?? $booking->user_id,
                'action' => 'status_changed',
                'description' => "Status berubah dari {$oldStatus} ke {$newStatus}",
            ]);
        }
    }
}
