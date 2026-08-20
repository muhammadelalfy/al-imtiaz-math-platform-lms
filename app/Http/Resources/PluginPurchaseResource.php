<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PluginPurchase */
class PluginPurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plugin_product_id' => $this->plugin_product_id,
            'status' => $this->status,
            'purchased_at' => $this->purchased_at,
            'plugin' => $this->whenLoaded('plugin', fn () => new PluginProductResource($this->plugin)),
        ];
    }
}
