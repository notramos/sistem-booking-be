<?php

namespace App\DTOs;

class ManualBookingDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $roomId,
        public readonly string $title,
        public readonly string $bookingDate,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly string $status,
        public readonly ?string $description = null,
        public readonly ?string $contactPerson = null,
        public readonly ?int $expectedAttendees = null,
    ) {}
}
