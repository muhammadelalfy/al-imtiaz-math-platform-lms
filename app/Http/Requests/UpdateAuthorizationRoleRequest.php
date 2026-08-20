<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesAuthorizationManagement;
use App\Models\AuthorizationRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuthorizationRoleRequest extends FormRequest
{
    use AuthorizesAuthorizationManagement;

    public function rules(): array
    {
        /** @var AuthorizationRole $role */
        $role = $this->route('role');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9-]*$/', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role)],
            'label' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ];
    }
}
