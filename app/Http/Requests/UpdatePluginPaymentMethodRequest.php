<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePluginPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAnyRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'is_enabled' => ['required', 'boolean'],
        ];
    }
}
