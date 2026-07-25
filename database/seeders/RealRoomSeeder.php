<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Database\Seeder;

/**
 * Data ruangan asli gedung GKP Harapan Indah, ditranskripsi dari dokumen fisik
 * "Data Ruang GKP Harapan Indah" (5 lantai, 21 ruangan). `capacity` pakai kolom
 * "Kapasitas Baru (Bu Prima)" — kapasitas lama disimpan di teks description untuk
 * referensi. `patron_name` (nama santo/santa pelindung) sengaja dikosongkan —
 * diisi manual belakangan lewat halaman Kelola Ruangan.
 */
class RealRoomSeeder extends Seeder
{
    public function run(): void
    {
        $building = 'GKP Harapan Indah';

        $rooms = [
            [
                'name' => 'Lobby',
                'category_slug' => 'lobby-area-umum',
                'description' => 'Area lobby utama gedung. Detail perlengkapan: lihat bagian penyimpanan.',
                'capacity' => 232,
                'floor' => '1',
                'facilities' => [],
            ],
            [
                'name' => '201',
                'category_slug' => 'ruang-rapat-pelayanan',
                'description' => 'Kapasitas lama (Glory): DPH. LCD TV 1, Proyektor 1, Layar Proyektor 1, AC 2. Perlengkapan lain: Kursi Besar Hitam 2, Kursi Sedang Hitam 22, Meja Rapat 1.',
                'capacity' => 20,
                'floor' => '2',
                'facilities' => ['Proyektor / LCD', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '202',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 15. Papan tulis kaca tempel tembok 1, Kursi Kuliah 16, Meja Kecil Depan 1, Kursi Sandar 250 (angka ini perlu dicek ulang — tidak wajar untuk kapasitas ruang 20, kemungkinan salah baca dokumen sumber), AC 1. Perlengkapan lain: Meja Bening Sedang Kaca 1, Kursi Makan Abu-abu 3, Kursi Hitam seperti Ruang 201 sebanyak 7.',
                'capacity' => 20,
                'floor' => '2',
                'facilities' => ['Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '203',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Kapasitas lama: 200 (gabungan dengan Ruang 204). LCD TV 2 (nilai gabungan), Meja Kecil Depan 2 (gabungan), AC 6 (gabungan). Perlengkapan lain: Meja Sedang Coklat 2, Kursi Bakso 2, Tiang Bendera 2, Microphone 2. Ada sekat dan bisa terhubung dengan Ruang 204; AC berada di langit-langit ruang.',
                'capacity' => 200,
                'floor' => '2',
                'facilities' => ['Proyektor / LCD', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '204',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Kapasitas lama: 200 (gabungan dengan Ruang 203). Nilai fasilitas sama seperti Ruang 203 (dihitung gabungan). Ada sekat dan bisa terhubung dengan Ruang 203; AC berada di langit-langit ruang.',
                'capacity' => 200,
                'floor' => '2',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '205',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 50 (gabungan dengan Ruang 206). AC 3 (nilai gabungan). Perlengkapan lain: Meja Lipat Kecil 1, Meja Panjang Sedang Coklat 2, Kabel Roll 2. Ada sekat dan bisa terhubung dengan Ruang 206.',
                'capacity' => 50,
                'floor' => '2',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '206',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 50 (gabungan dengan Ruang 205). LCD TV 1, Proyektor 1, Layar Proyektor 1, Papan Tulis 1, Kursi Sandar 62, AC (nilai gabungan dengan Ruang 205). Ada sekat dan bisa terhubung dengan Ruang 205.',
                'capacity' => 50,
                'floor' => '2',
                'facilities' => ['Proyektor / LCD', 'Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '301',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Kapasitas lama: 70 (gabungan dengan Ruang 302 & 303). LCD TV 1, Kursi Kuliah 80 (nilai gabungan), AC 1. Ada sekat dan bisa terhubung dengan Ruang 302/303.',
                'capacity' => 72,
                'floor' => '3',
                'facilities' => ['Proyektor / LCD', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '302',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Kapasitas lama: 70 (gabungan dengan Ruang 301 & 303). Kursi Kuliah 80 (nilai gabungan), AC 1. Ada sekat dan bisa terhubung dengan Ruang 301/303.',
                'capacity' => 72,
                'floor' => '3',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '303',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Kapasitas lama: 70 (gabungan dengan Ruang 301 & 302). Proyektor 1, Layar Proyektor 1, Kursi Kuliah 80 (nilai gabungan), Meja Kecil Depan 1, Kursi Sandar 1, AC 1. Ada sekat dan bisa terhubung dengan Ruang 301/302.',
                'capacity' => 72,
                'floor' => '3',
                'facilities' => ['Proyektor / LCD', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '304',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Kapasitas lama: 90 (gabungan dengan Ruang 305 & 306). Kursi Kuliah 88 (nilai gabungan), AC 1. Ada sekat dan bisa terhubung dengan Ruang 305/306.',
                'capacity' => 94,
                'floor' => '3',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '305',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Kapasitas lama: 90 (gabungan dengan Ruang 304 & 306). Kursi Kuliah 88 (nilai gabungan), Meja Kecil Depan 1, AC 1. Ada sekat dan bisa terhubung dengan Ruang 304/306.',
                'capacity' => 94,
                'floor' => '3',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '306',
                'category_slug' => 'ruang-serbaguna-sedang',
                'description' => 'Kapasitas lama: 90 (gabungan dengan Ruang 304 & 305). LCD TV 1, Kursi Kuliah 88 (nilai gabungan), Meja Kecil Depan 1, AC 1. Ada sekat dan bisa terhubung dengan Ruang 304/305.',
                'capacity' => 94,
                'floor' => '3',
                'facilities' => ['Proyektor / LCD', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '307',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 15. Papan Tulis 1, Kursi Kuliah 17, Meja Kecil Depan 1, Kursi Sandar 1, AC 1. Perlengkapan lain: Standing Buku Kotak Kecil 2.',
                'capacity' => 17,
                'floor' => '3',
                'facilities' => ['Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '308',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 15. Papan Tulis 1, Kursi Kuliah 21, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 17,
                'floor' => '3',
                'facilities' => ['Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '309',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 30 s.d 35. Kursi Kuliah 34, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 35,
                'floor' => '3',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '310',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 2 s.d 3. Kursi Kuliah 1, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 4,
                'floor' => '3',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '311',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 15. Papan Tulis 1, Kursi Kuliah 21, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 19,
                'floor' => '3',
                'facilities' => ['Whiteboard', 'AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '312',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 18 s.d 20. Kursi Kuliah 19, Meja Kecil Depan 1, Kursi Sandar 1, AC 1.',
                'capacity' => 22,
                'floor' => '3',
                'facilities' => ['AC / Pendingin', 'Meja & Kursi'],
            ],
            [
                'name' => '401 / Auditorium',
                'category_slug' => 'ruang-ibadah-utama',
                'description' => 'Kapasitas lama: 300. Proyektor 1, Kursi Sandar 208. Perlengkapan lain: Keyboard 1, Kursi Keyboard 1, Rangka Backdrop 1, Mimbar Kitab Suci 1, Speaker Besar 2, Speaker Kecil 2, Panggung 1.',
                'capacity' => 310,
                'floor' => '4',
                'facilities' => ['Proyektor / LCD', 'Sound System', 'Alat Musik', 'Panggung', 'Meja & Kursi'],
            ],
            [
                'name' => '501',
                'category_slug' => 'ruang-kelas-pertemuan-kecil',
                'description' => 'Kapasitas lama: 70. AC 3. Perlengkapan lain: Kursi Sandar Plastik Biru 199. Catatan: 1 AC baru, 2 AC lama (1 rusak, 1 kurang dingin).',
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
