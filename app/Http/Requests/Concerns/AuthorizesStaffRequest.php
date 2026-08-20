<?php

namespace App\Http\Requests\Concerns;

trait AuthorizesStaffRequest
{
    protected function isStaff(): bool
    {
        return $this->user()?->isAnyRole('admin', 'teacher') ?? false;
    }
}
