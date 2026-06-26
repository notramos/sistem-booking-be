<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Database\Seeder;

class DemoRoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'name' => 'Ruang Ibadah Utama',
                'category_slug' => 'ruang-ibadah-utama',
                'description' => 'Ruang ibadah utama gereja dengan kapasitas besar, dilengkapi sound system dan proyektor.',
                'capacity' => 500,
                'floor' => '1',
                'building' => 'Gedung Gereja Utama',
                'facilities' => ['Proyektor / LCD', 'Sound System', 'AC / Pendingin', 'Meja & Kursi', 'Panggung', 'Alat Musik', 'Parkir Luas', 'Akses Difabel'],
            ],
            [
                'name' => 'Aula Serbaguna Lantai 2',
                'category_slug' => 'aula-serbaguna',
                'description' => 'Aula serbaguna untuk resepsi pernikahan, seminar, dan acara gereja lainnya.',
                'capacity' => 200,
                'floor' => '2',
                'building' => 'Gedung Gereja Utama',
                'facilities' => ['Proyektor / LCD', 'Sound System', 'AC / Pendingin', 'Whiteboard', 'Meja & Kursi', 'Koneksi Internet', 'Dapur / Pantry', 'Parkir Luas'],
            ],
            [
                'name' => 'Ruang Pastoral',
                'category_slug' => 'ruang-pastoral',
                'description' => 'Ruang konseling yang nyaman dan privat untuk pembinaan pastoral.',
                'capacity' => 10,
                'floor' => '1',
                'building' => 'Gedung Gereja Utama',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi', 'Toilet'],
            ],
            [
                'name' => 'Ruang Sekolah Minggu',
                'category_slug' => 'ruang-sekolah-minggu',
                'description' => 'Ruang ceria untuk pengajaran anak-anak sekolah minggu.',
                'capacity' => 60,
                'floor' => '1',
                'building' => 'Gedung Pendidikan',
                'facilities' => ['Proyektor / LCD', 'Sound System', 'Whiteboard', 'Meja & Kursi', 'Kipas Angin'],
            ],
            [
                'name' => 'Ruang Musik & Koor',
                'category_slug' => 'ruang-musik-koor',
                'description' => 'Ruang latihan paduan suara dan musik dengan akustik yang baik.',
                'capacity' => 40,
                'floor' => '3',
                'building' => 'Gedung Gereja Utama',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi', 'Alat Musik', 'Soundproof', 'Toilet'],
            ],
            [
                'name' => 'Ruang Rapat Pelayanan',
                'category_slug' => 'ruang-rapat-pelayanan',
                'description' => 'Ruang rapat untuk koordinasi tim pelayanan dan pengurus gereja.',
                'capacity' => 20,
                'floor' => '2',
                'building' => 'Gedung Gereja Utama',
                'facilities' => ['Proyektor / LCD', 'AC / Pendingin', 'Whiteboard', 'Meja & Kursi', 'Koneksi Internet', 'Toilet'],
            ],
            [
                'name' => 'Lapangan Serbaguna',
                'category_slug' => 'lapangan-area-outdoor',
                'description' => 'Lapangan outdoor untuk kegiatan olahraga dan acara gereja di luar ruangan.',
                'capacity' => 300,
                'floor' => 'Ground',
                'building' => 'Area Luar Gereja',
                'facilities' => ['Parkir Luas', 'Akses Difabel'],
            ],
        ];

        foreach ($rooms as $roomData) {
            $categoryId = \App\Models\RoomCategory::where('slug', $roomData['category_slug'])->value('id');
            $facilityNames = $roomData['facilities'];
            $slug = \Illuminate\Support\Str::slug($roomData['name']);
            unset($roomData['category_slug'], $roomData['facilities']);

            $roomData['category_id'] = $categoryId;
            $room = Room::firstOrCreate(['slug' => $slug], $roomData);
            $facilityIds = \App\Models\RoomFacility::whereIn('name', $facilityNames)->pluck('id')->toArray();
            $room->facilities()->sync($facilityIds);

            RoomImage::firstOrCreate(
                ['room_id' => $room->id, 'is_primary' => true],
                [
                    'image_path' => "rooms/placeholder-{$slug}.jpg",
                    'is_primary' => true,
                    'sort_order' => 0,
                ]
            );
        }
    }
}
