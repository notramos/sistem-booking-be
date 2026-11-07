<?php

namespace Tests\Unit;

use App\Models\Room;
use App\Support\PracticeRoom;
use PHPUnit\Framework\TestCase;

class PracticeRoomTest extends TestCase
{
    public function test_church_areas_are_only_used_for_their_respective_practice(): void
    {
        $choir = new Room(['name' => 'Panti Koor Gereja', 'slug' => 'panti-koor-gereja']);
        $altar = new Room(['name' => 'Area Altar Gereja', 'slug' => 'area-altar-gereja']);
        $lobby = new Room(['name' => 'Lobby', 'patron_name' => 'Aristoteles']);
        $cecilia = new Room(['name' => '501', 'patron_name' => 'Cecilia']);

        $this->assertTrue(PracticeRoom::allows($choir, 'latihan_koor'));
        $this->assertTrue(PracticeRoom::allows($lobby, 'latihan_koor'));
        $this->assertTrue(PracticeRoom::allows($cecilia, 'latihan_koor'));
        $this->assertFalse(PracticeRoom::allows($altar, 'latihan_koor'));
        $this->assertTrue(PracticeRoom::allows($altar, 'latihan_lektor_pemazmur'));
        $this->assertFalse(PracticeRoom::allows($choir, 'latihan_lektor_pemazmur'));
        $this->assertFalse(PracticeRoom::allows($lobby, 'latihan_lektor_pemazmur'));
        $this->assertFalse(PracticeRoom::allows($choir, 'rapat'));
        $this->assertFalse(PracticeRoom::allows($altar, null));
        $this->assertTrue(PracticeRoom::allows($lobby, 'rapat'));
    }
}
