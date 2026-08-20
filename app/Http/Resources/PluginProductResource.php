<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PluginProduct */
class PluginProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $purchase = $this->resource->relationLoaded('purchases')
            ? $this->resource->getRelation('purchases')->first()
            : null;
        $installation = $this->resource->relationLoaded('installations')
            ? $this->resource->getRelation('installations')->first()
            : null;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'module_name' => $this->module_name,
            'price' => $this->price,
            'purchased' => $purchase !== null,
            'installed' => $installation !== null,
            'installed_module' => $installation?->getAttribute('module_name'),
            'payment_status' => $this->resource->getAttribute('payment_status'),
            'core_feature' => (bool) data_get($this->metadata, 'core_feature', false),
            'metadata' => $this->metadata,
        ];
    }
}
