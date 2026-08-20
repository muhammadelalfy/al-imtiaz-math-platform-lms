<?php

namespace App\Models;

use Database\Factories\NotificationCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $sent_by
 * @property string $audience
 * @property string|null $grade
 * @property int|null $academic_group_id
 * @property array<int, string>|null $channels
 * @property string $title
 * @property string $body
 * @property int $recipient_count
 * @property \Illuminate\Support\Carbon|null $queued_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property-read User $sender
 */
class NotificationCampaign extends Model
{
    /** @use HasFactory<NotificationCampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'sent_by',
        'audience',
        'grade',
        'academic_group_id',
        'channels',
        'title',
        'body',
        'recipient_count',
        'queued_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'recipient_ids' => 'array',
            'channels' => 'array',
            'queued_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function academicGroup(): BelongsTo
    {
        return $this->belongsTo(AcademicGroup::class);
    }
}
