<?php

namespace App\Notifications;

use App\Models\NotificationCampaign;
use App\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InAppLmsNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly NotificationCampaign $campaign,
        private readonly NotificationDelivery $delivery,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|null> */
    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'delivery_id' => $this->delivery->id,
            'title' => $this->campaign->title,
            'body' => $this->campaign->body,
            'audience' => $this->campaign->audience,
            'grade' => $this->campaign->grade,
            'sent_by' => $this->campaign->sent_by,
        ];
    }
}
