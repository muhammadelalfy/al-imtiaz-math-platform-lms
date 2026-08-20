<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $notification_campaign_id
 * @property int $recipient_id
 * @property string $status
 * @property int $attempts
 * @property-read User $recipient
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NotificationDeliveryChannel> $channels
 */
class NotificationDelivery extends Model
{
    protected $fillable = [
        'notification_campaign_id',
        'recipient_id',
        'status',
        'attempts',
        'failure_reason',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(NotificationCampaign::class, 'notification_campaign_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function channels(): HasMany
    {
        return $this->hasMany(NotificationDeliveryChannel::class);
    }
}
