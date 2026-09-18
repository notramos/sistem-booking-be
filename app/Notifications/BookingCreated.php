<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Notifications\Concerns\HasAlbertusWebPush;

class BookingCreated extends Notification
{
    use Queueable;
    use HasAlbertusWebPush;

    public function __construct(private Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return $this->pushMessage('Peminjaman Baru', 'Ada peminjaman baru dari '.$this->booking->user->name.' yang perlu diproses.', '/approvals', 'booking-new-'.$this->booking->id);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking Baru: ' . $this->booking->title)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Ada booking baru yang perlu persetujuan:')
            ->line('Judul: ' . $this->booking->title)
            ->line('Ruangan: ' . $this->booking->room->display_label)
            ->line('Tanggal: ' . $this->booking->booking_date->format('d/m/Y'))
            ->line('Waktu: ' . $this->booking->start_time . ' - ' . $this->booking->end_time)
            ->line('Pemesan: ' . $this->booking->user->name)
            ->action('Lihat Booking', url('/approvals'))
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
            'booker_name' => $this->booking->user->name,
            'type' => 'booking_created',
        ];
    }
}
