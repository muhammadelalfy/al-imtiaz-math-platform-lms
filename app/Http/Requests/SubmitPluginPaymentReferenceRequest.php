<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPluginPaymentReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'min:5', 'max:160', 'unique:plugin_payment_transactions,reference'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
