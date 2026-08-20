<?php

namespace App\Services;

use App\Models\NotificationChannelSetting;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationChannelConfigurationService
{
    /** @var array<string, string> */
    private const CHANNELS = [
        'in_app' => 'داخل المنصة',
        'whatsapp' => 'واتساب للأعمال',
        'sms' => 'رسائل نصية',
    ];

    /** @return Collection<int, NotificationChannelSetting> */
    public function all(): Collection
    {
        $now = now();
        NotificationChannelSetting::query()->insertOrIgnore(collect(self::CHANNELS)
            ->map(fn (string $label, string $code): array => [
                'code' => $code,
                'label' => $label,
                'is_enabled' => $code === 'in_app',
                'settings' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all());

        return NotificationChannelSetting::query()->orderBy('id')->get();
    }

    /** @param array{is_enabled?: bool, settings?: array<string, mixed>} $attributes */
    public function update(NotificationChannelSetting $channel, array $attributes, User $actor): NotificationChannelSetting
    {
        if ($channel->code === 'in_app') {
            $attributes['is_enabled'] = true;
        }

        $channel->fill($attributes + ['updated_by' => $actor->id])->save();

        return $channel->fresh();
    }

    public function isProviderReady(string $channel): bool
    {
        return match ($channel) {
            'in_app' => true,
            'whatsapp' => filled(config('services.whatsapp.access_token')) && filled(config('services.whatsapp.phone_number_id')),
            'sms' => filled(config('services.twilio.account_sid')) && filled(config('services.twilio.auth_token')) && filled(config('services.twilio.from_number')),
            default => false,
        };
    }
}
