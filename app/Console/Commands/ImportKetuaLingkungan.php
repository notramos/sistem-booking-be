<?php

namespace App\Console\Commands;

use App\Models\Lingkungan;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportKetuaLingkungan extends Command
{
    protected $signature = 'users:import-ketua-lingkungan {file : Excel sumber} {--commit : Simpan akun setelah pemeriksaan}';

    protected $description = 'Pratinjau atau impor akun Ketua Lingkungan dari Excel';

    private const WILAYAH = [
        'Agustinus' => 'St. Agustinus', 'Alfonsus' => 'St. Alfonsus',
        'Aloysius' => 'St. Aloysius', 'Anastasia' => 'St. Anastasia',
        'Andreas' => 'St. Andreas', 'Arcadius' => 'St. Arcadius',
        'FransiskusAsisi' => 'St. Fr. Asisi', 'Felisitas' => 'St. Felisitas',
        'Helena' => 'St. Helena', 'FransiskusXaverius' => 'St. Fr. Xaverius',
        'IgnatiusLoyola' => 'St. Ign. Loyola', 'MariaFatima' => 'St. Maria Fatima',
        'Petrus' => 'St. Petrus', 'ReginaPacis' => 'St. Regina Pacis',
        'Theresia' => 'St. Theresia', 'Thomas' => 'St. Thomas',
        'Yohanes' => 'St. Yohanes',
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error('File Excel tidak ditemukan.');
            return self::FAILURE;
        }

        $sheet = IOFactory::load($file)->getSheet(0);
        $accounts = [];
        $errors = [];
        for ($row = 4; $row <= $sheet->getHighestRow(); $row++) {
            $number = trim((string) $sheet->getCell("B{$row}")->getValue());
            if ($number === '' || ! ctype_digit($number)) continue;

            $name = trim((string) $sheet->getCell("D{$row}")->getValue());
            $email = trim((string) $sheet->getCell("E{$row}")->getValue());
            $username = trim((string) $sheet->getCell("G{$row}")->getValue());
            $password = (string) $sheet->getCell("N{$row}")->getValue();

            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || $username === '' || strlen($password) < 8) {
                $errors[] = "Baris {$row}: nama, email, UserName, atau panjang password tidak valid.";
                continue;
            }
            if (! preg_match('/^([A-Za-z]+)_([0-9]+[AB]?)$/i', $username, $parts)) {
                $errors[] = "Baris {$row}: format UserName tidak dikenal ({$username}).";
                continue;
            }
            $prefix = collect(array_keys(self::WILAYAH))->first(fn ($key) => strcasecmp($key, $parts[1]) === 0);
            if ($prefix === null) {
                $errors[] = "Baris {$row}: wilayah UserName tidak dikenal ({$username}).";
                continue;
            }
            $wilayah = self::WILAYAH[$prefix];
            $nomor = strtoupper($parts[2]);
            $lingkungan = "{$wilayah} {$nomor}";
            $accounts[] = compact('row', 'name', 'email', 'username', 'password', 'wilayah', 'lingkungan');
        }

        if (count($accounts) !== 68) $errors[] = 'File harus memuat tepat 68 akun; terbaca '.count($accounts).'.';
        if (count(array_unique(array_map(fn ($a) => strtolower($a['email']), $accounts))) !== count($accounts)) $errors[] = 'Ada email ganda di Excel.';
        if (count(array_unique(array_map(fn ($a) => strtolower($a['username']), $accounts))) !== count($accounts)) $errors[] = 'Ada UserName ganda di Excel.';

        foreach ($accounts as $account) {
            if (User::where('email', $account['email'])->orWhereRaw('LOWER(username) = ?', [strtolower($account['username'])])->exists()) {
                $errors[] = "Baris {$account['row']}: email atau UserName sudah terdaftar ({$account['username']}).";
            }
            if ($account['wilayah'] === 'St. Helena') continue;
            $w = Wilayah::where('name', $account['wilayah'])->first();
            if (! $w) {
                $errors[] = "Baris {$account['row']}: wilayah {$account['wilayah']} tidak ditemukan.";
            } elseif (! $this->findLingkungan($w, $account['lingkungan'])) {
                $errors[] = "Baris {$account['row']}: lingkungan {$account['lingkungan']} tidak ditemukan.";
            }
        }

        $this->line('Akun: '.count($accounts).'; wilayah St. Helena akan dibuat jika belum ada.');
        foreach ($errors as $error) $this->error($error);
        if ($errors !== []) return self::FAILURE;
        if (! $this->option('commit')) {
            $this->info('Pratinjau lolos. Jalankan ulang dengan --commit untuk menyimpan 68 akun.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($accounts) {
            $helena = Wilayah::firstOrCreate(['name' => 'St. Helena'], ['is_active' => true]);
            foreach (range(1, 4) as $number) {
                Lingkungan::firstOrCreate(
                    ['wilayah_id' => $helena->id, 'name' => "St. Helena {$number}"],
                    ['is_active' => true]
                );
            }
            foreach ($accounts as $account) {
                $w = Wilayah::where('name', $account['wilayah'])->firstOrFail();
                $l = $this->findLingkungan($w, $account['lingkungan']);
                if (! $l) throw new \RuntimeException('Lingkungan tidak ditemukan: '.$account['lingkungan']);
                $user = User::create([
                    'name' => $account['name'], 'email' => $account['email'],
                    'username' => $account['username'], 'password' => $account['password'],
                    'wilayah_id' => $w->id, 'lingkungan_id' => $l->id,
                    'position' => 'Ketua Lingkungan', 'department' => 'Perangkat Karya',
                    'parish' => 'Paroki Harapan Indah', 'is_active' => true,
                ]);
                $user->assignRole('ketua_lingkungan');
            }
        });
        $this->info('68 akun Ketua Lingkungan berhasil disimpan.');
        return self::SUCCESS;
    }

    private function findLingkungan(Wilayah $wilayah, string $name): ?Lingkungan
    {
        $normalized = fn (string $value) => strtolower(preg_replace('/[^a-z0-9]/i', '', $value));
        return $wilayah->lingkungan->first(fn ($item) => $normalized($item->name) === $normalized($name));
    }
}
