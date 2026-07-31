<?php

namespace App\Notifications;

use App\Models\CongregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CongregationServiceApproved extends Notification
{
    use Queueable;

    public function __construct(private CongregationService $service) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function typeLabel(): string
    {
        return config("congregation-services.types.{$this->service->service_type}.label", $this->service->service_type);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Permohonan Disetujui: ' . $this->typeLabel())
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Permohonan pelayanan Anda telah disetujui:')
            ->line('Jenis: ' . $this->typeLabel())
            ->line('Atas nama: ' . $this->service->applicant_name);

        if ($this->service->service_date) {
            $mail->line('Tanggal pelayanan: ' . $this->service->service_date->format('d/m/Y'));
        }
        if ($this->service->notes) {
            $mail->line('Catatan sekretariat: ' . $this->service->notes);
        }

        return $mail
            ->action('Lihat Permohonan', url('/layanan-umat/' . $this->service->id))
            ->line('Terima kasih');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'congregation_service_id' => $this->service->id,
            'service_type' => $this->service->service_type,
            'service_type_label' => $this->typeLabel(),
            'applicant_name' => $this->service->applicant_name,
            'service_date' => $this->service->service_date?->format('Y-m-d'),
            'notes' => $this->service->notes,
            'type' => 'congregation_service_approved',
        ];
    }
}
