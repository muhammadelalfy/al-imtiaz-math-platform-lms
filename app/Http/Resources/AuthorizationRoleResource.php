<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AuthorizationRole */
class AuthorizationRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'is_system' => $this->is_system,
            'permissions' => AuthorizationPermissionResource::collection($this->whenLoaded('permissions')),
            'permission_ids' => $this->whenLoaded('permissions', fn (): array => $this->permissions->pluck('id')->all()),
        ];
    }
}
