<?php

namespace Database\Seeders;

use App\Models\Lingkungan;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;

class WilayahLingkunganSeeder extends Seeder
{
    /**
     * Data contoh wilayah & lingkungan untuk paroki.
     * Silakan ganti dengan data resmi paroki; nama lingkungan dibuat unik
     * karena form menyimpan nama (bukan id) dan menampilkannya di dokumen.
     *
     * @var array<string, list<string>>
     */
    private array $data = [
        'Wilayah 1 - Santo Petrus' => [
            'St. Petrus', 'St. Andreas', 'St. Yohanes Pembaptis', 'St. Stefanus', 'St. Bartolomeus',
        ],
        'Wilayah 2 - Santo Paulus' => [
            'St. Paulus', 'St. Timotius', 'St. Titus', 'St. Barnabas', 'St. Silas',
        ],
        'Wilayah 3 - Santa Maria' => [
            'St. Maria Bunda', 'St. Maria Magdalena', 'St. Anna', 'St. Elisabet', 'St. Monika',
        ],
        'Wilayah 4 - Santo Yosef' => [
            'St. Yosef', 'St. Yoakim', 'St. Zakharia', 'St. Simeon', 'St. Gabriel',
        ],
        'Wilayah 5 - Santo Fransiskus' => [
            'St. Fransiskus Asisi', 'St. Klara', 'St. Antonius Padua', 'St. Bonaventura', 'St. Fransiskus Xaverius',
        ],
        'Wilayah 6 - Santo Yohanes' => [
            'St. Yohanes Rasul', 'St. Yohanes Bosco', 'St. Yohanes Maria Vianney', 'St. Yohanes Paulus II', 'St. Lukas',
        ],
        'Wilayah 7 - Santo Ignatius' => [
            'St. Ignatius Loyola', 'St. Aloysius Gonzaga', 'St. Stanislaus', 'St. Robertus Bellarminus', 'St. Petrus Kanisius',
        ],
        'Wilayah 8 - Santa Theresia' => [
            'St. Theresia Kanak-kanak Yesus', 'St. Theresia Avila', 'St. Katarina Siena', 'St. Cecilia', 'St. Agnes',
        ],
    ];

    public function run(): void
    {
        foreach ($this->data as $wilayahName => $lingkunganNames) {
            $wilayah = Wilayah::firstOrCreate(['name' => $wilayahName], ['is_active' => true]);

            foreach ($lingkunganNames as $lingkunganName) {
                Lingkungan::firstOrCreate(
                    ['wilayah_id' => $wilayah->id, 'name' => $lingkunganName],
                    ['is_active' => true]
                );
            }
        }
    }
}
