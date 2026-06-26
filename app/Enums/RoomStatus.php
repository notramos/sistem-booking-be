<?php

namespace App\Enums;

enum RoomStatus: string
{
    case AVAILABLE = 'available';
    case MAINTENANCE = 'maintenance';
    case UNAVAILABLE = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::MAINTENANCE => 'Perbaikan',
            self::UNAVAILABLE => 'Tidak Tersedia',
        };
    }
}
