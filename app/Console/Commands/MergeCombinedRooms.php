<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lanjutan dari migrasi `combine_same_capacity_rooms`: migrasi HAPUS FISIK
 * ruangan individu (203, 204, dst) alih-alih sekadar dinonaktifkan, dan
 * sesuaikan seluruh data yang mereferensikannya (booking, foto ruangan,
 * jadwal maintenance) supaya menunjuk ke ruangan gabungan.
 *
 * Urutan wajib: sesuaikan dulu data anak (booking/foto/maintenance) SEBELUM
 * menghapus baris ruangannya — `bookings.room_id` tidak cascade-delete
 * (RESTRICT), jadi kalau masih ada booking yang menunjuk ke ruangan individu,
 * penghapusan akan gagal di level database (aman, tidak akan menghapus
 * sebagian lalu gagal di tengah jalan tanpa alasan jelas).
 *
 * Booking tunggal (reguler) yang aslinya satu kegiatan tercatat sebagai
 * beberapa baris (satu per ruangan individu — pola dari impor Excel
 * sebelumnya) DIGABUNG jadi satu baris di ruangan gabungan. Booking rutin
 * SENGAJA TIDAK digabung (cuma dipindah room_id-nya satu-satu) karena
 * tanggal yang berhasil per ruangan bisa berbeda (ada yang lolos ada yang
 * bentrok) — menggabungkannya otomatis berisiko menghilangkan tanggal yang
 * sah tanpa cara aman menentukan mana yang benar.
 */
class MergeCombinedRooms extends Command
{
    protected $signature = 'rooms:merge-combined {--commit : Benar-benar ubah & hapus, bukan cuma pratinjau}';

    protected $description = 'Hapus ruangan individu yang sudah digabung, pindahkan booking/foto/maintenance-nya ke ruangan gabungan';

    /** @var list<array{combined:string, members:list<string>}> */
    private array $groups = [
        ['combined' => '203-204', 'members' => ['203', '204']],
        ['combined' => '205-206', 'members' => ['205', '206']],
        ['combined' => '301-302-303', 'members' => ['301', '302', '303']],
        ['combined' => '304-305-306', 'members' => ['304', '305', '306']],
        ['combined' => '307-308', 'members' => ['307', '308']],
    ];

    public function handle(): int
    {
        $isDryRun = ! $this->option('commit');
        $this->info($isDryRun ? '=== MODE DRY-RUN (tidak mengubah apa pun) ===' : '=== MODE COMMIT (akan mengubah & menghapus) ===');

        DB::beginTransaction();

        try {
            foreach ($this->groups as $group) {
                $this->processGroup($group, $isDryRun);
            }

            if ($isDryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->newLine();
            $this->comment('Ini baru pratinjau. Jalankan ulang dengan --commit untuk benar-benar mengeksekusi.');
        }

        return self::SUCCESS;
    }

    private function processGroup(array $group, bool $isDryRun): void
    {
        $combinedId = DB::table('rooms')->where('name', $group['combined'])->value('id');
        if (! $combinedId) {
            $this->warn("Ruangan gabungan '{$group['combined']}' tidak ditemukan — lewati grup ini (pastikan migrasi combine_same_capacity_rooms sudah jalan).");

            return;
        }

        $members = DB::table('rooms')->whereIn('name', $group['members'])->get(['id', 'name']);
        if ($members->isEmpty()) {
            $this->warn("Ruangan individu untuk grup '{$group['combined']}' tidak ditemukan (mungkin sudah pernah dihapus) — lewati.");

            return;
        }
        $memberIds = $members->pluck('id')->all();

        $this->line("\n== {$group['combined']} (dari ".$members->pluck('name')->implode(', ').') ==');

        // 1) Pindahkan foto ruangan & jadwal maintenance supaya tidak ikut
        // terhapus oleh cascadeOnDelete saat ruangan individu dihapus.
        $imageCount = DB::table('room_images')->whereIn('room_id', $memberIds)->count();
        $maintCount = DB::table('maintenance_schedules')->whereIn('room_id', $memberIds)->count();
        $this->line("  Foto ruangan dipindah: {$imageCount}");
        $this->line("  Jadwal maintenance dipindah: {$maintCount}");
        if (! $isDryRun) {
            DB::table('room_images')->whereIn('room_id', $memberIds)->update(['room_id' => $combinedId]);
            DB::table('maintenance_schedules')->whereIn('room_id', $memberIds)->update(['room_id' => $combinedId]);
        }

        // 2) Booking reguler: gabungkan baris yang merupakan kegiatan sama
        // (peminjam+tanggal+jam+pemilik sama) yang tercatat terpisah per ruangan.
        $regulars = DB::table('bookings')
            ->whereIn('room_id', $memberIds)
            ->where('booking_type', 'reguler')
            ->get(['id', 'user_id', 'title', 'booking_date', 'start_time', 'end_time', 'room_id']);

        $clusters = $regulars->groupBy(fn ($b) => implode('|', [$b->user_id, $b->title, $b->booking_date, $b->start_time, $b->end_time]));

        $merged = 0;
        $repointedSingle = 0;
        foreach ($clusters as $cluster) {
            $survivor = $cluster->first();
            $duplicates = $cluster->skip(1);

            if (! $isDryRun) {
                DB::table('bookings')->where('id', $survivor->id)->update(['room_id' => $combinedId]);
                if ($duplicates->isNotEmpty()) {
                    DB::table('bookings')->whereIn('id', $duplicates->pluck('id'))->delete();
                }
            }

            if ($duplicates->isNotEmpty()) {
                $merged += $duplicates->count();
            } else {
                $repointedSingle++;
            }
        }
        $this->line("  Booking reguler digabung jadi satu (baris duplikat dihapus): {$merged}");
        $this->line("  Booking reguler dipindah tanpa duplikat: {$repointedSingle}");

        // 3) Booking rutin: pindah room_id satu-satu, TANPA digabung (lihat
        // penjelasan di docblock class).
        $rutinCount = DB::table('bookings')
            ->whereIn('room_id', $memberIds)
            ->where('booking_type', 'rutin')
            ->count();
        $this->line("  Booking rutin dipindah (tanpa digabung): {$rutinCount}");
        if (! $isDryRun) {
            DB::table('bookings')
                ->whereIn('room_id', $memberIds)
                ->where('booking_type', 'rutin')
                ->update(['room_id' => $combinedId]);
        }

        // 4) Hapus ruangan individu — aman sekarang karena semua yang
        // mereferensikannya sudah dipindah.
        if (! $isDryRun) {
            DB::table('rooms')->whereIn('id', $memberIds)->delete();
        }
        $this->line('  Ruangan individu dihapus: '.$members->pluck('name')->implode(', '));
    }
}
