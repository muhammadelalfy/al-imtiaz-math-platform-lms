<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesAuthorizationManagement;
use App\Models\AuthorizationPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuthorizationPermissionRequest extends FormRequest
{
    use AuthorizesAuthorizationManagement;

    public function rules(): array
    {
        /** @var AuthorizationPermission $permission */
        $permission = $this->route('permission');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9]*(?:[.-][a-z0-9]+)*$/', Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permission)],
            'label' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
