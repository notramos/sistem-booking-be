<?php

namespace App\DTOs;

class BookingDTO
{
    public function __construct(
        public readonly string $roomId,
        public readonly string $title,
        public readonly string $bookingDate,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly ?string $description = null,
        public readonly ?string $purposeType = null,
        public readonly ?int $expectedAttendees = null,
        public readonly ?string $notes = null,
    ) {}
}
