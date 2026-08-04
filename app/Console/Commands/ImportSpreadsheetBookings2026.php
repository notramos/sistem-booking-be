<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Impor sekali-pakai data "Data Peminjaman Ruang Aug-Des 2026.xlsx" dari
 * sekretariat. HANYA baris yang datanya lengkap (kegiatan, tanggal/pola,
 * jam, ruangan valid) yang dimasukkan — baris yang datanya rusak/tidak
 * lengkap di sumbernya sengaja tidak ditebak, harus ditambahkan manual
 * lewat aplikasi kalau memang diperlukan.
 *
 * Ruangan gabungan di Excel (mis. "203-204") dipecah jadi satu booking per
 * ruangan fisik, karena keduanya sama-sama terpakai fisik pada waktu yang
 * sama. Seri "Rutin" yang rentangnya >3 bulan dipecah jadi beberapa seri
 * berturut-turut supaya tetap sesuai batas booking rutin yang berlaku.
 *
 * Default dry-run (cuma menampilkan rencana). Jalankan dengan --commit untuk
 * benar-benar menyimpan ke database.
 */
class ImportSpreadsheetBookings2026 extends Command
{
    protected $signature = 'import:spreadsheet-bookings-2026 {--commit : Benar-benar simpan ke database, bukan cuma pratinjau}';

    protected $description = 'Impor data booking dari Data Peminjaman Ruang Aug-Des 2026.xlsx (sekali pakai)';

    /** @var array<string, string> Nama ruangan (persis seperti di Excel) => id ruangan. */
    private array $roomIds = [];

    /** @var array<string, list<array{start:string,end:string}>> "room_id|date" => daftar jam terpakai. */
    private array $occupancy = [];

    public function handle(): int
    {
        $isDryRun = ! $this->option('commit');
        $this->info($isDryRun ? '=== MODE DRY-RUN (tidak menyimpan apa pun) ===' : '=== MODE COMMIT (akan menyimpan ke database) ===');

        $owner = User::where('email', 'sekretariat@ealbertus.org')->first();
        if (! $owner) {
            $this->error('Akun sekretariat@ealbertus.org tidak ditemukan — jalankan AdminUserSeeder dulu.');

            return self::FAILURE;
        }

        foreach (Room::pluck('id', 'name') as $name => $id) {
            $this->roomIds[$name] = $id;
        }

        $this->loadExistingOccupancy();

        $singles = $this->singles();
        $rutinSeries = $this->rutinSeries();

        $created = 0;
        $skippedConflict = 0;
        $droppedOccurrences = 0;

        DB::beginTransaction();

        try {
            foreach ($singles as $row) {
                $roomId = $this->roomIds[$row['room']] ?? null;
                if (! $roomId) {
                    $this->warn("Ruangan '{$row['room']}' tidak ditemukan di sistem — dilewati: {$row['kegiatan']} ({$row['tanggal']})");
                    continue;
                }

                if ($this->hasConflict($roomId, $row['tanggal'], $row['start'], $row['end'])) {
                    $this->warn("BENTROK, dilewati: {$row['kegiatan']} | {$row['room']} | {$row['tanggal']} {$row['start']}-{$row['end']}");
                    $skippedConflict++;
                    continue;
                }

                $this->markOccupied($roomId, $row['tanggal'], $row['start'], $row['end']);

                $description = $this->buildDescription($row['kegiatan'], $row['kategori'], $row['jumlah']);

                if (! $isDryRun) {
                    Booking::create([
                        'user_id' => $owner->id,
                        'room_id' => $roomId,
                        'title' => $row['pic'] ?? $row['kegiatan'],
                        'description' => $description,
                        'booking_date' => $row['tanggal'],
                        'start_time' => $row['start'],
                        'end_time' => $row['end'],
                        'contact_person' => $row['hp'],
                        'expected_attendees' => is_numeric($row['jumlah']) ? (int) $row['jumlah'] : null,
                        'status' => 'approved',
                        'booking_type' => 'reguler',
                    ]);
                }
                $created++;
            }

            foreach ($rutinSeries as $row) {
                $roomId = $this->roomIds[$row['room']] ?? null;
                if (! $roomId) {
                    $this->warn("Ruangan '{$row['room']}' tidak ditemukan di sistem — dilewati: {$row['kegiatan']}");
                    continue;
                }

                foreach ($this->splitIntoChunks($row['firstMonth'], $row['lastMonth'], $row['year']) as $chunk) {
                    $dates = $this->weeklyDatesInRange($row['dow'], $chunk['start'], $chunk['end']);
                    $keptDates = [];

                    foreach ($dates as $date) {
                        if ($this->hasConflict($roomId, $date, $row['start'], $row['end'])) {
                            $droppedOccurrences++;
                            continue;
                        }
                        $this->markOccupied($roomId, $date, $row['start'], $row['end']);
                        $keptDates[] = $date;
                    }

                    if (count($keptDates) === 0) {
                        $this->warn("Semua tanggal bentrok, seri dilewati: {$row['kegiatan']} | {$row['room']} | {$chunk['start']}..{$chunk['end']}");
                        continue;
                    }

                    $description = $this->buildDescription($row['kegiatan'], $row['kategori'], $row['jumlah']);

                    if (! $isDryRun) {
                        Booking::create([
                            'user_id' => $owner->id,
                            'room_id' => $roomId,
                            'title' => $row['pic'] ?? $row['kegiatan'],
                            'description' => $description,
                            'booking_date' => $keptDates[0],
                            'start_time' => $row['start'],
                            'end_time' => $row['end'],
                            'contact_person' => $row['hp'],
                            'expected_attendees' => is_numeric($row['jumlah']) ? (int) $row['jumlah'] : null,
                            'status' => 'approved',
                            'booking_type' => 'rutin',
                            'recurring_pattern' => 'weekly',
                            'recurring_dates' => $keptDates,
                        ]);
                    }
                    $created++;
                    $this->line("  seri: {$row['kegiatan']} | {$row['room']} | ".count($keptDates)." tanggal ({$keptDates[0]} s/d ".end($keptDates).')');
                }
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

        $this->newLine();
        $this->info("Booking yang akan/telah dibuat: {$created}");
        $this->info("Entri single dilewati karena bentrok: {$skippedConflict}");
        $this->info("Kemunculan rutin dilewati karena bentrok: {$droppedOccurrences}");

        if ($isDryRun) {
            $this->newLine();
            $this->comment('Ini baru pratinjau. Jalankan ulang dengan --commit untuk benar-benar menyimpan.');
        }

        return self::SUCCESS;
    }

    private function buildDescription(string $kegiatan, ?string $kategori, ?string $jumlah): string
    {
        $parts = [$kegiatan];
        if ($kategori) {
            $parts[] = "Kategori: {$kategori}";
        }
        if ($jumlah) {
            $parts[] = "Perkiraan peserta: {$jumlah}";
        }
        $parts[] = 'Diimpor dari data sekretariat (Agu-Des 2026).';

        return implode(' — ', $parts);
    }

    /** Muat semua booking non-final/approved tahun 2026 sebagai peta jam terpakai. */
    private function loadExistingOccupancy(): void
    {
        $bookings = Booking::whereIn('status', ['pending', 'sekretariat_review', 'admin_review', 'approved'])
            ->whereYear('booking_date', 2026)
            ->get(['room_id', 'booking_date', 'start_time', 'end_time', 'recurring_dates']);

        foreach ($bookings as $b) {
            $dates = $b->recurring_dates ?: [$b->booking_date->toDateString()];
            foreach ($dates as $date) {
                $this->markOccupied($b->room_id, $date, substr($b->start_time, 0, 5), substr($b->end_time, 0, 5));
            }
        }
    }

    private function markOccupied(string $roomId, string $date, string $start, string $end): void
    {
        $this->occupancy["{$roomId}|{$date}"][] = ['start' => $start, 'end' => $end];
    }

    private function hasConflict(string $roomId, string $date, string $start, string $end): bool
    {
        foreach ($this->occupancy["{$roomId}|{$date}"] ?? [] as $slot) {
            if ($start < $slot['end'] && $slot['start'] < $end) {
                return true;
            }
        }

        return false;
    }

    /** Pecah rentang bulan jadi beberapa seri berturut-turut, masing-masing maksimal 3 bulan. */
    private function splitIntoChunks(int $firstMonth, int $lastMonth, int $year): array
    {
        $chunks = [];
        $month = $firstMonth;
        while ($month <= $lastMonth) {
            $chunkEndMonth = min($month + 2, $lastMonth);
            $chunks[] = [
                'start' => Carbon::create($year, $month, 1)->toDateString(),
                'end' => Carbon::create($year, $chunkEndMonth, 1)->endOfMonth()->toDateString(),
            ];
            $month = $chunkEndMonth + 1;
        }

        return $chunks;
    }

    /** Semua tanggal dengan hari-dalam-minggu $dow (0=Minggu..6=Sabtu) di antara $start dan $end (inklusif). */
    private function weeklyDatesInRange(int $dow, string $start, string $end): array
    {
        $cursor = Carbon::parse($start);
        while ((int) $cursor->dayOfWeek !== $dow) {
            $cursor->addDay();
        }

        $endDate = Carbon::parse($end);
        $dates = [];
        while ($cursor->lte($endDate)) {
            $dates[] = $cursor->toDateString();
            $cursor = $cursor->copy()->addDays(7);
        }

        return $dates;
    }

    /** @return list<array{kategori:?string,kegiatan:string,pic:?string,hp:?string,tanggal:string,start:string,end:string,room:string,jumlah:?string}> */
    private function singles(): array
    {
        $singles = [
            ['kategori' => null, 'kegiatan' => 'Wisuda STI Betherda Bekasi', 'pic' => 'Joyman Waruwu', 'hp' => '81218075938', 'tanggal' => '2026-08-15', 'start' => '09:00', 'end' => '13:00', 'room' => '401 / Auditorium', 'jumlah' => null],
            ['kategori' => null, 'kegiatan' => 'Peringatan St Pelindung Wilayah St. Agustinus', 'pic' => 'Albertus Suharis', 'hp' => '81316356523', 'tanggal' => '2026-09-05', 'start' => '09:00', 'end' => '15:00', 'room' => '401 / Auditorium', 'jumlah' => null],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-08-08', 'start' => '07:00', 'end' => '12:00', 'room' => '203', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-08-08', 'start' => '07:00', 'end' => '12:00', 'room' => '204', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-08-08', 'start' => '07:00', 'end' => '12:00', 'room' => '205', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-08-08', 'start' => '07:00', 'end' => '12:00', 'room' => '206', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-09-12', 'start' => '07:00', 'end' => '12:00', 'room' => '203', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-09-12', 'start' => '07:00', 'end' => '12:00', 'room' => '204', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-09-12', 'start' => '07:00', 'end' => '12:00', 'room' => '205', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-09-12', 'start' => '07:00', 'end' => '12:00', 'room' => '206', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-10-10', 'start' => '07:00', 'end' => '12:00', 'room' => '203', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-10-10', 'start' => '07:00', 'end' => '12:00', 'room' => '204', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-10-10', 'start' => '07:00', 'end' => '12:00', 'room' => '205', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-10-10', 'start' => '07:00', 'end' => '12:00', 'room' => '206', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-11-14', 'start' => '07:00', 'end' => '12:00', 'room' => '203', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-11-14', 'start' => '07:00', 'end' => '12:00', 'room' => '204', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-11-14', 'start' => '07:00', 'end' => '12:00', 'room' => '205', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-11-14', 'start' => '07:00', 'end' => '12:00', 'room' => '206', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-12-12', 'start' => '07:00', 'end' => '12:00', 'room' => '203', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-12-12', 'start' => '07:00', 'end' => '12:00', 'room' => '204', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-12-12', 'start' => '07:00', 'end' => '12:00', 'room' => '205', 'jumlah' => '100'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'Pos Yandu Adi Yuswo', 'pic' => 'Sianny', 'hp' => '81383732959', 'tanggal' => '2026-12-12', 'start' => '07:00', 'end' => '12:00', 'room' => '206', 'jumlah' => '100'],
            ['kategori' => 'SKK', 'kegiatan' => 'Discovery', 'pic' => 'Varinia', 'hp' => '81911356969', 'tanggal' => '2026-08-22', 'start' => '08:00', 'end' => '15:00', 'room' => '202', 'jumlah' => '20'],
            ['kategori' => 'AAV', 'kegiatan' => 'Latihan utk lomba PICF', 'pic' => 'uchy', 'hp' => '8119929529', 'tanggal' => '2026-08-25', 'start' => '10:00', 'end' => '14:00', 'room' => '304', 'jumlah' => '60'],
            ['kategori' => 'AAV', 'kegiatan' => 'Latihan utk lomba PICF', 'pic' => 'uchy', 'hp' => '8119929529', 'tanggal' => '2026-08-25', 'start' => '10:00', 'end' => '14:00', 'room' => '306', 'jumlah' => '60'],
            ['kategori' => 'AADC', 'kegiatan' => 'BIA', 'pic' => 'Michelle', 'hp' => '8163600098', 'tanggal' => '2026-08-16', 'start' => '10:00', 'end' => '11:00', 'room' => '309', 'jumlah' => '20'],
            ['kategori' => 'Sie Kerasulan keluarga', 'kegiatan' => 'Misa syukur HUT FKPK ke 28', 'pic' => 'Arianthy Diana K', 'hp' => '8128638718', 'tanggal' => '2026-08-29', 'start' => '10:00', 'end' => '12:00', 'room' => '203', 'jumlah' => '150'],
            ['kategori' => 'Sie Kerasulan keluarga', 'kegiatan' => 'Misa syukur HUT FKPK ke 28', 'pic' => 'Arianthy Diana K', 'hp' => '8128638718', 'tanggal' => '2026-08-29', 'start' => '10:00', 'end' => '12:00', 'room' => '204', 'jumlah' => '150'],
            ['kategori' => 'KTM', 'kegiatan' => 'Pembinaan Pengajaran', 'pic' => 'Ailly / Acai', 'hp' => '83898549462', 'tanggal' => '2026-08-22', 'start' => '08:00', 'end' => '12:00', 'room' => '301', 'jumlah' => '30'],
            ['kategori' => 'KTM', 'kegiatan' => 'Pembinaan Pengajaran', 'pic' => 'Ailly / Acai', 'hp' => '83898549462', 'tanggal' => '2026-08-22', 'start' => '08:00', 'end' => '12:00', 'room' => '302', 'jumlah' => '30'],
            ['kategori' => 'KTM', 'kegiatan' => 'Pembinaan Pengajaran', 'pic' => 'Ailly / Acai', 'hp' => '83898549462', 'tanggal' => '2026-08-22', 'start' => '08:00', 'end' => '12:00', 'room' => '303', 'jumlah' => '30'],
            ['kategori' => 'WKRI FX', 'kegiatan' => 'Team building', 'pic' => 'Dharyanti Kartono', 'hp' => '85710020703', 'tanggal' => '2026-11-21', 'start' => '09:00', 'end' => '14:00', 'room' => '205', 'jumlah' => '50'],
            ['kategori' => 'WKRI FX', 'kegiatan' => 'Team building', 'pic' => 'Dharyanti Kartono', 'hp' => '85710020703', 'tanggal' => '2026-11-21', 'start' => '09:00', 'end' => '14:00', 'room' => '206', 'jumlah' => '50'],
            ['kategori' => 'Misdinar', 'kegiatan' => 'Pendaftaran Misdinar', 'pic' => 'Bunga Claudia', 'hp' => '81314841414', 'tanggal' => '2026-07-18', 'start' => '18:00', 'end' => '19:30', 'room' => 'Lobby', 'jumlah' => '100'],
            ['kategori' => 'Misdinar', 'kegiatan' => 'Pendaftaran Misdinar', 'pic' => 'Bunga Claudia', 'hp' => '81314841414', 'tanggal' => '2026-07-19', 'start' => '09:00', 'end' => '11:00', 'room' => 'Lobby', 'jumlah' => '100'],
            ['kategori' => 'Misdinar', 'kegiatan' => 'Pendaftaran Misdinar', 'pic' => 'Bunga Claudia', 'hp' => '81314841414', 'tanggal' => '2026-07-25', 'start' => '18:00', 'end' => '19:30', 'room' => 'Lobby', 'jumlah' => '100'],
            ['kategori' => 'Misdinar', 'kegiatan' => 'Pendaftaran Misdinar', 'pic' => 'Bunga Claudia', 'hp' => '81314841414', 'tanggal' => '2026-07-26', 'start' => '09:00', 'end' => '11:00', 'room' => 'Lobby', 'jumlah' => '100'],
            ['kategori' => 'Misdinar', 'kegiatan' => 'Pendaftaran Misdinar', 'pic' => 'Bunga Claudia', 'hp' => '81314841414', 'tanggal' => '2026-08-02', 'start' => '09:00', 'end' => '11:00', 'room' => 'Lobby', 'jumlah' => '100'],
            ['kategori' => 'Misdinar', 'kegiatan' => 'Pendaftaran Misdinar', 'pic' => 'Bunga Claudia', 'hp' => '81314841414', 'tanggal' => '2026-08-08', 'start' => '18:00', 'end' => '19:30', 'room' => 'Lobby', 'jumlah' => '100'],
            ['kategori' => 'Misdinar', 'kegiatan' => 'Pendaftaran Misdinar', 'pic' => 'Bunga Claudia', 'hp' => '81314841414', 'tanggal' => '2026-08-09', 'start' => '09:00', 'end' => '11:00', 'room' => 'Lobby', 'jumlah' => '100'],
            ['kategori' => 'Prodiakon', 'kegiatan' => 'Rapat pengurus', 'pic' => 'Handhi', 'hp' => '8128060911', 'tanggal' => '2026-08-02', 'start' => '12:00', 'end' => '15:00', 'room' => '205', 'jumlah' => '25'],
            ['kategori' => 'Prodiakon', 'kegiatan' => 'Rapat pengurus', 'pic' => 'Handhi', 'hp' => '8128060911', 'tanggal' => '2026-08-02', 'start' => '12:00', 'end' => '15:00', 'room' => '206', 'jumlah' => '25'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'GB HUT FKPK', 'pic' => 'dr, Inggrid', 'hp' => '8561087212', 'tanggal' => '2026-08-28', 'start' => '15:00', 'end' => '18:00', 'room' => '203', 'jumlah' => '15'],
            ['kategori' => 'Kesehatan', 'kegiatan' => 'GB HUT FKPK', 'pic' => 'dr, Inggrid', 'hp' => '8561087212', 'tanggal' => '2026-08-28', 'start' => '15:00', 'end' => '18:00', 'room' => '204', 'jumlah' => '15'],
            ['kategori' => 'KTM', 'kegiatan' => 'Misa HUT KTM ke-19', 'pic' => 'Ailly / Acai', 'hp' => '83898549462', 'tanggal' => '2026-08-19', 'start' => '10:00', 'end' => '13:00', 'room' => '301', 'jumlah' => '50'],
            ['kategori' => 'KTM', 'kegiatan' => 'Misa HUT KTM ke-19', 'pic' => 'Ailly / Acai', 'hp' => '83898549462', 'tanggal' => '2026-08-19', 'start' => '10:00', 'end' => '13:00', 'room' => '302', 'jumlah' => '50'],
            ['kategori' => 'KTM', 'kegiatan' => 'Misa HUT KTM ke-19', 'pic' => 'Ailly / Acai', 'hp' => '83898549462', 'tanggal' => '2026-08-19', 'start' => '10:00', 'end' => '13:00', 'room' => '303', 'jumlah' => '50'],
            ['kategori' => 'Katekese', 'kegiatan' => 'Misa KMKS dengan Romo Daniel', 'pic' => 'Iin Maria', 'hp' => '82237970172', 'tanggal' => '2026-08-14', 'start' => '10:00', 'end' => '12:30', 'room' => '301', 'jumlah' => '40'],
            ['kategori' => 'Katekese', 'kegiatan' => 'Misa KMKS dengan Romo Daniel', 'pic' => 'Iin Maria', 'hp' => '82237970172', 'tanggal' => '2026-08-14', 'start' => '10:00', 'end' => '12:30', 'room' => '302', 'jumlah' => '40'],
            ['kategori' => 'Katekese', 'kegiatan' => 'Misa KMKS dengan Romo Daniel', 'pic' => 'Iin Maria', 'hp' => '82237970172', 'tanggal' => '2026-08-14', 'start' => '10:00', 'end' => '12:30', 'room' => '303', 'jumlah' => '40'],
            ['kategori' => 'KTM muda-mudi', 'kegiatan' => 'Sel Doa', 'pic' => 'Gracia Felicita', 'hp' => '87819960635', 'tanggal' => '2026-08-02', 'start' => '13:00', 'end' => '16:00', 'room' => '311', 'jumlah' => '10'],
            ['kategori' => 'WKRI', 'kegiatan' => 'Seminar Digital Family', 'pic' => 'Ibu Ade Lie', 'hp' => '87820728811', 'tanggal' => '2026-09-26', 'start' => '10:00', 'end' => '12:00', 'room' => '203', 'jumlah' => '200'],
            ['kategori' => 'WKRI', 'kegiatan' => 'Seminar Digital Family', 'pic' => 'Ibu Ade Lie', 'hp' => '87820728811', 'tanggal' => '2026-09-26', 'start' => '10:00', 'end' => '12:00', 'room' => '204', 'jumlah' => '200'],
            ['kategori' => 'ASAK', 'kegiatan' => 'Pertemuan OTA ASAK', 'pic' => 'Adhi Purwoko', 'hp' => '8159911520', 'tanggal' => '2026-09-19', 'start' => '10:00', 'end' => '13:00', 'room' => '304', 'jumlah' => '75'],
            ['kategori' => 'ASAK', 'kegiatan' => 'Pertemuan OTA ASAK', 'pic' => 'Adhi Purwoko', 'hp' => '8159911520', 'tanggal' => '2026-09-19', 'start' => '10:00', 'end' => '13:00', 'room' => '305', 'jumlah' => '75'],
            ['kategori' => 'ASAK', 'kegiatan' => 'Pertemuan OTA ASAK', 'pic' => 'Adhi Purwoko', 'hp' => '8159911520', 'tanggal' => '2026-09-19', 'start' => '10:00', 'end' => '13:00', 'room' => '306', 'jumlah' => '75'],
            ['kategori' => 'PDOMPKK umendei', 'kegiatan' => 'PD Tim Lumendei', 'pic' => 'Lauwrensis', 'hp' => '895375283429', 'tanggal' => '2026-08-02', 'start' => '13:00', 'end' => '17:00', 'room' => '301', 'jumlah' => '30'],
            ['kategori' => 'PDOMPKK umendei', 'kegiatan' => 'PD Tim Lumendei', 'pic' => 'Lauwrensis', 'hp' => '895375283429', 'tanggal' => '2026-08-02', 'start' => '13:00', 'end' => '17:00', 'room' => '302', 'jumlah' => '30'],
            ['kategori' => 'PDOMPKK umendei', 'kegiatan' => 'PD Tim Lumendei', 'pic' => 'Lauwrensis', 'hp' => '895375283429', 'tanggal' => '2026-08-02', 'start' => '13:00', 'end' => '17:00', 'room' => '303', 'jumlah' => '30'],
        ];

        return $singles;
    }

    /** @return list<array{kategori:?string,kegiatan:string,pic:?string,hp:?string,dow:int,firstMonth:int,lastMonth:int,year:int,start:string,end:string,room:string,jumlah:?string}> */
    private function rutinSeries(): array
    {
        $rutinSeries = [
            ['kategori' => 'Legio Maria', 'kegiatan' => 'Rapat Legio', 'pic' => 'Lisa', 'hp' => '818866007', 'dow' => 4, 'firstMonth' => 1, 'lastMonth' => 12, 'year' => 2026, 'start' => '09:00', 'end' => '11:00', 'room' => '205', 'jumlah' => '12'],
            ['kategori' => 'Legio Maria', 'kegiatan' => 'Rapat Legio', 'pic' => 'Lisa', 'hp' => '818866007', 'dow' => 4, 'firstMonth' => 1, 'lastMonth' => 12, 'year' => 2026, 'start' => '09:00', 'end' => '11:00', 'room' => '206', 'jumlah' => '12'],
            ['kategori' => 'Katekese', 'kegiatan' => 'Cathclass', 'pic' => 'Theo Hari', 'hp' => '85959001970', 'dow' => 3, 'firstMonth' => 4, 'lastMonth' => 10, 'year' => 2026, 'start' => '19:00', 'end' => '21:00', 'room' => '301', 'jumlah' => '50'],
            ['kategori' => 'Katekese', 'kegiatan' => 'Cathclass', 'pic' => 'Theo Hari', 'hp' => '85959001970', 'dow' => 3, 'firstMonth' => 4, 'lastMonth' => 10, 'year' => 2026, 'start' => '19:00', 'end' => '21:00', 'room' => '302', 'jumlah' => '50'],
            ['kategori' => 'Katekese', 'kegiatan' => 'Cathclass', 'pic' => 'Theo Hari', 'hp' => '85959001970', 'dow' => 3, 'firstMonth' => 4, 'lastMonth' => 10, 'year' => 2026, 'start' => '19:00', 'end' => '21:00', 'room' => '303', 'jumlah' => '50'],
            ['kategori' => 'AAV', 'kegiatan' => 'pelatihan dirigen AAV', 'pic' => 'Ivana Laura', 'hp' => '81297427280', 'dow' => 6, 'firstMonth' => 5, 'lastMonth' => 9, 'year' => 2026, 'start' => '12:00', 'end' => '14:00', 'room' => '308', 'jumlah' => '15'],
            ['kategori' => 'Link Anastasia 3', 'kegiatan' => 'Persekutuan doa rutin', 'pic' => 'Lauwrensia', 'hp' => '895375283429', 'dow' => 5, 'firstMonth' => 7, 'lastMonth' => 12, 'year' => 2026, 'start' => '18:00', 'end' => '22:00', 'room' => '205', 'jumlah' => '40'],
            ['kategori' => 'Link Anastasia 3', 'kegiatan' => 'Persekutuan doa rutin', 'pic' => 'Lauwrensia', 'hp' => '895375283429', 'dow' => 5, 'firstMonth' => 7, 'lastMonth' => 12, 'year' => 2026, 'start' => '18:00', 'end' => '22:00', 'room' => '206', 'jumlah' => '40'],
            ['kategori' => 'Koor Albertus Magnus', 'kegiatan' => 'Latihan koor rutin', 'pic' => 'Prita', 'hp' => '85921514218', 'dow' => 6, 'firstMonth' => 7, 'lastMonth' => 7, 'year' => 2026, 'start' => '19:30', 'end' => '21:00', 'room' => '501', 'jumlah' => '25'],
            ['kategori' => 'Misdinar', 'kegiatan' => 'latihan paduan suara', 'pic' => 'Benedicta Felisia', 'hp' => '85692141321', 'dow' => 6, 'firstMonth' => 7, 'lastMonth' => 8, 'year' => 2026, 'start' => '19:00', 'end' => '21:00', 'room' => 'Lobby', 'jumlah' => '15'],
            ['kategori' => 'IKKSU', 'kegiatan' => 'Pertemuan rutin bulanan', 'pic' => 'Philippus Pandiangan', 'hp' => '81385887777', 'dow' => 5, 'firstMonth' => 7, 'lastMonth' => 8, 'year' => 2026, 'start' => '19:00', 'end' => '21:00', 'room' => '301', 'jumlah' => '40-70'],
            ['kategori' => 'IKKSU', 'kegiatan' => 'Pertemuan rutin bulanan', 'pic' => 'Philippus Pandiangan', 'hp' => '81385887777', 'dow' => 5, 'firstMonth' => 7, 'lastMonth' => 8, 'year' => 2026, 'start' => '19:00', 'end' => '21:00', 'room' => '302', 'jumlah' => '40-70'],
            ['kategori' => 'IKKSU', 'kegiatan' => 'Pertemuan rutin bulanan', 'pic' => 'Philippus Pandiangan', 'hp' => '81385887777', 'dow' => 5, 'firstMonth' => 7, 'lastMonth' => 8, 'year' => 2026, 'start' => '19:00', 'end' => '21:00', 'room' => '303', 'jumlah' => '40-70'],
            ['kategori' => 'PDPKK', 'kegiatan' => 'Persekuruan doa umum', 'pic' => 'Simon P', 'hp' => '81219154948', 'dow' => 1, 'firstMonth' => 7, 'lastMonth' => 9, 'year' => 2026, 'start' => '19:30', 'end' => '21:30', 'room' => '304', 'jumlah' => '60-90'],
            ['kategori' => 'PDPKK', 'kegiatan' => 'Persekuruan doa umum', 'pic' => 'Simon P', 'hp' => '81219154948', 'dow' => 1, 'firstMonth' => 7, 'lastMonth' => 9, 'year' => 2026, 'start' => '19:30', 'end' => '21:30', 'room' => '305', 'jumlah' => '60-90'],
            ['kategori' => 'PDPKK', 'kegiatan' => 'Persekuruan doa umum', 'pic' => 'Simon P', 'hp' => '81219154948', 'dow' => 1, 'firstMonth' => 7, 'lastMonth' => 9, 'year' => 2026, 'start' => '19:30', 'end' => '21:30', 'room' => '306', 'jumlah' => '60-90'],
            ['kategori' => 'PDPKK', 'kegiatan' => 'PD Tim', 'pic' => 'Simon P', 'hp' => '81219154948', 'dow' => 5, 'firstMonth' => 7, 'lastMonth' => 9, 'year' => 2026, 'start' => '19:30', 'end' => '21:30', 'room' => '301', 'jumlah' => '30-40'],
            ['kategori' => 'PDPKK', 'kegiatan' => 'PD Tim', 'pic' => 'Simon P', 'hp' => '81219154948', 'dow' => 5, 'firstMonth' => 7, 'lastMonth' => 9, 'year' => 2026, 'start' => '19:30', 'end' => '21:30', 'room' => '302', 'jumlah' => '30-40'],
            ['kategori' => 'PDPKK', 'kegiatan' => 'PD Tim', 'pic' => 'Simon P', 'hp' => '81219154948', 'dow' => 5, 'firstMonth' => 7, 'lastMonth' => 9, 'year' => 2026, 'start' => '19:30', 'end' => '21:30', 'room' => '303', 'jumlah' => '30-40'],
            ['kategori' => 'PDPKK', 'kegiatan' => 'Worshop/ ibadah KTM umum', 'pic' => 'Julius T', 'hp' => '88213960729', 'dow' => 0, 'firstMonth' => 8, 'lastMonth' => 10, 'year' => 2026, 'start' => '13:00', 'end' => '16:00', 'room' => '312', 'jumlah' => '20'],
            ['kategori' => 'AAV', 'kegiatan' => 'latihan rutin padus', 'pic' => 'Ivana Laura', 'hp' => '81297427280', 'dow' => 5, 'firstMonth' => 7, 'lastMonth' => 9, 'year' => 2026, 'start' => '18:30', 'end' => '21:00', 'room' => '501', 'jumlah' => '65'],
            ['kategori' => 'AAV', 'kegiatan' => 'latihan rutin padus', 'pic' => 'Ivana Laura', 'hp' => '81297427280', 'dow' => 6, 'firstMonth' => 8, 'lastMonth' => 9, 'year' => 2026, 'start' => '10:00', 'end' => '14:00', 'room' => '501', 'jumlah' => '65'],
            ['kategori' => 'Katekese', 'kegiatan' => 'KMKS (komunitas meditasi kitab suci)', 'pic' => 'Iin Maria', 'hp' => '82237970172', 'dow' => 5, 'firstMonth' => 8, 'lastMonth' => 10, 'year' => 2026, 'start' => '10:00', 'end' => '13:00', 'room' => '205', 'jumlah' => '35'],
            ['kategori' => 'Katekese', 'kegiatan' => 'KMKS (komunitas meditasi kitab suci)', 'pic' => 'Iin Maria', 'hp' => '82237970172', 'dow' => 5, 'firstMonth' => 8, 'lastMonth' => 10, 'year' => 2026, 'start' => '10:00', 'end' => '13:00', 'room' => '206', 'jumlah' => '35'],
            ['kategori' => 'ASAK', 'kegiatan' => 'Bimbel Mathematika ASAK', 'pic' => 'Adhi Purwoko', 'hp' => '8159911520', 'dow' => 0, 'firstMonth' => 8, 'lastMonth' => 10, 'year' => 2026, 'start' => '10:00', 'end' => '11:30', 'room' => '307', 'jumlah' => '15'],
        ];

        return $rutinSeries;
    }
}
