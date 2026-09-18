<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Notifications\Concerns\HasAlbertusWebPush;

class BookingReminder extends Notification
{
    use Queueable;
    use HasAlbertusWebPush;

    public function __construct(private Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class, 'mail'];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return $this->pushMessage('Pengingat Peminjaman', 'Peminjaman '.$this->booking->title.' dijadwalkan besok.', '/booking/'.$this->booking->id, 'booking-reminder-'.$this->booking->id);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengingat Booking: ' . $this->booking->title)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Ini adalah pengingat bahwa Anda memiliki booking besok:')
            ->line('Judul: ' . $this->booking->title)
            ->line('Ruangan: ' . $this->booking->room->display_label)
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
            'room_name' => $this->booking->room->display_label,
            'booking_date' => $this->booking->booking_date->format('Y-m-d'),
            'start_time' => $this->booking->start_time,
            'end_time' => $this->booking->end_time,
            'type' => 'booking_reminder',
        ];
    }
}
