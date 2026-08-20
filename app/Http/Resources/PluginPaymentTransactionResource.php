<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PluginPaymentTransaction */
class PluginPaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payer = $this->resource->relationLoaded('user') ? $this->resource->getRelation('user') : null;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reference' => $this->reference,
            'customer_note' => $this->customer_note,
            'review_note' => $this->review_note,
            'reviewed_at' => $this->reviewed_at,
            'fulfilled_at' => $this->fulfilled_at,
            'plugin' => $this->whenLoaded('plugin', fn () => new PluginProductResource($this->plugin)),
            'method' => $this->whenLoaded('method', fn () => new PluginPaymentMethodResource($this->method)),
            'user' => $this->when($payer !== null, [
                'id' => $payer?->getKey(),
                'name' => $payer?->getAttribute('name'),
            ]),
        ];
    }
}
