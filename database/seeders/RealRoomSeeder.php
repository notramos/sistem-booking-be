<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Database\Seeder;

/**
 * Data ruangan asli gedung GKP Harapan Indah, ditranskripsi dari dokumen fisik
 * "Data Ruang GKP Harapan Indah" dan tabel resmi "Data Ruangan GKP &
 * Fasilitasnya" (14 ruangan efektif setelah ruang bersekat yang selalu
 * dipakai gabungan disatukan jadi satu entri: 203-204, 205-206, 301-302-303,
 * 304-305-306). `capacity` pakai nilai kapasitas gabungan resmi (bukan hasil
 * penjumlahan kapasitas per-ruang individu). `patron_name` = nama santo/santa
 * pelindung ruangan sesuai dokumen resmi.
 */
class RealRoomSeeder extends Seeder
{
    public function run(): void
    {
        $building = 'GKP Harapan Indah';

        $rooms = [
            [
                'name' => 'Selasar Lobby',
                'patron_name' => 'Aristoteles',
                'category_slug' => 'lobby-area-umum',
                'description' => 'Selasar lobby lantai 1.',
                'capacity' => 232,
                'floor' => '1',
                'facilities' => [],
            ],
            [
                'name' => '201',
                'patron_name' => 'Dominikus',
                'category_slug' => 'ruang-rapat-pelayanan',
                'description' => 'Ruang DPH di lantai 2.',
                'capacity' => 20,
                'floor' => '2',
                'facilities' => ['AC', 'LCD TV', 'Projector'],
            ],
            [
                'name' => '202',
                'patron_name' => 'Elisabeth',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang Elisabeth nomor 202 di lantai 2.',
                'capacity' => 20,
                'floor' => '2',
                'facilities' => ['AC', 'Kursi Sandar', 'Papan Tulis'],
            ],
            [
                'name' => '203-204',
                'patron_name' => 'Thomas Aquinas',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Ruang Thomas Aquinas, gabungan nomor 203-204 di lantai 2.',
                'capacity' => 200,
                'floor' => '2',
                'facilities' => ['AC', 'LCD TV', 'Sound System', 'Kursi Sandar'],
            ],
            [
                'name' => '205-206',
                'patron_name' => 'Clara',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang Clara, gabungan nomor 205-206 di lantai 2.',
                'capacity' => 50,
                'floor' => '2',
                'facilities' => ['AC', 'LCD TV', 'Kursi Sandar', 'Papan Tulis'],
            ],
            [
                'name' => '301-302-303',
                'patron_name' => 'Petrus Verona',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Ruang Petrus Verona, gabungan nomor 301-302-303 di lantai 3.',
                'capacity' => 72,
                'floor' => '3',
                'facilities' => ['AC', 'LCD TV', 'Projector', 'Kursi Sandar', 'Kursi Kuliah'],
            ],
            [
                'name' => '304-305-306',
                'patron_name' => 'Antonius Padua',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Ruang Antonius Padua, gabungan nomor 304-305-306 di lantai 3.',
                'capacity' => 94,
                'floor' => '3',
                'facilities' => ['AC', 'LCD TV', 'Kursi Kuliah'],
            ],
            [
                'name' => '307',
                'patron_name' => 'Agnes',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang Agnes nomor 307 di lantai 3.',
                'capacity' => 17,
                'floor' => '3',
                'facilities' => ['AC', 'Kursi Kuliah', 'Papan Tulis'],
            ],
            [
                'name' => '308',
                'patron_name' => 'Rosalia',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang Rosalia nomor 308 di lantai 3.',
                'capacity' => 17,
                'floor' => '3',
                'facilities' => ['AC', 'Kursi Kuliah', 'Papan Tulis'],
            ],
            [
                'name' => '309',
                'patron_name' => 'Fransiskus Asisi',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang Fransiskus Asisi nomor 309 di lantai 3.',
                'capacity' => 35,
                'floor' => '3',
                'facilities' => ['AC', 'Kursi Kuliah'],
            ],
            [
                'name' => '310',
                'patron_name' => 'Louis IX',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang Louis IX nomor 310 di lantai 3.',
                'capacity' => 4,
                'floor' => '3',
                'facilities' => ['AC', 'Kursi Kuliah'],
            ],
            [
                'name' => '311',
                'patron_name' => 'Damianus',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang Damianus nomor 311 di lantai 3.',
                'capacity' => 19,
                'floor' => '3',
                'facilities' => ['AC', 'Kursi Kuliah', 'Papan Tulis'],
            ],
            [
                'name' => '312',
                'patron_name' => 'Tarsisius',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang Tarsisius nomor 312 di lantai 3.',
                'capacity' => 22,
                'floor' => '3',
                'facilities' => ['AC', 'Kursi Kuliah'],
            ],
            [
                'name' => 'Auditorium',
                'patron_name' => 'Veritas',
                'category_slug' => 'ruang-ibadah-utama',
                'description' => 'Auditorium Veritas di lantai 4.',
                'capacity' => 310,
                'floor' => '4',
                'facilities' => ['AC', 'Projector', 'Sound System', 'Kursi Sandar'],
            ],
            [
                'name' => '501',
                'patron_name' => 'Cecilia',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang Cecilia nomor 501 di lantai 5.',
                'capacity' => 40,
                'floor' => '5',
                'facilities' => ['AC', 'Kursi Sandar'],
            ],
        ];

        foreach ($rooms as $roomData) {
            $categoryId = \App\Models\RoomCategory::where('slug', $roomData['category_slug'])->value('id');
            $facilityNames = $roomData['facilities'];
            $slug = \Illuminate\Support\Str::slug($building.'-'.$roomData['name']);
            unset($roomData['category_slug'], $roomData['facilities']);

            $roomData['category_id'] = $categoryId;
            $roomData['building'] = $building;
            $roomData['slug'] = $slug;
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
