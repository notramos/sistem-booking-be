<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminder extends Notification
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
            ->subject('Pengingat Booking: ' . $this->booking->title)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Ini adalah pengingat bahwa Anda memiliki booking besok:')
            ->line('Judul: ' . $this->booking->title)
            ->line('Ruangan: ' . $this->booking->room->name)
            ->line('Tanggal: ' . $this->booking->booking_date->format('d/m/Y'))
            ->line('Waktu: ' . $this->booking->start_time . ' - ' . $this->booking->end_time)
            ->action('Lihat Detail', url('/my-bookings/' . $this->booking->id))
            ->line('Terima kasih');
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
            'type' => 'booking_reminder',
        ];
    }
}
