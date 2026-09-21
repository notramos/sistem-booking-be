<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\MaintenanceSchedule;
use App\Models\Room;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportGkpWorkbook extends Command
{
    protected $signature = 'import:gkp-workbook
        {path=storage/app/imports/GKP.xlsx : Lokasi file GKP.xlsx}
        {--commit : Simpan data. Tanpa opsi ini hanya dry-run}
        {--report=storage/app/imports/gkp-import-report.csv : Lokasi laporan CSV}';

    protected $description = 'Impor jadwal peminjaman GKP dari lembar mingguan, dengan deteksi duplikat dan bentrok';

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $occupancy = [];

    /** @var array<int, array<string, mixed>> */
    private array $reportRows = [];

    /** @var array<string, string> */
    private array $roomIds = [];

    /** @var array<int, array<string, mixed>> */
    private array $metadata = [];

    public function handle(): int
    {
        $path = $this->absolutePath((string) $this->argument('path'));
        $reportPath = $this->absolutePath((string) $this->option('report'));
        $dryRun = ! $this->option('commit');

        $this->info($dryRun
            ? '=== MODE DRY-RUN (tidak menyimpan booking) ==='
            : '=== MODE COMMIT (menyimpan booking yang valid dan tidak bentrok) ===');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $owner = null;
        if (! $dryRun) {
            $owner = User::role('sekretariat')->orderBy('created_at')->first();
            if (! $owner) {
                $this->error('Akun dengan peran sekretariat tidak ditemukan.');

                return self::FAILURE;
            }
        }

        foreach (Room::whereNull('deleted_at')->get(['id', 'name']) as $room) {
            $this->roomIds[$this->normalize($room->name)] = $room->id;
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            $this->error('Gagal membaca workbook: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->metadata = $this->readMetadata($spreadsheet);
        [$occurrences, $sourceSkipped, $sourceDuplicates] = $this->readWeeklyOccurrences($spreadsheet);

        $this->loadDatabaseOccupancy();
        $sourceConflictIds = $this->findSourceConflicts($occurrences);

        $created = 0;
        $alreadyExists = 0;
        $databaseConflicts = 0;
        $maintenanceConflicts = 0;
        $sourceConflicts = 0;

        DB::beginTransaction();

        try {
            foreach ($occurrences as $occurrence) {
                if (isset($sourceConflictIds[$occurrence['key']])) {
                    $sourceConflicts++;

                    continue;
                }

                $conflict = $this->findOccupancyConflict($occurrence);
                if ($conflict) {
                    if ($this->isSameEvent($occurrence, $conflict)) {
                        $alreadyExists++;
                        $this->addReport($occurrence, 'already_exists', 'Sudah ada di database', $conflict['label']);
                    } elseif ($conflict['type'] === 'maintenance') {
                        $maintenanceConflicts++;
                        $this->addReport($occurrence, 'maintenance_conflict', 'Bentrok jadwal maintenance', $conflict['label']);
                    } else {
                        $databaseConflicts++;
                        $this->addReport($occurrence, 'database_conflict', 'Bentrok dengan booking di database', $conflict['label']);
                    }

                    continue;
                }

                $endAt = Carbon::parse($occurrence['date'].' '.$occurrence['end']);
                $isPast = $endAt->lessThanOrEqualTo(now());

                if (! $dryRun) {
                    Booking::create([
                        'user_id' => $owner->id,
                        'room_id' => $occurrence['room_id'],
                        'title' => $occurrence['pic'] ?: $occurrence['event'],
                        'description' => $this->buildDescription($occurrence),
                        'booking_date' => $occurrence['date'],
                        'start_time' => $occurrence['start'],
                        'end_time' => $occurrence['end'],
                        'contact_person' => $occurrence['phone'],
                        'expected_attendees' => $occurrence['attendees'],
                        'status' => $isPast ? 'completed' : 'approved',
                        'completed_at' => $isPast ? $endAt : null,
                        'booking_type' => 'reguler',
                    ]);
                }

                $this->markOccupied([
                    'room_id' => $occurrence['room_id'],
                    'date' => $occurrence['date'],
                    'start' => $occurrence['start'],
                    'end' => $occurrence['end'],
                    'type' => 'import',
                    'event' => $occurrence['event'],
                    'title' => $occurrence['pic'] ?: $occurrence['event'],
                    'description' => $this->buildDescription($occurrence),
                    'label' => "Impor {$occurrence['sheet']} baris {$occurrence['row']}: {$occurrence['event']}",
                ]);
                $created++;
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->writeReport($reportPath);
            $this->error('Impor dibatalkan: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->writeReport($reportPath);

        $this->newLine();
        $this->table(
            ['Hasil', 'Jumlah'],
            [
                ['Booking '.($dryRun ? 'siap dibuat' : 'dibuat'), $created],
                ['Sudah ada di database', $alreadyExists],
                ['Bentrok antarbaris sumber', $sourceConflicts],
                ['Bentrok booking database', $databaseConflicts],
                ['Bentrok maintenance', $maintenanceConflicts],
                ['Duplikat persis di workbook', $sourceDuplicates],
                ['Data sumber ambigu/tidak valid', $sourceSkipped],
            ]
        );
        $this->info('Laporan: '.$reportPath);

        if ($dryRun) {
            $this->comment('Belum ada booking yang disimpan. Periksa laporan, lalu jalankan ulang dengan --commit.');
        }

        return self::SUCCESS;
    }

    /** @return array{0:array<int,array<string,mixed>>,1:int,2:int} */
    private function readWeeklyOccurrences($spreadsheet): array
    {
        $occurrences = [];
        $seen = [];
        $sourceSkipped = 0;
        $sourceDuplicates = 0;

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (! preg_match('/^\d{1,2}-/', $sheet->getTitle())) {
                continue;
            }

            $highestRow = $sheet->getHighestDataRow();
            $lastDate = null;
            for ($row = 7; $row <= $highestRow; $row++) {
                $date = $this->excelDate($sheet->getCell("A{$row}")->getValue());
                if ($date) {
                    $lastDate = $date;
                }
                $sourceRoom = trim((string) $sheet->getCell("B{$row}")->getFormattedValue());
                $rawStart = trim((string) $sheet->getCell("C{$row}")->getFormattedValue());
                $rawEnd = trim((string) $sheet->getCell("E{$row}")->getFormattedValue());
                $event = $this->cleanText($sheet->getCell("F{$row}")->getFormattedValue());

                if (! $date && $sourceRoom === '' && $rawStart === '' && $rawEnd === '' && $event === '') {
                    continue;
                }

                // Beberapa lembar memakai merged cell tanggal; baris lanjutan mewarisi tanggal di atasnya.
                $date ??= $lastDate;

                $base = [
                    'sheet' => $sheet->getTitle(),
                    'row' => $row,
                    'date' => $date ?: '',
                    'source_room' => $sourceRoom,
                    'room' => '',
                    'start' => '',
                    'end' => '',
                    'event' => $event,
                ];

                if (! $date || $sourceRoom === '' || $event === '') {
                    $sourceSkipped++;
                    $this->addReport($base, 'invalid_source', 'Tanggal, ruangan, atau kegiatan kosong');

                    continue;
                }

                $times = $this->parseTimes($rawStart, $rawEnd);
                if (! $times) {
                    $sourceSkipped++;
                    $this->addReport($base, 'ambiguous_time', "Jam tidak dapat dipastikan: {$rawStart} - {$rawEnd}");

                    continue;
                }

                $rooms = $this->mapRooms($sourceRoom);
                if (! $rooms) {
                    $sourceSkipped++;
                    $this->addReport($base, 'unmapped_room', 'Ruangan tidak dikenali');

                    continue;
                }

                $meta = $this->findMetadata($event);
                foreach ($rooms as $roomName) {
                    $roomId = $this->resolveRoomId($roomName);
                    $occurrence = [
                        ...$base,
                        'room' => $roomName,
                        'room_id' => $roomId,
                        'start' => $times['start'],
                        'end' => $times['end'],
                        'pic' => $meta['pic'] ?? null,
                        'phone' => $meta['phone'] ?? null,
                        'category' => $meta['category'] ?? null,
                        'attendees' => $meta['attendees'] ?? null,
                    ];

                    if (! $roomId) {
                        $sourceSkipped++;
                        $this->addReport($occurrence, 'room_missing_in_database', 'Ruangan hasil mapping tidak ada di master data');

                        continue;
                    }

                    $signature = implode('|', [
                        $date, $roomId, $times['start'], $times['end'], $this->normalize($event),
                    ]);
                    if (isset($seen[$signature])) {
                        $sourceDuplicates++;
                        $this->addReport($occurrence, 'source_duplicate', 'Duplikat persis di workbook', $seen[$signature]);

                        continue;
                    }

                    $seen[$signature] = "{$sheet->getTitle()} baris {$row}";
                    $occurrence['key'] = $signature;
                    $occurrences[] = $occurrence;
                }
            }
        }

        usort($occurrences, fn (array $a, array $b) => [$a['date'], $a['start'], $a['room']] <=> [$b['date'], $b['start'], $b['room']]);

        return [$occurrences, $sourceSkipped, $sourceDuplicates];
    }

    /** @return array<string, bool> */
    private function findSourceConflicts(array $occurrences): array
    {
        $conflicts = [];
        $groups = [];
        foreach ($occurrences as $occurrence) {
            $groups[$occurrence['room_id'].'|'.$occurrence['date']][] = $occurrence;
        }

        foreach ($groups as $rows) {
            $count = count($rows);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if (! $this->overlaps($rows[$i]['start'], $rows[$i]['end'], $rows[$j]['start'], $rows[$j]['end'])) {
                        continue;
                    }

                    $conflicts[$rows[$i]['key']] = true;
                    $conflicts[$rows[$j]['key']] = true;
                    $this->addReport(
                        $rows[$i],
                        'source_conflict',
                        'Bentrok dengan baris lain di workbook',
                        "{$rows[$j]['sheet']} baris {$rows[$j]['row']}: {$rows[$j]['event']} {$rows[$j]['start']}-{$rows[$j]['end']}"
                    );
                    $this->addReport(
                        $rows[$j],
                        'source_conflict',
                        'Bentrok dengan baris lain di workbook',
                        "{$rows[$i]['sheet']} baris {$rows[$i]['row']}: {$rows[$i]['event']} {$rows[$i]['start']}-{$rows[$i]['end']}"
                    );
                }
            }
        }

        return $conflicts;
    }

    private function loadDatabaseOccupancy(): void
    {
        $bookings = Booking::whereNotIn('status', ['rejected', 'cancelled'])
            ->where(function ($query) {
                $query->whereBetween('booking_date', ['2026-01-01', '2027-01-02'])
                    ->orWhereNotNull('recurring_dates');
            })
            ->get(['id', 'room_id', 'title', 'description', 'booking_date', 'start_time', 'end_time', 'recurring_dates', 'status']);

        foreach ($bookings as $booking) {
            $dates = $booking->recurring_dates ?: [$booking->booking_date->toDateString()];
            foreach ($dates as $date) {
                if ($date < '2026-01-01' || $date > '2027-01-02') {
                    continue;
                }
                $this->markOccupied([
                    'room_id' => $booking->room_id,
                    'date' => $date,
                    'start' => substr((string) $booking->start_time, 0, 5),
                    'end' => substr((string) $booking->end_time, 0, 5),
                    'type' => 'booking',
                    'event' => $booking->description ?: $booking->title,
                    'title' => $booking->title,
                    'description' => $booking->description,
                    'label' => "Booking {$booking->id} [{$booking->status}] {$booking->title} {$booking->start_time}-{$booking->end_time}",
                ]);
            }
        }

        $maintenance = MaintenanceSchedule::where('start_date', '<=', '2027-01-02')
            ->where('end_date', '>=', '2026-01-01')
            ->get();

        foreach ($maintenance as $schedule) {
            $cursor = $schedule->start_date->copy();
            $last = $schedule->end_date->copy();
            while ($cursor->lessThanOrEqualTo($last)) {
                $date = $cursor->toDateString();
                if ($date >= '2026-01-01' && $date <= '2027-01-02') {
                    $this->markOccupied([
                        'room_id' => $schedule->room_id,
                        'date' => $date,
                        'start' => $schedule->is_all_day ? '00:00' : substr((string) $schedule->start_time, 0, 5),
                        'end' => $schedule->is_all_day ? '23:59' : substr((string) $schedule->end_time, 0, 5),
                        'type' => 'maintenance',
                        'event' => $schedule->title,
                        'title' => $schedule->title,
                        'description' => $schedule->description,
                        'label' => "Maintenance {$schedule->title}",
                    ]);
                }
                $cursor->addDay();
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function readMetadata($spreadsheet): array
    {
        $metadata = [];
        $specs = [
            'Audi' => ['event' => 2, 'pic' => 3, 'phone' => 4, 'attendees' => null, 'category' => null],
            'R-1' => ['event' => 3, 'pic' => 4, 'phone' => 5, 'attendees' => 8, 'category' => 2],
            'R-2' => ['event' => 3, 'pic' => 4, 'phone' => 5, 'attendees' => 8, 'category' => 2],
            'R-2-tp form ' => ['event' => 3, 'pic' => 4, 'phone' => 5, 'attendees' => 8, 'category' => 2],
        ];

        foreach ($specs as $sheetName => $spec) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                continue;
            }

            $last = ['event' => null, 'pic' => null, 'phone' => null, 'attendees' => null, 'category' => null];
            for ($row = 7; $row <= $sheet->getHighestDataRow(); $row++) {
                foreach (['event', 'pic', 'phone', 'attendees', 'category'] as $field) {
                    $column = $spec[$field];
                    if (! $column) {
                        continue;
                    }
                    $value = $sheet->getCell([$column, $row])->getFormattedValue();
                    if ($value !== null && trim((string) $value) !== '') {
                        $last[$field] = $this->cleanText($value);
                    }
                }

                if (! $last['event']) {
                    continue;
                }

                $attendees = is_numeric(str_replace(',', '.', (string) $last['attendees']))
                    ? (int) $last['attendees']
                    : null;
                $metadata[] = [
                    'normalized' => $this->normalize($last['event']),
                    'event' => $last['event'],
                    'pic' => $last['pic'],
                    'phone' => $this->normalizePhone($last['phone']),
                    'attendees' => $attendees,
                    'category' => $last['category'],
                ];
            }
        }

        usort($metadata, fn (array $a, array $b) => strlen($b['normalized']) <=> strlen($a['normalized']));

        return $metadata;
    }

    /** @return array<string, mixed> */
    private function findMetadata(string $event): array
    {
        $needle = $this->normalize($event);
        foreach ($this->metadata as $meta) {
            if ($needle === $meta['normalized']) {
                return $meta;
            }
        }

        foreach ($this->metadata as $meta) {
            if (strlen($meta['normalized']) < 8) {
                continue;
            }
            if (str_contains($needle, $meta['normalized']) || str_contains($meta['normalized'], $needle)) {
                return $meta;
            }
        }

        return [];
    }

    /** @return array<int, string> */
    private function mapRooms(string $source): array
    {
        $key = $this->normalize($source);
        $map = [
            'lobby' => ['Selasar Lobby'],
            'lobby gkp' => ['Selasar Lobby'],
            'selasar lobby' => ['Selasar Lobby'],
            'auditorium' => ['Auditorium'],
            'audiorium' => ['Auditorium'],
            'audittorium' => ['Auditorium'],
            '201' => ['201'],
            '202' => ['202'],
            '203 204' => ['203-204'],
            '203 203' => ['203-204'],
            '205 206' => ['205-206'],
            '205 205' => ['205-206'],
            '301 302 303' => ['301-302-303'],
            '301 303' => ['301-302-303'],
            '301 302' => ['301-302-303'],
            '304 305 306' => ['304-305-306'],
            '307' => ['307'],
            '308' => ['308'],
            '309' => ['309'],
            '310' => ['310'],
            '311' => ['311'],
            '312' => ['312'],
            '501' => ['501'],
            '307 308' => ['307', '308'],
            '309 312' => ['309', '312'],
            '307 308 309' => ['307', '308', '309'],
            '307 308 309 3011' => ['307', '308', '309', '311'],
            '205 205 501' => ['205-206', '501'],
            '205 206 501' => ['205-206', '501'],
            '203 204 205 206' => ['203-204', '205-206'],
            '203 204 205 205' => ['203-204', '205-206'],
            '203 204 202' => ['203-204', '202'],
            '202 203 204' => ['202', '203-204'],
            '304 305 306 307' => ['304-305-306', '307'],
        ];

        return $map[$key] ?? [];
    }

    /** @return array{start:string,end:string}|null */
    private function parseTimes(string $rawStart, string $rawEnd): ?array
    {
        $startLower = mb_strtolower(trim($rawStart));
        $endLower = mb_strtolower(trim($rawEnd));
        if (str_contains($startLower, '24 jam') || str_contains($endLower, '24 jam')) {
            return ['start' => '00:00', 'end' => '23:59'];
        }
        if ($endLower === 'selesai' || str_contains($endLower, 'selesai')) {
            return null;
        }

        $start = $this->normalizeTime($rawStart);
        $end = $this->normalizeTime($rawEnd);
        if (! $start || ! $end || $start >= $end) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    private function normalizeTime(string $value): ?string
    {
        $value = trim(str_replace([',', '.'], ':', $value));
        if (preg_match('/^\d{3,4}$/', $value)) {
            $value = str_pad($value, 4, '0', STR_PAD_LEFT);
            $value = substr($value, 0, 2).':'.substr($value, 2, 2);
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $match)) {
            $hour = (int) $match[1];
            $minute = (int) $match[2];
            if ($hour <= 23 && $minute <= 59) {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        }

        return null;
    }

    private function excelDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }
        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function resolveRoomId(string $roomName): ?string
    {
        $aliases = match ($roomName) {
            'Selasar Lobby' => ['Selasar Lobby', 'Lobby'],
            'Auditorium' => ['Auditorium', '401 / Auditorium', '401 Auditorium'],
            default => [$roomName],
        };

        foreach ($aliases as $alias) {
            $id = $this->roomIds[$this->normalize($alias)] ?? null;
            if ($id) {
                return $id;
            }
        }

        return null;
    }

    private function findOccupancyConflict(array $occurrence): ?array
    {
        foreach ($this->occupancy[$occurrence['room_id'].'|'.$occurrence['date']] ?? [] as $slot) {
            if ($this->overlaps($occurrence['start'], $occurrence['end'], $slot['start'], $slot['end'])) {
                return $slot;
            }
        }

        return null;
    }

    private function overlaps(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $aStart < $bEnd && $bStart < $aEnd;
    }

    private function markOccupied(array $slot): void
    {
        $this->occupancy[$slot['room_id'].'|'.$slot['date']][] = $slot;
    }

    private function isSameEvent(array $occurrence, array $slot): bool
    {
        if ($occurrence['start'] !== $slot['start'] || $occurrence['end'] !== $slot['end']) {
            return false;
        }
        $event = $this->normalize($occurrence['event']);
        $candidate = $this->normalize(trim(($slot['title'] ?? '').' '.($slot['description'] ?? '').' '.($slot['event'] ?? '')));

        return strlen($event) >= 4 && str_contains($candidate, $event);
    }

    private function buildDescription(array $occurrence): string
    {
        $parts = [$occurrence['event']];
        if ($occurrence['category']) {
            $parts[] = 'Kategori: '.$occurrence['category'];
        }
        $parts[] = "Diimpor dari GKP.xlsx ({$occurrence['sheet']} baris {$occurrence['row']}).";

        return implode(' — ', $parts);
    }

    private function addReport(array $row, string $code, string $reason, ?string $conflictWith = null): void
    {
        $this->reportRows[] = [
            'code' => $code,
            'reason' => $reason,
            'sheet' => $row['sheet'] ?? '',
            'row' => $row['row'] ?? '',
            'date' => $row['date'] ?? '',
            'source_room' => $row['source_room'] ?? '',
            'mapped_room' => $row['room'] ?? '',
            'start' => $row['start'] ?? '',
            'end' => $row['end'] ?? '',
            'event' => $row['event'] ?? '',
            'conflict_with' => $conflictWith ?? '',
        ];
    }

    private function writeReport(string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $handle = fopen($path, 'wb');
        if (! $handle) {
            $this->warn("Laporan tidak dapat ditulis: {$path}");

            return;
        }
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['kode', 'alasan', 'sheet', 'baris', 'tanggal', 'ruangan_sumber', 'ruangan_mapping', 'mulai', 'selesai', 'kegiatan', 'bentrok_dengan']);
        foreach ($this->reportRows as $row) {
            fputcsv($handle, array_values($row));
        }
        fclose($handle);
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if ($phone === null || trim((string) $phone) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (! $digits) {
            return null;
        }
        if (str_starts_with($digits, '8')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    private function cleanText(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function normalize(mixed $value): string
    {
        $text = mb_strtolower($this->cleanText($value));
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
