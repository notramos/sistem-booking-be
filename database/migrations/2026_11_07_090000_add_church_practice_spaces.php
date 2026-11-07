<?php

use App\Models\Room;
use App\Models\RoomCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $category = RoomCategory::firstOrCreate(
            ['slug' => 'ruang-ibadah-utama'],
            ['name' => 'Ruang Ibadah Utama', 'description' => 'Area di dalam gereja']
        );

        foreach ([
            'panti-koor-gereja' => [
                'name' => 'Panti Koor Gereja',
                'description' => 'Khusus latihan koor. Tanpa AC; lampu hanya di area panti koor dan wajib dimatikan kembali setelah latihan.',
            ],
            'area-altar-gereja' => [
                'name' => 'Area Altar Gereja',
                'description' => 'Khusus latihan lektor dan pemazmur setelah misa selesai. Tanpa AC; lampu dan mikrofon hanya di sekitar altar.',
            ],
        ] as $slug => $details) {
            $room = Room::withTrashed()->updateOrCreate(['slug' => $slug], [
                ...$details,
                'category_id' => $category->id,
                'capacity' => 20,
                'building' => 'Gereja',
                'status' => 'available',
                'is_active' => true,
            ]);
            if ($room->trashed()) {
                $room->restore();
            }
        }
    }

    public function down(): void
    {
        foreach (['panti-koor-gereja', 'area-altar-gereja'] as $slug) {
            $room = Room::where('slug', $slug)->first();
            if ($room && ! $room->bookings()->exists()) {
                $room->forceDelete();
            }
        }
    }
};
