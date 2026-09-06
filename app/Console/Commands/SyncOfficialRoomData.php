<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\RoomFacility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncOfficialRoomData extends Command
{
    protected $signature = 'rooms:sync-official-data {--commit : Simpan perubahan ke database}';

    protected $description = 'Sinkronkan nama, kapasitas, lantai, dan fasilitas ruangan dengan dokumen resmi GKP';

    /** @var array<int, array<string, mixed>> */
    private array $rooms = [
        ['aliases' => ['Lobby', 'Selasar Lobby'], 'name' => 'Selasar Lobby', 'patron' => 'Aristoteles', 'capacity' => 232, 'floor' => '1', 'facilities' => []],
        ['aliases' => ['201'], 'name' => '201', 'patron' => 'Dominikus', 'capacity' => 20, 'floor' => '2', 'facilities' => ['AC', 'LCD TV', 'Projector']],
        ['aliases' => ['202'], 'name' => '202', 'patron' => 'Elisabeth', 'capacity' => 20, 'floor' => '2', 'facilities' => ['AC', 'Kursi Sandar', 'Papan Tulis']],
        ['aliases' => ['203-204'], 'name' => '203-204', 'patron' => 'Thomas Aquinas', 'capacity' => 200, 'floor' => '2', 'facilities' => ['AC', 'LCD TV', 'Sound System', 'Kursi Sandar']],
        ['aliases' => ['205-206'], 'name' => '205-206', 'patron' => 'Clara', 'capacity' => 50, 'floor' => '2', 'facilities' => ['AC', 'LCD TV', 'Kursi Sandar', 'Papan Tulis']],
        ['aliases' => ['301-302-303'], 'name' => '301-302-303', 'patron' => 'Petrus Verona', 'capacity' => 72, 'floor' => '3', 'facilities' => ['AC', 'LCD TV', 'Projector', 'Kursi Sandar', 'Kursi Kuliah']],
        ['aliases' => ['304-305-306'], 'name' => '304-305-306', 'patron' => 'Antonius Padua', 'capacity' => 94, 'floor' => '3', 'facilities' => ['AC', 'LCD TV', 'Kursi Kuliah']],
        ['aliases' => ['307'], 'name' => '307', 'patron' => 'Agnes', 'capacity' => 17, 'floor' => '3', 'facilities' => ['AC', 'Kursi Kuliah', 'Papan Tulis']],
        ['aliases' => ['308'], 'name' => '308', 'patron' => 'Rosalia', 'capacity' => 17, 'floor' => '3', 'facilities' => ['AC', 'Kursi Kuliah', 'Papan Tulis']],
        ['aliases' => ['309'], 'name' => '309', 'patron' => 'Fransiskus Asisi', 'capacity' => 35, 'floor' => '3', 'facilities' => ['AC', 'Kursi Kuliah']],
        ['aliases' => ['310'], 'name' => '310', 'patron' => 'Louis IX', 'capacity' => 4, 'floor' => '3', 'facilities' => ['AC', 'Kursi Kuliah']],
        ['aliases' => ['311'], 'name' => '311', 'patron' => 'Damianus', 'capacity' => 19, 'floor' => '3', 'facilities' => ['AC', 'Kursi Kuliah', 'Papan Tulis']],
        ['aliases' => ['312'], 'name' => '312', 'patron' => 'Tarsisius', 'capacity' => 22, 'floor' => '3', 'facilities' => ['AC', 'Kursi Kuliah']],
        ['aliases' => ['401 / Auditorium', 'Auditorium'], 'name' => 'Auditorium', 'patron' => 'Veritas', 'capacity' => 310, 'floor' => '4', 'facilities' => ['AC', 'Projector', 'Sound System', 'Kursi Sandar']],
        ['aliases' => ['501'], 'name' => '501', 'patron' => 'Cecilia', 'capacity' => 40, 'floor' => '5', 'facilities' => ['AC', 'Kursi Sandar']],
    ];

    /** @var array<string, string> */
    private array $facilityIcons = [
        'AC' => 'ac',
        'LCD TV' => 'tv',
        'Projector' => 'projector',
        'Sound System' => 'speaker',
        'Kursi Sandar' => 'chair',
        'Kursi Kuliah' => 'chair',
        'Papan Tulis' => 'whiteboard',
    ];

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');
        $this->info($commit ? '=== MODE COMMIT ===' : '=== MODE DRY-RUN ===');

        DB::beginTransaction();

        try {
            $facilities = collect($this->facilityIcons)->mapWithKeys(function (string $icon, string $name) {
                $facility = RoomFacility::updateOrCreate(
                    ['name' => $name],
                    ['icon' => $icon, 'is_active' => true],
                );

                return [$name => $facility->id];
            });

            $deactivated = RoomFacility::whereNotIn('name', array_keys($this->facilityIcons))
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $this->line("Fasilitas resmi aktif: {$facilities->count()}; fasilitas lama dinonaktifkan: {$deactivated}");

            foreach ($this->rooms as $official) {
                $room = Room::whereIn('name', $official['aliases'])->first();
                if (! $room) {
                    throw new \RuntimeException('Ruangan tidak ditemukan: '.implode(' / ', $official['aliases']));
                }

                $description = $official['name'] === '201'
                    ? 'Ruang Dominikus nomor 201 (ruang DPH) di lantai 2.'
                    : ($official['name'] === 'Selasar Lobby'
                        ? 'Selasar Lobby Aristoteles di lantai 1.'
                        : "Ruang {$official['patron']} {$official['name']} di lantai {$official['floor']}.");

                $room->update([
                    'name' => $official['name'],
                    'patron_name' => $official['patron'],
                    'slug' => Str::slug('GKP Harapan Indah-'.$official['name']),
                    'description' => $description,
                    'capacity' => $official['capacity'],
                    'floor' => $official['floor'],
                    'building' => 'GKP Harapan Indah',
                    'is_active' => true,
                ]);

                $room->facilities()->sync(
                    collect($official['facilities'])->map(fn (string $name) => $facilities[$name])->all(),
                );

                $facilityText = empty($official['facilities']) ? '-' : implode(', ', $official['facilities']);
                $this->line("{$official['patron']} | {$official['name']} | Lt.{$official['floor']} | {$official['capacity']} orang | {$facilityText}");
            }

            if ($commit) {
                DB::commit();
                $this->info('Data ruangan resmi berhasil disimpan.');
            } else {
                DB::rollBack();
                $this->comment('Pratinjau selesai; tidak ada perubahan yang disimpan. Gunakan --commit untuk menyimpan.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Sinkronisasi dibatalkan: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
