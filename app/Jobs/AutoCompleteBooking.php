<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;

/**
 * SENGAJA tidak implements ShouldQueue — hosting shared ini tidak punya proses
 * queue worker yang jalan terus (`php artisan queue:work`), cuma cron scheduler
 * biasa. Kalau pakai ShouldQueue, job cuma akan menumpuk di tabel `jobs` tanpa
 * pernah benar-benar dieksekusi. Tanpa ShouldQueue, `$schedule->job(...)`
 * menjalankannya langsung (synchronous) di proses cron itu sendiri.
 */
class AutoCompleteBooking
{
    public function handle(): void
    {
        $now = now();

        // Reguler: satu tanggal (booking_date) jadi acuan langsung.
        Booking::where('status', BookingStatus::APPROVED->value)
            ->where('booking_type', 'reguler')
            ->where(function ($q) use ($now) {
                $q->where('booking_date', '<', $now->toDateString())
                  ->orWhere(function ($q) use ($now) {
                      $q->where('booking_date', '=', $now->toDateString())
                        ->where('end_time', '<=', $now->format('H:i:s'));
                  });
            })
            ->update(['status' => BookingStatus::COMPLETED->value, 'completed_at' => $now]);

        // Rutin: baru selesai kalau tanggal TERAKHIR di recurring_dates sudah lewat —
        // booking_date cuma menyimpan tanggal pertama, jadi tidak bisa dipakai langsung
        // seperti booking reguler (nanti seri yang masih berjalan ikut ditandai selesai).
        Booking::where('status', BookingStatus::APPROVED->value)
            ->where('booking_type', 'rutin')
            ->whereNotNull('recurring_dates')
            ->get()
            ->each(function (Booking $booking) use ($now) {
                $dates = $booking->recurring_dates ?? [];
                if (empty($dates)) {
                    return;
                }

                $lastDate = collect($dates)->sort()->last();
                $isPast = $lastDate < $now->toDateString()
                    || ($lastDate === $now->toDateString() && $booking->end_time <= $now->format('H:i:s'));

                if ($isPast) {
                    $booking->update(['status' => BookingStatus::COMPLETED->value, 'completed_at' => $now]);
                }
            });
    }
}
