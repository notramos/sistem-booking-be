<?php

namespace App\Enums;

enum PurposeType: string
{
    case IBADAH = 'ibadah';
    case ACARA_KELUARGA = 'acara_keluarga';
    case LATIHAN_MUSIK = 'latihan_musik';
    case PEMBINAAN = 'pembinaan';
    case RAPAT = 'rapat';
    case SEMINAR = 'seminar';
    case PUBLIK = 'publik';

    public function label(): string
    {
        return match ($this) {
            self::IBADAH => 'Ibadah & Persekutuan',
            self::ACARA_KELUARGA => 'Acara Keluarga',
            self::LATIHAN_MUSIK => 'Latihan Musik',
            self::PEMBINAAN => 'Pembinaan',
            self::RAPAT => 'Rapat Pelayanan',
            self::SEMINAR => 'Seminar & Training',
            self::PUBLIK => 'Acara Publik',
        };
    }
}
