<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecurringBookingCreated extends Notification
{
    use Queueable;

    public function __construct(private Booking $booking, private int $skippedCount) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dates = $this->booking->recurring_dates ?? [];
        $first = $dates[0] ?? $this->booking->booking_date->format('Y-m-d');
        $last = $dates[count($dates) - 1] ?? $first;

        $message = (new MailMessage)
            ->subject('Booking Rutin Baru: ' . $this->booking->title)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Ada pengajuan booking rutin baru yang perlu persetujuan:')
            ->line('Judul: ' . $this->booking->title)
            ->line('Ruangan: ' . $this->booking->room->name)
            ->line('Jumlah tanggal: ' . count($dates) . " (dari {$first} s/d {$last})")
            ->line('Waktu: ' . $this->booking->start_time . ' - ' . $this->booking->end_time)
            ->line('Pemesan: ' . $this->booking->user->name);

        if ($this->skippedCount > 0) {
            $message->line("{$this->skippedCount} tanggal dilewati karena bentrok/jadwal perbaikan.");
        }

        return $message->action('Lihat Booking', url('/approvals'))->line('Terima kasih');
    }

    public function toDatabase(object $notifiable): array
    {
        $dates = $this->booking->recurring_dates ?? [];

        return [
            'booking_id' => $this->booking->id,
            'title' => $this->booking->title,
            'room_name' => $this->booking->room->name,
            'booking_date' => $dates[0] ?? $this->booking->booking_date->format('Y-m-d'),
            'end_booking_date' => $dates[count($dates) - 1] ?? null,
            'occurrence_count' => count($dates),
            'skipped_count' => $this->skippedCount,
            'start_time' => $this->booking->start_time,
            'end_time' => $this->booking->end_time,
            'booker_name' => $this->booking->user->name,
            'type' => 'recurring_booking_created',
        ];
    }
}
