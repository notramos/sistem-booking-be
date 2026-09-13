<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PushTestNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('AlbertusKU')
            ->body('Notifikasi push sudah aktif di perangkat ini.')
            ->icon(config('services.frontend.url').'/icons/icon-192.png')
            ->badge(config('services.frontend.url').'/icons/icon-192.png')
            ->data(['url' => '/notifications']);
    }
}
