<?php

namespace App\Http\Resources;

use App\Services\TeacherDashboardLayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TeacherDashboardLayout */
class TeacherDashboardLayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'card_order' => TeacherDashboardLayoutService::normalize($this->resource->card_order),
        ];
    }
}
