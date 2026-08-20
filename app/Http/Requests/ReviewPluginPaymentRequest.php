<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewPluginPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAnyRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['review_note' => ['nullable', 'string', 'max:500']];
    }
}
