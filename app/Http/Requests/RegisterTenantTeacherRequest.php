<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterTenantTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'organization_name' => ['required', 'string', 'max:140'],
            'tenant_slug' => ['required', 'alpha_dash', 'min:3', 'max:80', 'unique:tenants,slug'],
            'package_id' => ['required', 'integer', Rule::exists('subscription_packages', 'id')->where('is_active', true)],
        ];
    }
}
