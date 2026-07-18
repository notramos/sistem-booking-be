<?php

namespace App\DTOs;

use App\Models\Booking;

class RecurringBookingResult
{
    /**
     * @param  array<int, array{date: string, reason: string}>  $skipped
     */
    public function __construct(
        public readonly Booking $booking,
        public readonly array $skipped,
    ) {}
}
