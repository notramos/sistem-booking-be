<?php

namespace App\Notifications\Concerns;

use NotificationChannels\WebPush\WebPushMessage;

trait HasAlbertusWebPush
{
    protected function pushMessage(string $title, string $body, string $path, string $tag): WebPushMessage
    {
        $baseUrl = rtrim(config('services.frontend.url'), '/');

        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon($baseUrl.'/icons/icon-192.png')
            ->badge($baseUrl.'/icons/icon-192.png')
            ->tag($tag)
            ->data(['url' => $path]);
    }
}
