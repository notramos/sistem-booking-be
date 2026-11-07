<?php

namespace App\Support;

use App\Models\Room;

class PracticeRoom
{
    public static function allows(Room $room, ?string $purposeType): bool
    {
        $slug = mb_strtolower((string) $room->slug);
        $name = mb_strtolower(trim((string) $room->name));
        $patron = mb_strtolower(trim((string) $room->patron_name));

        if ($slug === 'panti-koor-gereja' || $name === 'panti koor gereja') {
            return $purposeType === 'latihan_koor';
        }

        if ($slug === 'area-altar-gereja' || $name === 'area altar gereja') {
            return $purposeType === 'latihan_lektor_pemazmur';
        }

        if ($purposeType === 'latihan_koor') {
            return in_array($patron, ['aristoteles', 'cecilia'], true)
                || in_array($name, ['lobby', 'selasar lobby', '501'], true);
        }

        return $purposeType !== 'latihan_lektor_pemazmur';
    }

    public static function error(?string $purposeType): string
    {
        return match ($purposeType) {
            'latihan_koor' => 'Latihan Koor hanya dapat menggunakan Panti Koor Gereja, Lobby Aristoteles, atau Ruang St. Cecilia (501).',
            'latihan_lektor_pemazmur' => 'Latihan lektor dan pemazmur hanya dapat menggunakan Area Altar Gereja.',
            default => 'Panti Koor dan Area Altar hanya dapat dipesan untuk jenis latihan yang sesuai.',
        };
    }
}
