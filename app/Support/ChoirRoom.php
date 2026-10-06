<?php

namespace App\Support;

use App\Models\Room;

class ChoirRoom
{
    public static function allows(Room $room): bool
    {
        return in_array(mb_strtolower(trim((string) $room->patron_name)), ['aristoteles', 'cecilia'], true)
            || in_array(mb_strtolower(trim($room->name)), ['lobby', 'selasar lobby', '501'], true);
    }
}
