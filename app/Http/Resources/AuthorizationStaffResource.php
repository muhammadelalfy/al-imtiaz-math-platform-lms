<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class AuthorizationStaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'base_role' => $this->role,
            'roles' => AuthorizationRoleResource::collection($this->whenLoaded('roles')),
            'role_ids' => $this->whenLoaded('roles', fn (): array => $this->roles->pluck('id')->all()),
        ];
    }
}
