<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('notifications.send') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'audience' => ['required', Rule::in(['all_parents', 'all_students', 'selected', 'grade', 'academic_group'])],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
            'grade' => ['nullable', 'required_if:audience,grade', 'string', 'max:100'],
            'academic_group_id' => ['nullable', 'required_if:audience,academic_group', 'integer', Rule::exists('academic_groups', 'id')],
            'recipient_ids' => ['nullable', 'required_if:audience,selected', 'array', 'max:500'],
            'recipient_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
            'channels' => ['sometimes', 'array', 'min:1'],
            'channels.*' => ['string', 'distinct', Rule::in(['in_app', 'whatsapp', 'sms'])],
        ];
    }
}
