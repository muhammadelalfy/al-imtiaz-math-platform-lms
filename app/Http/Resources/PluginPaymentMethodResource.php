<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PluginPaymentMethod */
class PluginPaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $this->label,
            'recipient' => $this->recipient,
            'instructions' => $this->instructions,
            'is_enabled' => $this->is_enabled,
        ];
    }
}
