<?php

namespace App\Http\Resources;

use App\Models\TeacherSlackLogDestination;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TeacherSlackLogDestination */
class TeacherSlackLogDestinationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'channel_label' => $this->channel_label,
            'is_enabled' => $this->is_enabled,
            'configured' => filled($this->webhook_url),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
