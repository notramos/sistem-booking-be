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
                'name' => 'Lobby',
                'patron_name' => 'Aristoteles',
                'category_slug' => 'lobby-area-umum',
                'description' => 'Area lobby utama gedung. Detail perlengkapan: lihat bagian penyimpanan.',
                'capacity' => 232,
                'floor' => '1',
                'facilities' => [],
            ],
            [
                'name' => '201',
                'patron_name' => 'Dominikus',
                'category_slug' => 'ruang-rapat-pelayanan',
                'description' => 'LCD TV 1, Proyektor 1, Layar Proyektor 1, AC 2. Perlengkapan lain: Kursi Besar Hitam 2, Kursi Sedang Hitam 22, Meja Rapat 1.',
                'capacity' => 20,
                'floor' => '2',
                'facilities' => ['Proyektor / LCD', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '202',
                'patron_name' => 'Elisabeth',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Papan tulis kaca tempel tembok 1, Kursi Kuliah 16, Meja Kecil Depan 1, AC 1. Perlengkapan lain: Meja Bening Sedang Kaca 1, Kursi Makan Abu-abu 3, Kursi Hitam seperti Ruang 201 sebanyak 7.',
                'capacity' => 20,
                'floor' => '2',
                'facilities' => ['Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '203-204',
                'patron_name' => 'Thomas Aquinas',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Ruang bersekat (203 & 204) yang selalu dipakai gabungan sebagai satu ruang serbaguna. LCD TV 2, Meja Kecil Depan 2, AC 6. Perlengkapan lain: Meja Sedang Coklat 2, Kursi Bakso 2, Tiang Bendera 2, Microphone 2.',
                'capacity' => 200,
                'floor' => '2',
                'facilities' => ['Proyektor / LCD', 'Sound System', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '205-206',
                'patron_name' => 'Clara',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Ruang bersekat (205 & 206) yang selalu dipakai gabungan. LCD TV 1, Proyektor 1, Layar Proyektor 1, Papan Tulis 1, AC 3. Perlengkapan lain: Meja Lipat Kecil 1, Meja Panjang Sedang Coklat 2, Kabel Roll 2.',
                'capacity' => 50,
                'floor' => '2',
                'facilities' => ['Proyektor / LCD', 'Sound System', 'Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '301-302-303',
                'patron_name' => 'Petrus Verona',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Ruang bersekat (301, 302 & 303) yang selalu dipakai gabungan. LCD TV 1, Proyektor 1, Layar Proyektor 1, Kursi Kuliah 80, Meja Kecil Depan 1, AC 3.',
                'capacity' => 72,
                'floor' => '3',
                'facilities' => ['Proyektor / LCD', 'Sound System', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '304-305-306',
                'patron_name' => 'Antonius Padua',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Ruang bersekat (304, 305 & 306) yang selalu dipakai gabungan. LCD TV 1, Kursi Kuliah 88, Meja Kecil Depan 1, AC 3.',
                'capacity' => 94,
                'floor' => '3',
                'facilities' => ['Proyektor / LCD', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '307',
                'patron_name' => 'Agnes',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Papan Tulis 1, Kursi Kuliah 17, Meja Kecil Depan 1, Kursi Sandar 1, AC 1. Perlengkapan lain: Standing Buku Kotak Kecil 2.',
                'capacity' => 17,
                'floor' => '3',
                'facilities' => ['Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '308',
                'patron_name' => 'Rosalia',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Papan Tulis 1, Kursi Kuliah 21, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 17,
                'floor' => '3',
                'facilities' => ['Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '309',
                'patron_name' => 'Fransiskus Asisi',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kursi Kuliah 34, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 35,
                'floor' => '3',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '310',
                'patron_name' => 'Louis IX',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kursi Kuliah 1, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 4,
                'floor' => '3',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '311',
                'patron_name' => 'Damianus',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Papan Tulis 1, Kursi Kuliah 21, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 19,
                'floor' => '3',
                'facilities' => ['Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '312',
                'patron_name' => 'Tarsisius',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kursi Kuliah 19, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 22,
                'floor' => '3',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '401 / Auditorium',
                'patron_name' => 'Veritas',
                'category_slug' => 'ruang-ibadah-utama',
                'description' => 'Proyektor 1, Kursi Sandar 208. Perlengkapan lain: Keyboard 1, Kursi Keyboard 1, Rangka Backdrop 1, Mimbar Kitab Suci 1, Speaker Besar 2, Speaker Kecil 2, Panggung 1.',
                'capacity' => 310,
                'floor' => '4',
                'facilities' => ['Proyektor / LCD', 'Sound System', 'Alat Musik', 'Panggung', 'Meja & Kursi'],
            ],
            [
                'name' => '501',
                'patron_name' => 'Cecilia',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'AC 3. Perlengkapan lain: Kursi Sandar Plastik Biru 199. Catatan: 1 AC baru, 2 AC lama (1 rusak, 1 kurang dingin).',
                'capacity' => 40,
                'floor' => '5',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
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
