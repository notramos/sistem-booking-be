<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Notifications\Concerns\HasAlbertusWebPush;

/**
 * Kabar kemajuan untuk PEMOHON saat sekretariat menyetujui tahap pertama.
 * Berbeda dari BookingMovedToAdminReview yang ditujukan ke para admin sebagai
 * ajakan bertindak — di sini nadanya memberi tahu, bukan meminta persetujuan.
 */
class BookingForwardedToAdmin extends Notification
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
        return $this->pushMessage('Peminjaman Diteruskan', 'Peminjaman Anda sudah disetujui sekretariat dan menunggu persetujuan final.', '/booking/'.$this->booking->id, 'booking-forwarded-'.$this->booking->id);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking Diteruskan ke Admin: ' . $this->booking->title)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Booking Anda sudah disetujui Sekretariat dan sekarang menunggu persetujuan final dari Admin:')
            ->line('Peminjam: ' . $this->booking->title)
            ->line('Ruangan: ' . $this->booking->room->display_label)
            ->line('Tanggal: ' . $this->booking->booking_date->format('d/m/Y'))
            ->line('Waktu: ' . $this->booking->start_time . ' - ' . $this->booking->end_time)
            ->action('Lihat Booking', url('/booking/' . $this->booking->id))
            ->line('Anda akan diberi tahu lagi setelah Admin memberi keputusan.');
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
            'type' => 'booking_forwarded_to_admin',
        ];
    }
}
