<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification
{
    use Queueable;

    public function __construct(private Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking Dibatalkan: ' . $this->booking->title)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Booking berikut telah dibatalkan:')
            ->line('Judul: ' . $this->booking->title)
            ->line('Ruangan: ' . $this->booking->room->name)
            ->line('Tanggal: ' . $this->booking->booking_date->format('d/m/Y'))
            ->line('Waktu: ' . $this->booking->start_time . ' - ' . $this->booking->end_time)
            ->action('Lihat Detail', url('/'))
            ->line('Terima kasih');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'title' => $this->booking->title,
            'room_name' => $this->booking->room->name,
            'type' => 'booking_cancelled',
        ];
    }
}
