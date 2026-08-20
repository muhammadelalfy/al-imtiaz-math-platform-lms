<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TenantSubscription */
class TenantSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $startsAt = $this->resource->getAttribute('starts_at');
        $endsAt = $this->resource->getAttribute('ends_at');
        $paidAt = $this->resource->getAttribute('paid_at');
        $daysRemaining = $endsAt instanceof CarbonInterface
            ? now()->startOfDay()->diffInDays($endsAt->startOfDay(), false)
            : null;

        return [
            'id' => $this->id, 'status' => $this->status, 'payment_status' => $this->payment_status,
            'starts_at' => $startsAt instanceof CarbonInterface ? $startsAt->toISOString() : null,
            'ends_at' => $endsAt instanceof CarbonInterface ? $endsAt->toISOString() : null,
            'paid_at' => $paidAt instanceof CarbonInterface ? $paidAt->toISOString() : null,
            'payment_reference' => $this->payment_reference,
            'admin_note' => $this->admin_note, 'days_remaining' => $daysRemaining,
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'package' => new SubscriptionPackageResource($this->whenLoaded('package')),
            'activated_by' => $this->whenLoaded('activatedBy', fn () => $this->activatedBy?->only(['id', 'name'])),
        ];
    }
}
