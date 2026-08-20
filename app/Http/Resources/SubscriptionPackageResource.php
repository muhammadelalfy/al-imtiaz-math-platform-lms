<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SubscriptionPackage */
class SubscriptionPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'tagline' => $this->tagline,
            'description' => $this->description, 'price_cents' => $this->price_cents, 'currency' => $this->currency,
            'duration_days' => $this->duration_days, 'teacher_limit' => $this->teacher_limit,
            'student_limit' => $this->student_limit, 'features' => $this->features ?? [], 'is_active' => $this->is_active,
            'sort_order' => $this->sort_order, 'subscriptions_count' => $this->whenCounted('subscriptions'),
        ];
    }
}
