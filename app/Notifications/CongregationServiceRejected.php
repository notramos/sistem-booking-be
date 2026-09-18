<?php

namespace App\Notifications;

use App\Models\CongregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Notifications\Concerns\HasAlbertusWebPush;

class CongregationServiceRejected extends Notification
{
    use Queueable;
    use HasAlbertusWebPush;

    public function __construct(private CongregationService $service) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return $this->pushMessage('Permohonan Ditolak', 'Permohonan '.$this->typeLabel().' ditolak. Buka detail untuk melihat informasinya.', '/layanan-umat/'.$this->service->id, 'service-'.$this->service->id);
    }

    private function typeLabel(): string
    {
        return config("congregation-services.types.{$this->service->service_type}.label", $this->service->service_type);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Permohonan Ditolak: ' . $this->typeLabel())
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Permohonan pelayanan Anda tidak dapat diproses:')
            ->line('Jenis: ' . $this->typeLabel())
            ->line('Atas nama: ' . $this->service->applicant_name)
            ->line('Alasan: ' . ($this->service->notes ?: '-'))
            ->action('Lihat Permohonan', url('/layanan-umat/' . $this->service->id))
            ->line('Silakan hubungi sekretariat untuk informasi lebih lanjut.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'congregation_service_id' => $this->service->id,
            'service_type' => $this->service->service_type,
            'service_type_label' => $this->typeLabel(),
            'applicant_name' => $this->service->applicant_name,
            'reason' => $this->service->notes,
            'type' => 'congregation_service_rejected',
        ];
    }
}
