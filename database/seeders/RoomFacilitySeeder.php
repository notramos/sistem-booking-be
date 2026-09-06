<?php

namespace Database\Seeders;

use App\Models\RoomFacility;
use Illuminate\Database\Seeder;

class RoomFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['name' => 'AC', 'icon' => 'ac'],
            ['name' => 'LCD TV', 'icon' => 'tv'],
            ['name' => 'Projector', 'icon' => 'projector'],
            ['name' => 'Sound System', 'icon' => 'speaker'],
            ['name' => 'Kursi Sandar', 'icon' => 'chair'],
            ['name' => 'Kursi Kuliah', 'icon' => 'chair'],
            ['name' => 'Papan Tulis', 'icon' => 'whiteboard'],
        ];

        foreach ($facilities as $fac) {
            RoomFacility::updateOrCreate(
                ['name' => $fac['name']],
                [...$fac, 'is_active' => true]
            );
        }

        RoomFacility::whereNotIn('name', array_column($facilities, 'name'))
            ->update(['is_active' => false]);
    }
}
