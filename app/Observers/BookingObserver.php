<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Room;
use Illuminate\Support\Carbon;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'action' => 'created',
            'description' => 'Booking dibuat oleh ' . ($booking->user->name ?? 'Unknown'),
        ]);
    }

    public function updated(Booking $booking): void
    {
        if ($booking->isDirty('status')) {
            $oldStatus = $booking->getOriginal('status');
            $newStatus = $booking->status;

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => auth()->id() ?? $booking->user_id,
                'action' => 'status_changed',
                'description' => "Status berubah dari {$oldStatus} ke {$newStatus}",
            ]);
        }

        // Realokasi ruangan/tanggal/jam oleh sekretariat/admin (atau perubahan oleh
        // pemohon saat resubmit revisi) tidak selalu mengubah status, jadi dicatat
        // sebagai entri terpisah supaya tetap kelihatan di riwayat aktivitas booking.
        $slotFields = ['room_id', 'booking_date', 'start_time', 'end_time'];
        if ($booking->isDirty($slotFields)) {
            $changes = [];

            if ($booking->isDirty('room_id')) {
                $oldRoom = Room::find($booking->getOriginal('room_id'))?->name ?? '-';
                $newRoom = Room::find($booking->room_id)?->name ?? '-';
                $changes[] = "ruangan dari {$oldRoom} ke {$newRoom}";
            }

            if ($booking->isDirty('booking_date')) {
                $oldDate = Carbon::parse((string) $booking->getOriginal('booking_date'))->format('d M Y');
                $newDate = $booking->booking_date->format('d M Y');
                $changes[] = "tanggal dari {$oldDate} ke {$newDate}";
            }

            if ($booking->isDirty('start_time') || $booking->isDirty('end_time')) {
                $oldStart = substr((string) $booking->getOriginal('start_time'), 0, 5);
                $oldEnd = substr((string) $booking->getOriginal('end_time'), 0, 5);
                $newStart = substr($booking->start_time, 0, 5);
                $newEnd = substr($booking->end_time, 0, 5);
                $changes[] = "jam dari {$oldStart}-{$oldEnd} ke {$newStart}-{$newEnd}";
            }

            $actorName = auth()->user()->name ?? 'Sistem';

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => auth()->id() ?? $booking->user_id,
                'action' => 'rescheduled',
                'description' => "Jadwal diubah oleh {$actorName}: ".implode(', ', $changes),
            ]);
        }

        // Ganti satu tanggal dalam seri booking rutin (updateRecurringDate()) — kalau
        // tanggal yang diganti BUKAN yang paling awal, booking_date tidak ikut berubah,
        // jadi butuh entri log sendiri supaya perubahan tetap tercatat.
        if ($booking->isDirty('recurring_dates') && ! $booking->isDirty('booking_date')) {
            $oldDates = $booking->getOriginal('recurring_dates') ?? [];
            $newDates = $booking->recurring_dates ?? [];
            $removed = array_values(array_diff($oldDates, $newDates));
            $added = array_values(array_diff($newDates, $oldDates));
            $oldDate = $removed[0] ?? null;
            $newDate = $added[0] ?? null;

            if ($oldDate && $newDate) {
                $actorName = auth()->user()->name ?? 'Sistem';

                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => auth()->id() ?? $booking->user_id,
                    'action' => 'rescheduled',
                    'description' => "Satu tanggal booking rutin diubah oleh {$actorName}: "
                        .Carbon::parse($oldDate)->format('d M Y').' ke '.Carbon::parse($newDate)->format('d M Y'),
                ]);
            } elseif ($oldDate && ! $newDate) {
                // Hapus tanggal (deleteRecurringDate()) — kalau tanggal yang dihapus BUKAN
                // yang paling awal, booking_date tidak ikut berubah, jadi butuh entri log
                // sendiri di sini juga (mirror blok "diubah" di atas).
                $actorName = auth()->user()->name ?? 'Sistem';

                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => auth()->id() ?? $booking->user_id,
                    'action' => 'rescheduled',
                    'description' => "Satu tanggal booking rutin dihapus oleh {$actorName}: "
                        .Carbon::parse($oldDate)->format('d M Y'),
                ]);
            }
        }
    }
}
