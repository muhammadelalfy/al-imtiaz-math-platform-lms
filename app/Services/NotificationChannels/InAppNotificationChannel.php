<?php

namespace App\Services\NotificationChannels;

use App\Contracts\Notifications\NotificationChannelDispatcherInterface;
use App\Models\NotificationCampaign;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\InAppLmsNotification;

class InAppNotificationChannel implements NotificationChannelDispatcherInterface
{
    public function code(): string
    {
        return 'in_app';
    }

    public function isReady(): bool
    {
        return true;
    }

    public function dispatch(NotificationCampaign $campaign, NotificationDelivery $delivery, array $settings): array
    {
        /** @var User $recipient */
        $recipient = $delivery->recipient;
        $recipient->notify(new InAppLmsNotification($campaign, $delivery));

        return ['status' => 'sent'];
    }
}
