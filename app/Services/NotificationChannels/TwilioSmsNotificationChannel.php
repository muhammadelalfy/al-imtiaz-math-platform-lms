<?php

namespace App\Services\NotificationChannels;

use App\Contracts\Notifications\NotificationChannelDispatcherInterface;
use App\Models\NotificationCampaign;
use App\Models\NotificationDelivery;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class TwilioSmsNotificationChannel implements NotificationChannelDispatcherInterface
{
    public function code(): string
    {
        return 'sms';
    }

    public function isReady(): bool
    {
        return filled(config('services.twilio.account_sid')) && filled(config('services.twilio.auth_token')) && filled(config('services.twilio.from_number'));
    }

    public function dispatch(NotificationCampaign $campaign, NotificationDelivery $delivery, array $settings): array
    {
        /** @var User $recipient */
        $recipient = $delivery->recipient;
        /** @var Student|null $student */
        $student = $recipient->studentAccount?->student;
        $to = $recipient->role === 'parent' ? $student?->parent_phone : $student?->phone;
        $sid = (string) config('services.twilio.account_sid');

        if (! $this->isReady()) {
            return ['status' => 'skipped', 'failure_reason' => 'SMS credentials are not configured.'];
        }
        if (! $to) {
            return ['status' => 'skipped', 'failure_reason' => 'Recipient phone number is unavailable.'];
        }

        $response = Http::asForm()->withBasicAuth($sid, (string) config('services.twilio.auth_token'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $to,
                'From' => config('services.twilio.from_number'),
                'Body' => "{$campaign->title}\n{$campaign->body}",
            ]);

        if ($response->failed()) {
            return ['status' => 'failed', 'failure_reason' => str($response->body())->limit(1000)->toString()];
        }

        return ['status' => 'sent', 'provider_message_id' => (string) $response->json('sid')];
    }
}
