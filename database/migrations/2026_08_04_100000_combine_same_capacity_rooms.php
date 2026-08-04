<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ruangan-ruangan dengan kapasitas sama ternyata punya partisi lipat dan
 * secara fisik selalu dipakai sebagai satu ruang gabungan, bukan unit
 * terpisah (203+204, 205+206, 301+302+303, 304+305+306, 307+308). Tambahkan
 * satu entitas ruangan gabungan per kelompok, lalu nonaktifkan (BUKAN hapus)
 * ruangan individunya.
 *
 * `is_active = false` dipilih ketimbang hapus/soft-delete supaya booking yang
 * sudah terlanjur menunjuk ke ruangan individu (termasuk data historis yang
 * baru diimpor) tetap bisa ditampilkan dengan benar — rooms pakai SoftDeletes,
 * dan `$booking->room` tidak akan resolve ke baris yang sudah di-soft-delete.
 */
return new class extends Migration
{
    /** @var list<array{name:string, members:list<string>, category_slug:string, floor:string}> */
    private array $groups = [
        ['name' => '203-204', 'members' => ['203', '204'], 'category_slug' => 'ruang-serbaguna-sedang', 'floor' => '2'],
        ['name' => '205-206', 'members' => ['205', '206'], 'category_slug' => 'ruang-kelas-pertemuan-kecil', 'floor' => '2'],
        ['name' => '301-302-303', 'members' => ['301', '302', '303'], 'category_slug' => 'ruang-serbaguna-sedang', 'floor' => '3'],
        ['name' => '304-305-306', 'members' => ['304', '305', '306'], 'category_slug' => 'ruang-serbaguna-sedang', 'floor' => '3'],
        ['name' => '307-308', 'members' => ['307', '308'], 'category_slug' => 'ruang-kelas-pertemuan-kecil', 'floor' => '3'],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->groups as $group) {
            $categoryId = DB::table('room_categories')->where('slug', $group['category_slug'])->value('id');
            if (! $categoryId) {
                continue;
            }

            $members = DB::table('rooms')->whereIn('name', $group['members'])->get(['name', 'capacity']);
            if ($members->isEmpty()) {
                continue;
            }

            $totalCapacity = $members->sum('capacity');
            $memberList = $members->pluck('name')->implode(', ');

            DB::table('rooms')->insert([
                'id' => (string) Str::uuid(),
                'name' => $group['name'],
                'slug' => Str::slug($group['name']),
                'category_id' => $categoryId,
                'description' => "Ruang gabungan dari {$memberList} (partisi dilipat). Kapasitas gabungan {$totalCapacity} orang.",
                'capacity' => $totalCapacity,
                'floor' => $group['floor'],
                'building' => 'GKP Harapan Indah',
                'status' => 'available',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('rooms')->whereIn('name', $group['members'])->update(['is_active' => false, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        $combinedNames = array_column($this->groups, 'name');
        $memberNames = array_merge(...array_column($this->groups, 'members'));

        DB::table('rooms')->whereIn('name', $combinedNames)->delete();
        DB::table('rooms')->whereIn('name', $memberNames)->update(['is_active' => true, 'updated_at' => now()]);
    }
};
