<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationChannelSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('notifications.channels.manage') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'is_enabled' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'array'],
            'settings.sender_label' => ['sometimes', 'nullable', 'string', 'max:80'],
            'settings.template_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'settings.auto_create_group' => ['sometimes', 'boolean'],
        ];
    }
}
