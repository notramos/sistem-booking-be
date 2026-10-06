<?php

namespace App\Notifications;

use App\Models\CongregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Notifications\Concerns\HasAlbertusWebPush;

class CongregationServiceApproved extends Notification
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
        $schedule = $this->scheduleSummary();
        return $this->pushMessage('Permohonan Disetujui', 'Permohonan '.$this->typeLabel().' telah disetujui.'.($schedule ? ' '.$schedule : ''), '/layanan-umat/'.$this->service->id, 'service-'.$this->service->id);
    }

    private function scheduleSummary(): string
    {
        if (! $this->service->service_date) return '';
        $details = $this->service->dynamic_fields ?? [];
        return 'Jadwal: '.$this->service->service_date->format('d/m/Y')
            .(isset($details['confirmed_time']) ? ' '.$details['confirmed_time'] : '')
            .(isset($details['confirmed_place']) ? ' di '.$details['confirmed_place'] : '').'.';
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
        $details = $this->service->dynamic_fields ?? [];
        if (isset($details['confirmed_time'])) {
            $mail->line('Jam pelayanan: ' . $details['confirmed_time']);
        }
        if (isset($details['confirmed_place'])) {
            $mail->line('Tempat pelayanan: ' . $details['confirmed_place']);
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
            'service_time' => ($this->service->dynamic_fields ?? [])['confirmed_time'] ?? null,
            'service_place' => ($this->service->dynamic_fields ?? [])['confirmed_place'] ?? null,
            'notes' => $this->service->notes,
            'type' => 'congregation_service_approved',
        ];
    }
}
