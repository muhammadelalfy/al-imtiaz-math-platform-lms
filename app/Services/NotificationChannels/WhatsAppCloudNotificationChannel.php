<?php

namespace App\Services\NotificationChannels;

use App\Contracts\Notifications\NotificationChannelDispatcherInterface;
use App\Models\NotificationCampaign;
use App\Models\NotificationDelivery;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class WhatsAppCloudNotificationChannel implements NotificationChannelDispatcherInterface
{
    public function code(): string
    {
        return 'whatsapp';
    }

    public function isReady(): bool
    {
        return filled(config('services.whatsapp.access_token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    public function dispatch(NotificationCampaign $campaign, NotificationDelivery $delivery, array $settings): array
    {
        $template = data_get($settings, 'template_name');
        /** @var User $recipient */
        $recipient = $delivery->recipient;
        /** @var Student|null $student */
        $student = $recipient->studentAccount?->student;
        $to = $recipient->role === 'parent' ? $student?->parent_phone : $student?->phone;

        if (! $this->isReady()) {
            return ['status' => 'skipped', 'failure_reason' => 'WhatsApp credentials are not configured.'];
        }
        if (! $template || ! $to) {
            return ['status' => 'skipped', 'failure_reason' => 'A recipient phone number and an approved WhatsApp template are required.'];
        }

        $response = Http::withToken((string) config('services.whatsapp.access_token'))
            ->post('https://graph.facebook.com/v26.0/'.config('services.whatsapp.phone_number_id').'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => ['name' => $template, 'language' => ['code' => 'ar']],
            ]);

        if ($response->failed()) {
            return ['status' => 'failed', 'failure_reason' => str($response->body())->limit(1000)->toString()];
        }

        return ['status' => 'sent', 'provider_message_id' => (string) data_get($response->json(), 'messages.0.id')];
    }
}
