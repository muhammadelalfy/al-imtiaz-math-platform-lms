<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\NotificationCampaign */
class NotificationCampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'audience' => $this->audience,
            'grade' => $this->grade,
            'academic_group_id' => $this->academic_group_id,
            'channels' => $this->channels ?? ['in_app'],
            'title' => $this->title,
            'body' => $this->body,
            'recipient_count' => $this->recipient_count,
            'queued_at' => $this->queued_at instanceof \Illuminate\Support\Carbon ? $this->queued_at->toISOString() : $this->queued_at,
            'completed_at' => $this->completed_at instanceof \Illuminate\Support\Carbon ? $this->completed_at->toISOString() : $this->completed_at,
            'sender' => $this->whenLoaded('sender', fn (): array => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
            ]),
        ];
    }
}
