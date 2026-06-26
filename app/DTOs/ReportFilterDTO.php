<?php

namespace App\DTOs;

class ReportFilterDTO
{
    public function __construct(
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?string $roomId = null,
        public readonly ?string $userId = null,
        public readonly ?string $status = null,
        public readonly ?string $format = 'json',
    ) {}
}
