<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'alpha_dash', 'max:60', 'unique:subscription_packages,code'],
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'duration_days' => ['required', 'integer', 'between:1,730'],
            'teacher_limit' => ['required', 'integer', 'between:1,100'],
            'student_limit' => ['required', 'integer', 'between:1,100000'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['string', 'max:180'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
        ];
    }
}
