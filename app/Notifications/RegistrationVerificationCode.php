<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dikirim via Notification::route('mail', $email)->notify(...) — belum ada User
 * (notifiable) di titik ini, jadi cuma channel 'mail' (tidak ada 'database').
 */
class RegistrationVerificationCode extends Notification
{
    use Queueable;

    public function __construct(private string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Pendaftaran E-Albertus')
            ->greeting('Halo,')
            ->line('Gunakan kode berikut untuk memverifikasi email Anda dan melanjutkan pendaftaran:')
            ->line("**{$this->code}**")
            ->line('Kode ini berlaku selama 15 menit.')
            ->line('Kalau Anda tidak merasa melakukan pendaftaran ini, abaikan saja email ini.');
    }
}
