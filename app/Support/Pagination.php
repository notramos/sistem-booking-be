<?php

namespace App\Support;

class Pagination
{
    /**
     * Bersihkan nilai per_page dari input user (bisa null/string/negatif/raksasa)
     * jadi integer wajar, supaya request tak bisa memaksa scan tabel besar sekaligus
     * lewat mis. ?per_page=999999.
     */
    public static function perPage(mixed $value, int $default, int $max = 100): int
    {
        $value = (int) ($value ?: $default);

        return max(1, min($value, $max));
    }
}
