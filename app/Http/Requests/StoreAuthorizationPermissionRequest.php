<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesAuthorizationManagement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuthorizationPermissionRequest extends FormRequest
{
    use AuthorizesAuthorizationManagement;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9]*(?:[.-][a-z0-9]+)*$/', Rule::unique('permissions', 'name')->where('guard_name', 'web')],
            'label' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
