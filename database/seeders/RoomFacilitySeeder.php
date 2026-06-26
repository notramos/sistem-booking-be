<?php

namespace Database\Seeders;

use App\Models\RoomFacility;
use Illuminate\Database\Seeder;

class RoomFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['name' => 'Proyektor / LCD', 'icon' => 'projector'],
            ['name' => 'Sound System', 'icon' => 'speaker'],
            ['name' => 'AC / Pendingin', 'icon' => 'ac'],
            ['name' => 'Whiteboard', 'icon' => 'whiteboard'],
            ['name' => 'Meja & Kursi', 'icon' => 'chair'],
            ['name' => 'Koneksi Internet', 'icon' => 'wifi'],
            ['name' => 'Panggung', 'icon' => 'stage'],
            ['name' => 'Alat Musik', 'icon' => 'music'],
            ['name' => 'Soundproof', 'icon' => 'soundproof'],
            ['name' => 'Dapur / Pantry', 'icon' => 'kitchen'],
            ['name' => 'Toilet', 'icon' => 'toilet'],
            ['name' => 'Parkir Luas', 'icon' => 'parking'],
            ['name' => 'Akses Difabel', 'icon' => 'accessible'],
            ['name' => 'Kipas Angin', 'icon' => 'fan'],
        ];

        foreach ($facilities as $fac) {
            RoomFacility::firstOrCreate(
                ['name' => $fac['name']],
                $fac
            );
        }
    }
}
