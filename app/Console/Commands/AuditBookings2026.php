<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Command read-only: cetak semua booking Agu-Des 2026 (termasuk kemunculan
 * dari `recurring_dates`) supaya bisa dibandingkan manual dengan spreadsheet
 * sumber. Tidak mengubah data apa pun.
 */
class AuditBookings2026 extends Command
{
    protected $signature = 'bookings:audit-2026';

    protected $description = 'Cetak semua booking Agu-Des 2026 (read-only) untuk audit terhadap spreadsheet sumber';

    public function handle(): int
    {
        $start = Carbon::parse('2026-08-01');
        $end = Carbon::parse('2026-12-31');

        $bookings = Booking::with('room:id,name')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNotNull('recurring_dates');
                    });
            })
            ->orderBy('booking_date')
            ->get(['id', 'title', 'description', 'room_id', 'booking_type', 'booking_date', 'start_time', 'end_time', 'recurring_dates', 'status']);

        $this->info("Total baris booking: {$bookings->count()}");
        $this->newLine();

        foreach ($bookings as $b) {
            $dates = $b->recurring_dates
                ? collect($b->recurring_dates)->filter(fn ($d) => $d >= $start->toDateString() && $d <= $end->toDateString())->values()
                : collect([$b->booking_date]);

            if ($dates->isEmpty()) {
                continue;
            }

            $this->line(sprintf(
                '[%s] %s | %s | %s | %s-%s | status=%s | tanggal=%s',
                $b->booking_type,
                $b->title,
                $b->description ?? '-',
                $b->room->name ?? '(ruangan terhapus)',
                $b->start_time,
                $b->end_time,
                $b->status,
                $dates->implode(', ')
            ));
        }

        return self::SUCCESS;
    }
}
