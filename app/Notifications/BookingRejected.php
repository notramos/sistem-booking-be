<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejected extends Notification
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
            ->subject('Booking Ditolak: ' . $this->booking->title)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Booking Anda telah ditolak:')
            ->line('Judul: ' . $this->booking->title)
            ->line('Ruangan: ' . $this->booking->room->name)
            ->line('Alasan: ' . $this->booking->reject_reason)
            ->action('Lihat Detail', url('/my-bookings'))
            ->line('Silakan hubungi sekretariat untuk informasi lebih lanjut.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'title' => $this->booking->title,
            'room_name' => $this->booking->room->name,
            'booking_date' => $this->booking->booking_date->format('Y-m-d'),
            'start_time' => $this->booking->start_time,
            'end_time' => $this->booking->end_time,
            'reason' => $this->booking->reject_reason,
            'type' => 'booking_rejected',
        ];
    }
}
