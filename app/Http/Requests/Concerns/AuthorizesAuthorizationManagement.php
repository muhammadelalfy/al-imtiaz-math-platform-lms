<?php

namespace App\Http\Requests\Concerns;

trait AuthorizesAuthorizationManagement
{
    public function authorize(): bool
    {
        return $this->user()?->can('authorization.manage') ?? false;
    }
}
