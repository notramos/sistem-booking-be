<?php

namespace App\DTOs;

class RecurringBookingDTO
{
    /**
     * @param  array<int, string>  $dates  Tanggal (Y-m-d) yang sudah diresolusi di frontend
     *                                     (termasuk penggantian tanggal bentrok) — daftar ini
     *                                     otoritatif, bukan dihitung ulang di backend.
     */
    public function __construct(
        public readonly string $roomId,
        public readonly string $title,
        public readonly array $dates,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly string $pattern,
        public readonly ?string $description = null,
        public readonly ?string $purposeType = null,
        public readonly ?int $expectedAttendees = null,
        public readonly ?string $notes = null,
    ) {}
}
