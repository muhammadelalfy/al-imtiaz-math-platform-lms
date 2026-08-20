<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesAuthorizationManagement;
use Illuminate\Foundation\Http\FormRequest;

class SyncStaffAuthorizationRolesRequest extends FormRequest
{
    use AuthorizesAuthorizationManagement;

    public function rules(): array
    {
        return [
            'role_ids' => ['present', 'array'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
        ];
    }
}
