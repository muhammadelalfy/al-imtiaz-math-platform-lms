<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tenant */
class TeacherAcademyIdentityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'academy_name' => $this->resource->getAttribute('name'),
        ];
    }
}
