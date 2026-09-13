<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\CongregationService;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh untuk melihat antrean persetujuan.
 * Jalankan secara eksplisit dengan --class=ApprovalDemoSeeder.
 * Tidak dipanggil oleh DatabaseSeeder agar data demo tidak masuk tanpa sengaja.
 */
class ApprovalDemoSeeder extends Seeder
{
    private const MARKER = '[DEMO-PERSETUJUAN]';

    public function run(): void
    {
        $requester = User::role('umat')->first() ?? User::firstOrFail();
        $rooms = Room::query()->where('is_active', true)->orderBy('name')->take(5)->get();

        if ($rooms->isEmpty()) {
            $this->command?->warn('Tidak ada ruangan aktif. Seeder demo dibatalkan.');
            return;
        }

        $now = Carbon::now();
        $bookingRows = [
            ['title' => 'Demo - Pengajuan paling lama', 'status' => 'pending', 'days' => 7, 'hours' => 8],
            ['title' => 'Demo - Jadwal terdekat', 'status' => 'pending', 'days' => 2, 'hours' => 10],
            ['title' => 'Demo - Review Sekretariat', 'status' => 'sekretariat_review', 'days' => 5, 'hours' => 13],
            ['title' => 'Demo - Review Admin', 'status' => 'admin_review', 'days' => 4, 'hours' => 15],
            ['title' => 'Demo - Pengajuan baru', 'status' => 'pending', 'days' => 10, 'hours' => 16],
        ];

        foreach ($bookingRows as $index => $row) {
            $booking = Booking::updateOrCreate(
                ['description' => self::MARKER . ' ' . $row['title']],
                [
                    'user_id' => $requester->id,
                    'room_id' => $rooms[$index % $rooms->count()]->id,
                    'title' => $row['title'],
                    'description' => self::MARKER . ' ' . $row['title'],
                    'booking_date' => $now->copy()->addDays($row['days'])->toDateString(),
                    'start_time' => sprintf('%02d:00:00', $row['hours']),
                    'end_time' => sprintf('%02d:00:00', $row['hours'] + 2),
                    'expected_attendees' => 20 + ($index * 10),
                    'contact_person' => '081200000' . $index,
                    'status' => $row['status'],
                    'booking_type' => 'reguler',
                    'notes' => 'Data contoh untuk menguji antrean persetujuan.',
                ]
            );

            // Buat urutan antrean yang mudah terlihat: paling lama sampai terbaru.
            $booking->forceFill([
                'created_at' => $now->copy()->subDays(7 - $index)->subHours($index),
                'updated_at' => $now,
            ])->saveQuietly();
        }

        $serviceRows = [
            ['name' => 'Demo Intensi Misa Lama', 'days' => 3],
            ['name' => 'Demo Intensi Misa Terdekat', 'days' => 1],
            ['name' => 'Demo Konsultasi Romo', 'days' => 6],
        ];

        foreach ($serviceRows as $index => $row) {
            $service = CongregationService::updateOrCreate(
                ['description' => self::MARKER . ' ' . $row['name']],
                [
                    'user_id' => $requester->id,
                    'service_type' => $index === 2 ? 'konsultasi_romo' : 'intensi_misa',
                    'applicant_name' => $row['name'],
                    'contact' => '081300000' . $index,
                    'service_date' => $now->copy()->addDays($row['days'])->toDateString(),
                    'description' => self::MARKER . ' ' . $row['name'],
                    'status' => 'pending',
                    'dynamic_fields' => [],
                ]
            );

            $service->forceFill([
                'created_at' => $now->copy()->subDays(5 - $index),
                'updated_at' => $now,
            ])->saveQuietly();
        }

        $this->command?->info('Seeder demo persetujuan selesai: 5 booking dan 3 permohonan pelayanan dibuat/diperbarui.');
    }
}
