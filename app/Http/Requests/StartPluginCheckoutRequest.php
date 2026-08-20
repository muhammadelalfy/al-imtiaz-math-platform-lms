<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartPluginCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['payment_method' => ['required', 'in:vodafone_cash,instapay,fawry']];
    }
}
