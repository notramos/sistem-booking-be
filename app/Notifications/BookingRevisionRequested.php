<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRevisionRequested extends Notification
{
    use Queueable;

    public function __construct(private Booking $booking, private string $reason) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Revisi Diminta: ' . $this->booking->title)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Booking Anda perlu direvisi sebelum dapat diproses lebih lanjut:')
            ->line('Judul: ' . $this->booking->title)
            ->line('Ruangan: ' . $this->booking->room->name)
            ->line('Catatan Revisi: ' . $this->reason)
            ->action('Perbarui Booking', url('/my-bookings'))
            ->line('Silakan perbarui dan ajukan ulang booking Anda.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'title' => $this->booking->title,
            'room_name' => $this->booking->room->name,
            'reason' => $this->reason,
            'type' => 'booking_revision_requested',
        ];
    }
}
