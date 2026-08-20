<?php

namespace App\Services;

use App\Contracts\Notifications\NotificationChannelDispatcherInterface;
use App\Models\NotificationCampaign;
use App\Models\NotificationChannelSetting;
use App\Models\NotificationDelivery;

class NotificationChannelDeliveryService
{
    /** @param iterable<NotificationChannelDispatcherInterface> $dispatchers */
    public function __construct(
        private readonly iterable $dispatchers,
        private readonly NotificationChannelConfigurationService $configuration,
    )
    {
    }

    public function dispatch(NotificationCampaign $campaign, NotificationDelivery $delivery): bool
    {
        $selectedChannels = $campaign->channels ?: ['in_app'];
        $settings = $this->configuration->all()->whereIn('code', $selectedChannels)->keyBy('code');
        $drivers = collect($this->dispatchers)->keyBy(fn (NotificationChannelDispatcherInterface $dispatcher): string => $dispatcher->code());
        $existing = $delivery->channels->keyBy('channel');
        $now = now();
        $missing = collect($selectedChannels)
            ->reject(fn (string $code): bool => $existing->has($code))
            ->map(fn (string $code): array => [
                'notification_delivery_id' => $delivery->id,
                'channel' => $code,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();
        if ($missing !== []) {
            $delivery->channels()->insert($missing);
            $existing = $delivery->channels()->get()->keyBy('channel');
        }
        $delivered = false;

        foreach ($selectedChannels as $code) {
            $record = $existing->get($code);
            if (! $record) {
                continue;
            }
            $channel = $settings->get($code);
            $driver = $drivers->get($code);
            if (! $channel?->is_enabled || ! $driver instanceof NotificationChannelDispatcherInterface) {
                $record->update(['status' => 'skipped', 'failure_reason' => 'Channel is disabled or unavailable.']);
                continue;
            }

            $result = $driver->dispatch($campaign, $delivery, $channel->settings ?? []);
            $record->update([
                'status' => $result['status'],
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'failure_reason' => $result['failure_reason'] ?? null,
                'sent_at' => $result['status'] === 'sent' ? now() : null,
            ]);
            $delivered = $delivered || $result['status'] === 'sent';
        }

        return $delivered;
    }
}
