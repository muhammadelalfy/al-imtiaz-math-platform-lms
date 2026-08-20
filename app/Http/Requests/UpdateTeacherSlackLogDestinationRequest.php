<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherSlackLogDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAnyRole('teacher') ?? false;
    }

    public function rules(): array
    {
        return [
            'channel_label' => ['nullable', 'string', 'max:100'],
            'webhook_url' => ['nullable', 'string', 'max:2048'],
            'is_enabled' => ['required', 'boolean'],
        ];
    }
}
