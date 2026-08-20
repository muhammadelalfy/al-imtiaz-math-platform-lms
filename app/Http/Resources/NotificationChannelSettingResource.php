<?php

namespace App\Http\Resources;

use App\Services\NotificationChannelConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\NotificationChannelSetting */
class NotificationChannelSettingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $this->label,
            'is_enabled' => $this->is_enabled,
            'is_provider_ready' => app(NotificationChannelConfigurationService::class)->isProviderReady($this->code),
            'settings' => $this->settings ?? [],
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
