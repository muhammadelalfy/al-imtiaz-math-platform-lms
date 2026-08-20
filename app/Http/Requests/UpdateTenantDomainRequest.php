<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    public function rules(): array
    {
        return [
            'login_domain' => ['nullable', 'string', 'max:190', 'regex:/^(?=.{1,190}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/'],
        ];
    }
}
