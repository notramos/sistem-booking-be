<?php

namespace Database\Seeders;

use App\Models\RoomCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Ruang Ibadah Utama', 'description' => 'Ruang untuk ibadah raya dan kebaktian'],
            ['name' => 'Aula / Serbaguna', 'description' => 'Ruang serbaguna untuk acara gereja dan pertemuan besar'],
            ['name' => 'Ruang Pastoral', 'description' => 'Ruang untuk konseling dan pembinaan pastoral'],
            ['name' => 'Ruang Sekolah Minggu', 'description' => 'Ruang untuk pengajaran anak-anak dan remaja'],
            ['name' => 'Ruang Musik / Koor', 'description' => 'Ruang untuk latihan paduan suara dan musik'],
            ['name' => 'Ruang Rapat Pelayanan', 'description' => 'Ruang untuk rapat koordinasi pelayanan'],
            ['name' => 'Lapangan / Area Outdoor', 'description' => 'Area outdoor untuk kegiatan gereja'],
        ];

        foreach ($categories as $cat) {
            RoomCategory::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                $cat
            );
        }
    }
}
