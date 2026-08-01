<?php

namespace Database\Seeders;

use App\Models\Lingkungan;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;

class WilayahLingkunganSeeder extends Seeder
{
    /**
     * Data resmi wilayah & lingkungan paroki (16 wilayah, 65 lingkungan),
     * mengikuti berkas "Daftar Wilayah" dari sekretariat.
     *
     * `area` = nama perumahan/kompleks tempat lingkungan berada; ditampilkan
     * sebagai keterangan di dropdown karena daftar lingkungan disajikan datar
     * lintas wilayah, sehingga nama seperti "St. Alfonsus 2" saja sulit dikenali.
     *
     * Nama lingkungan dibuat unik karena sebagian form menyimpan nama (bukan id)
     * dan menampilkannya kembali di dokumen.
     *
     * @var array<string, list<array{name: string, area: string|null}>>
     */
    private array $data = [
        'PUP Barat' => [
            ['name' => 'St. Anastasia 1', 'area' => 'PUP Barat'],
            ['name' => 'St. Anastasia 2', 'area' => 'PUP Barat'],
            ['name' => 'St. Anastasia 3', 'area' => 'PUP Barat'],
            ['name' => 'St. Anastasia 4', 'area' => 'PUP Barat'],
        ],
        'PUP Timur' => [
            ['name' => 'St. Fr. Asisi 1', 'area' => 'PUP Timur'],
            ['name' => 'St. Fr. Asisi 2', 'area' => 'PUP Timur'],
            ['name' => 'St. Fr. Asisi 3', 'area' => 'PUP Timur'],
            ['name' => 'St. Fr. Asisi 4', 'area' => 'PUP Timur'],
            ['name' => 'St. Fr. Asisi 5', 'area' => 'PUP Timur'],
            ['name' => 'St. Fr. Asisi 6', 'area' => 'PUP Timur'],
        ],
        'THB Timur' => [
            ['name' => 'St. Petrus 1', 'area' => 'THB Timur'],
            ['name' => 'St. Petrus 2', 'area' => 'THB Timur'],
            ['name' => 'St. Petrus 3', 'area' => 'THB Timur'],
            ['name' => 'St. Petrus 4', 'area' => 'THB Timur'],
        ],
        'Tarumajaya Selatan' => [
            ['name' => 'St. Alfonsus 1', 'area' => 'Vila Mutiara Gading'],
            ['name' => 'St. Alfonsus 2', 'area' => 'Alindra'],
            ['name' => 'St. Alfonsus 3', 'area' => 'Puri Residence'],
        ],
        'THB Barat' => [
            ['name' => 'St. Aloysius 1', 'area' => 'THB Barat'],
            ['name' => 'St. Aloysius 2', 'area' => 'THB Barat'],
            ['name' => 'St. Aloysius 3', 'area' => 'THB Barat'],
            ['name' => 'St. Aloysius 4', 'area' => 'THB Barat'],
        ],
        'Harapan Indah Selatan' => [
            ['name' => 'St. Fr. Xaverius 1', 'area' => 'Harapan Indah Selatan'],
            ['name' => 'St. Fr. Xaverius 2', 'area' => 'Harapan Indah Selatan'],
            ['name' => 'St. Fr. Xaverius 3', 'area' => 'Harapan Indah Selatan'],
            ['name' => 'St. Fr. Xaverius 4', 'area' => 'Harapan Indah Selatan'],
        ],
        'Pejuang Jaya' => [
            ['name' => 'St. Regina Pacis 1', 'area' => 'Pejuang Jaya'],
            ['name' => 'St. Regina Pacis 2', 'area' => 'Pejuang Jaya'],
            ['name' => 'St. Regina Pacis 3', 'area' => 'Pejuang Jaya'],
        ],
        'Tarumajaya Utara' => [
            ['name' => 'St. Thomas 1', 'area' => 'Rakyat Setia'],
            ['name' => 'St. Thomas 2', 'area' => 'Segara Jaya'],
        ],
        'Bulevar Hijau' => [
            ['name' => 'St. Andreas 1', 'area' => 'Bulevar Hijau'],
            ['name' => 'St. Andreas 2', 'area' => 'Bulevar Hijau'],
            ['name' => 'St. Andreas 3', 'area' => 'Bulevar Hijau'],
            ['name' => 'St. Andreas 4', 'area' => 'Griya Harapan Permai'],
        ],
        'Harapan Indah 2' => [
            ['name' => 'St. Felisitas 1', 'area' => 'Tmn Sari & Asia Tropis'],
            ['name' => 'St. Felisitas 2', 'area' => 'Tmn Puspa'],
            ['name' => 'St. Felisitas 3', 'area' => 'Aralia'],
            ['name' => 'St. Felisitas 4', 'area' => 'Harmoni'],
            ['name' => 'St. Felisitas 5', 'area' => 'Ifolia'],
        ],
        'Harapan Indah Timur' => [
            ['name' => 'St. Theresia 1', 'area' => 'Harapan Indah Timur'],
            ['name' => 'St. Theresia 2', 'area' => 'Harapan Indah Timur'],
            ['name' => 'St. Theresia 3', 'area' => 'Harapan Indah Timur'],
            ['name' => 'St. Theresia 4', 'area' => 'Harapan Indah Timur'],
            ['name' => 'St. Theresia 5', 'area' => 'Harapan Indah Timur'],
        ],
        'Mutiara Gading City' => [
            ['name' => 'St. Maria Fatima 1', 'area' => 'MGC Babelan'],
            ['name' => 'St. Maria Fatima 2', 'area' => 'MGC Tarumajaya'],
            ['name' => 'St. Maria Fatima 3', 'area' => 'Green Ara'],
        ],
        'Harapan Indah Barat' => [
            ['name' => 'St. Arcadius 1', 'area' => 'Harapan Indah Barat'],
            ['name' => 'St. Arcadius 2', 'area' => 'Harapan Indah Barat'],
            ['name' => 'St. Arcadius 3', 'area' => 'Harapan Indah Barat'],
            ['name' => 'St. Arcadius 4', 'area' => 'Harapan Indah Barat'],
            ['name' => 'St. Arcadius 5', 'area' => 'Harapan Indah Barat'],
            ['name' => 'St. Arcadius 6', 'area' => 'Harapan Indah Barat'],
        ],
        'PUP Sektor 5' => [
            ['name' => 'St. Ign. Loyola 1', 'area' => 'PUP Sektor 5'],
            ['name' => 'St. Ign. Loyola 2', 'area' => 'PUP Sektor 5'],
            ['name' => 'St. Ign. Loyola 3', 'area' => 'PUP Sektor 5'],
        ],
        'Pejuang Kaliabang' => [
            ['name' => 'St. Yohanes 1', 'area' => 'Pejuang Pratama'],
            ['name' => 'St. Yohanes 2', 'area' => 'Pondok Sani'],
            ['name' => 'St. Yohanes 3', 'area' => 'Duta Bumi'],
            ['name' => 'St. Yohanes 4', 'area' => 'Permata Harapa Baru'],
        ],
        'Babelan' => [
            ['name' => 'St. Agustinus 1', 'area' => 'Kedaung'],
            ['name' => 'St. Agustinus 2', 'area' => 'Pulo Timaha'],
            ['name' => 'St. Agustinus 3', 'area' => 'Wahana'],
            ['name' => 'St. Agustinus 4', 'area' => 'Kedung Jaya'],
            ['name' => 'St. Agustinus 5', 'area' => 'Buni Bakti'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->data as $wilayahName => $lingkunganList) {
            $wilayah = Wilayah::firstOrCreate(['name' => $wilayahName], ['is_active' => true]);
            $wilayah->update(['is_active' => true]);

            foreach ($lingkunganList as $lingkungan) {
                Lingkungan::updateOrCreate(
                    ['wilayah_id' => $wilayah->id, 'name' => $lingkungan['name']],
                    ['area' => $lingkungan['area'], 'is_active' => true]
                );
            }

            // Lingkungan lama di wilayah ini yang tidak lagi ada di daftar resmi
            // cukup dinonaktifkan, bukan dihapus — akun pengguna menyimpan
            // lingkungan_id (nullOnDelete), jadi menghapus akan mengosongkan datanya.
            Lingkungan::where('wilayah_id', $wilayah->id)
                ->whereNotIn('name', array_column($lingkunganList, 'name'))
                ->update(['is_active' => false]);
        }

        // Wilayah di luar daftar resmi (mis. data contoh bawaan) ikut
        // dinonaktifkan beserta seluruh lingkungannya.
        $obsolete = Wilayah::whereNotIn('name', array_keys($this->data))->pluck('id');
        if ($obsolete->isNotEmpty()) {
            Wilayah::whereIn('id', $obsolete)->update(['is_active' => false]);
            Lingkungan::whereIn('wilayah_id', $obsolete)->update(['is_active' => false]);
        }
    }
}
