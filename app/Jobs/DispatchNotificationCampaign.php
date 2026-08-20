<?php

namespace App\Jobs;

use App\Models\NotificationCampaign;
use App\Models\NotificationDelivery;
use App\Services\NotificationChannelDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Throwable;

#[Timeout(60)]
#[MaxExceptions(3)]
class DispatchNotificationCampaign implements ShouldQueue
{
    use FoundationQueueable, Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [15, 60];

    public function __construct(public readonly int $campaignId)
    {
        $this->onQueue('notifications');
    }

    public function handle(NotificationChannelDeliveryService $channels): void
    {
        $campaign = NotificationCampaign::query()->find($this->campaignId);

        if (! $campaign) {
            return;
        }

        NotificationDelivery::query()
            ->where('notification_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->with(['recipient.studentAccount.student', 'channels'])
            ->orderBy('id')
            ->chunkById(100, function ($deliveries) use ($campaign, $channels): void {
                foreach ($deliveries as $delivery) {
                    try {
                        $delivery->increment('attempts');
                        $delivered = $channels->dispatch($campaign, $delivery);
                        $delivery->forceFill([
                            'status' => $delivered ? 'delivered' : 'failed',
                            'failure_reason' => $delivered ? null : 'No selected channel delivered the notification.',
                            'delivered_at' => $delivered ? now() : null,
                        ])->save();
                    } catch (Throwable $exception) {
                        $delivery->forceFill([
                            'status' => 'failed',
                            'failure_reason' => str($exception->getMessage())->limit(1000)->toString(),
                        ])->save();
                    }
                }
            }, 'id');

        if (! $campaign->deliveries()->where('status', 'pending')->exists()) {
            $campaign->forceFill(['completed_at' => now()])->save();
        }
    }
}
