<?php

namespace App\Notifications;

use App\Models\CongregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CongregationServiceCreated extends Notification
{
    use Queueable;

    public function __construct(private CongregationService $service) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel = config("congregation-services.types.{$this->service->service_type}.label", $this->service->service_type);

        return (new MailMessage)
            ->subject('Permohonan Baru: ' . $typeLabel)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Ada permohonan pelayanan baru yang perlu diproses:')
            ->line('Jenis: ' . $typeLabel)
            ->line('Pemohon: ' . $this->service->applicant_name)
            ->line('Kontak: ' . $this->service->contact)
            ->action('Lihat Permohonan', url('/layanan-umat'))
            ->line('Terima kasih');
    }

    public function toDatabase(object $notifiable): array
    {
        $typeLabel = config("congregation-services.types.{$this->service->service_type}.label", $this->service->service_type);

        return [
            'congregation_service_id' => $this->service->id,
            'service_type' => $this->service->service_type,
            'service_type_label' => $typeLabel,
            'applicant_name' => $this->service->applicant_name,
            'contact' => $this->service->contact,
            'type' => 'congregation_service_created',
        ];
    }
}
