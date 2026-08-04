<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cari booking rutin yang tercatat duplikat persis (title+description+room+jam+
 * daftar tanggal recurring_dates sama persis) — sisa dari proses gabung ruangan
 * sebelumnya yang sengaja tidak menggabungkan booking rutin. Sisakan satu (yang
 * paling lama dibuat), hapus sisanya. Dry-run default, --commit untuk eksekusi.
 */
class DedupeRecurringBookings extends Command
{
    protected $signature = 'bookings:dedupe-recurring {--commit : Benar-benar hapus, bukan cuma pratinjau}';

    protected $description = 'Hapus booking rutin yang tercatat duplikat persis (title, deskripsi, ruangan, jam, tanggal sama)';

    public function handle(): int
    {
        $isDryRun = ! $this->option('commit');
        $this->info($isDryRun ? '=== MODE DRY-RUN (tidak menghapus apa pun) ===' : '=== MODE COMMIT (akan menghapus) ===');

        $bookings = DB::table('bookings')
            ->where('booking_type', 'rutin')
            ->orderBy('created_at')
            ->get(['id', 'title', 'description', 'room_id', 'start_time', 'end_time', 'recurring_dates', 'status', 'created_at']);

        $groups = $bookings->groupBy(function ($b) {
            return implode('|', [
                $b->title,
                $b->description,
                $b->room_id,
                $b->start_time,
                $b->end_time,
                $b->status,
                $b->recurring_dates,
            ]);
        });

        $toDelete = [];
        $groupsWithDupes = 0;

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $groupsWithDupes++;
            $survivor = $group->first();
            $dupes = $group->skip(1);

            $this->line("\n\"{$survivor->title}\" | {$survivor->description} | jam {$survivor->start_time}-{$survivor->end_time}");
            $this->line('  Disimpan: id='.$survivor->id." (dibuat {$survivor->created_at})");
            foreach ($dupes as $d) {
                $this->line('  Dihapus:  id='.$d->id." (dibuat {$d->created_at})");
                $toDelete[] = $d->id;
            }
        }

        $this->newLine();
        $this->info("Grup duplikat ditemukan: {$groupsWithDupes}. Total baris yang akan dihapus: ".count($toDelete));

        if (! $isDryRun && ! empty($toDelete)) {
            DB::table('bookings')->whereIn('id', $toDelete)->delete();
        }

        if ($isDryRun) {
            $this->newLine();
            $this->comment('Ini baru pratinjau. Jalankan ulang dengan --commit untuk benar-benar menghapus.');
        }

        return self::SUCCESS;
    }
}
