<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    public function rules(): array
    {
        return [
            'subscription_package_id' => ['sometimes', 'integer', Rule::exists('subscription_packages', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(['pending', 'active', 'past_due', 'cancelled', 'expired'])],
            'payment_status' => ['required', Rule::in(['unpaid', 'pending', 'paid', 'refunded'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'payment_reference' => ['nullable', 'string', 'max:160'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
