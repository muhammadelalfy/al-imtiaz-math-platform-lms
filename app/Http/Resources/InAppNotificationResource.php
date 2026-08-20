<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DatabaseNotification */
class InAppNotificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->data['campaign_id'] ?? null,
            'delivery_id' => $this->data['delivery_id'] ?? null,
            'title' => $this->data['title'] ?? '',
            'body' => $this->data['body'] ?? '',
            'audience' => $this->data['audience'] ?? null,
            'grade' => $this->data['grade'] ?? null,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
