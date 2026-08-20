<?php

namespace App\Contracts\Notifications;

use App\Models\NotificationCampaign;
use App\Models\NotificationDelivery;

interface NotificationChannelDispatcherInterface
{
    public function code(): string;

    public function isReady(): bool;

    /** @return array{status: string, provider_message_id?: string, failure_reason?: string} */
    public function dispatch(NotificationCampaign $campaign, NotificationDelivery $delivery, array $settings): array;
}
