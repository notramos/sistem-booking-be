<?php

namespace App\DTOs;

class RecurringPreviewDTO
{
    public function __construct(
        public readonly string $roomId,
        public readonly string $firstDate,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly string $pattern,
        public readonly int $durationMonths,
    ) {}
}
