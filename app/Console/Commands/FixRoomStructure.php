<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\RoomFacility;
use App\Models\RoomImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Perbaiki data ruangan berdasarkan dokumen resmi "Data Ruangan GKP &
 * Fasilitasnya": isi nama pelindung (patron_name), betulkan kapasitas
 * gabungan yang salah dijumlah oleh `rooms:merge-combined` sebelumnya
 * (seharusnya nilai gabungan yang sama, bukan hasil penjumlahan), dan
 * pisahkan kembali "307-308" jadi 307 (Agnes) & 308 (Rosalia) — keduanya
 * ternyata ruang terpisah, bukan gabungan.
 *
 * Booking pada ruang gabungan "307-308" dipetakan balik ke 307/308
 * berdasarkan pola PIC+hari+jam yang diketahui dari
 * `ImportSpreadsheetBookings2026` (baris 333 & 352, sebelum digabung).
 * Booking yang tidak cocok pola manapun TIDAK ditebak — command berhenti
 * sebelum menghapus ruang gabungan supaya data itu tidak hilang.
 */
class FixRoomStructure extends Command
{
    protected $signature = 'rooms:fix-structure {--commit : Benar-benar ubah, bukan cuma pratinjau}';

    protected $description = 'Isi nama pelindung ruangan, betulkan kapasitas gabungan, pisah ulang 307-308';

    /** @var array<string, string> nama ruangan => nama pelindung */
    private array $patronNames = [
        'Lobby' => 'Aristoteles',
        '201' => 'Dominikus',
        '202' => 'Elisabeth',
        '203-204' => 'Thomas Aquinas',
        '205-206' => 'Clara',
        '301-302-303' => 'Petrus Verona',
        '304-305-306' => 'Antonius Padua',
        '309' => 'Fransiskus Asisi',
        '310' => 'Louis IX',
        '311' => 'Damianus',
        '312' => 'Tarsisius',
        '401 / Auditorium' => 'Veritas',
        '501' => 'Cecilia',
    ];

    /** @var array<string, int> nama ruangan gabungan => kapasitas benar (bukan hasil jumlah) */
    private array $correctedCapacities = [
        '203-204' => 200,
        '205-206' => 50,
        '301-302-303' => 72,
        '304-305-306' => 94,
    ];

    public function handle(): int
    {
        $isDryRun = ! $this->option('commit');
        $this->info($isDryRun ? '=== MODE DRY-RUN (tidak mengubah apa pun) ===' : '=== MODE COMMIT (akan mengubah) ===');

        DB::beginTransaction();

        try {
            $this->fixPatronNames($isDryRun);
            $this->fixCapacities($isDryRun);
            $aborted = ! $this->splitRoom307308($isDryRun);

            if ($isDryRun || $aborted) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            if ($aborted) {
                $this->error('Pemisahan 307-308 DIBATALKAN karena ada booking yang tidak cocok pola manapun (lihat peringatan di atas). Semua perubahan lain juga dibatalkan (transaksi tunggal) — perbaiki data booking itu dulu lalu jalankan ulang.');

                return self::FAILURE;
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

    private function fixPatronNames(bool $isDryRun): void
    {
        $this->line("\n== Nama pelindung (patron_name) ==");
        foreach ($this->patronNames as $roomName => $patron) {
            $room = Room::where('name', $roomName)->first();
            if (! $room) {
                $this->warn("  Ruangan '{$roomName}' tidak ditemukan — dilewati.");

                continue;
            }
            $this->line("  {$roomName} => {$patron}");
            if (! $isDryRun) {
                $room->update(['patron_name' => $patron]);
            }
        }
    }

    private function fixCapacities(bool $isDryRun): void
    {
        $this->line("\n== Kapasitas gabungan (dikoreksi dari hasil penjumlahan) ==");
        foreach ($this->correctedCapacities as $roomName => $capacity) {
            $room = Room::where('name', $roomName)->first();
            if (! $room) {
                $this->warn("  Ruangan '{$roomName}' tidak ditemukan — dilewati.");

                continue;
            }
            $this->line("  {$roomName}: {$room->capacity} => {$capacity}");
            if (! $isDryRun) {
                $room->update(['capacity' => $capacity]);
            }
        }
    }

    /** @return bool true jika berhasil (atau ruang 307-308 memang sudah tidak ada), false jika dibatalkan */
    private function splitRoom307308(bool $isDryRun): bool
    {
        $this->line("\n== Pisah ulang 307-308 ==");

        $combined = Room::where('name', '307-308')->first();
        if (! $combined) {
            $this->line('  Ruangan "307-308" tidak ditemukan (mungkin sudah pernah dipisah) — lewati.');

            return true;
        }

        // Buang dulu booking cancelled ("Pertemuan Rapat OMK Wilayah", "Adhi P - ASAK")
        // dan duplikat "Adhi Purwoko" (yang benar dipakai adalah "Adhi P") — dikonfirmasi user.
        $toDelete = DB::table('bookings')
            ->where('room_id', $combined->id)
            ->where(function ($q) {
                $q->where('status', 'cancelled')->orWhere('title', 'Adhi Purwoko');
            })
            ->get(['id', 'title', 'status']);

        foreach ($toDelete as $b) {
            $this->line("  Hapus (cancelled/duplikat): \"{$b->title}\" (status={$b->status})");
        }
        if (! $isDryRun && $toDelete->isNotEmpty()) {
            DB::table('bookings')->whereIn('id', $toDelete->pluck('id'))->delete();
        }

        $bookings = DB::table('bookings')
            ->where('room_id', $combined->id)
            ->whereNotIn('id', $toDelete->pluck('id'))
            ->get();

        $target308 = [];
        $target307 = [];
        $unmatched = [];

        foreach ($bookings as $b) {
            if ($this->matches308($b)) {
                $target308[] = $b->id;
            } elseif ($this->matches307($b)) {
                $target307[] = $b->id;
            } else {
                $unmatched[] = $b;
            }
        }

        if (! empty($unmatched)) {
            foreach ($unmatched as $b) {
                $this->error("  Tidak cocok pola manapun: id={$b->id} title=\"{$b->title}\" desc=\"{$b->description}\" date={$b->booking_date} {$b->start_time}-{$b->end_time}");
            }

            return false;
        }

        $newRooms = [
            '307' => ['patron' => 'Agnes', 'ids' => $target307],
            '308' => ['patron' => 'Rosalia', 'ids' => $target308],
        ];

        foreach ($newRooms as $name => $info) {
            $this->line("  Buat ruang {$name} ({$info['patron']}), booking dipindah: ".count($info['ids']));

            if ($isDryRun) {
                continue;
            }

            $slug = Str::slug($combined->building.'-'.$name);
            $newRoom = Room::create([
                'name' => $name,
                'patron_name' => $info['patron'],
                'category_id' => $combined->category_id,
                'description' => $combined->description,
                'capacity' => 17,
                'floor' => $combined->floor,
                'building' => $combined->building,
                'slug' => $slug,
                'status' => $combined->status,
                'is_active' => $combined->is_active,
            ]);

            $facilityIds = RoomFacility::whereIn('name', ['AC / Pendingin', 'Meja & Kursi', 'Whiteboard'])->pluck('id')->toArray();
            $newRoom->facilities()->sync($facilityIds);

            RoomImage::create([
                'room_id' => $newRoom->id,
                'image_path' => "rooms/placeholder-{$slug}.jpg",
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            if (! empty($info['ids'])) {
                DB::table('bookings')->whereIn('id', $info['ids'])->update(['room_id' => $newRoom->id]);
            }
        }

        if (! $isDryRun) {
            DB::table('room_images')->where('room_id', $combined->id)->delete();
            DB::table('maintenance_schedules')->where('room_id', $combined->id)->delete();
            $combined->forceDelete();
        }

        $this->line('  Ruangan gabungan "307-308" dihapus.');

        return true;
    }

    private function matches308(object $b): bool
    {
        return $b->booking_type === 'rutin'
            && $b->title === 'Ivana Laura'
            && str_contains((string) $b->description, 'dirigen')
            && substr($b->start_time, 0, 5) === '12:00'
            && substr($b->end_time, 0, 5) === '14:00';
    }

    private function matches307(object $b): bool
    {
        return $b->booking_type === 'rutin'
            && $b->title === 'Adhi P'
            && str_contains((string) $b->description, 'ASAK')
            && substr($b->start_time, 0, 5) === '10:00'
            && substr($b->end_time, 0, 5) === '11:30';
    }
}
