<?php

namespace App\DTOs;

class CalendarFilterDTO
{
    public function __construct(
        public readonly string $start,
        public readonly string $end,
        public readonly ?string $roomId = null,
    ) {}
}
