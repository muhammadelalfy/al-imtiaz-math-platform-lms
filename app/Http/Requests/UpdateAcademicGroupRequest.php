<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('groups.manage') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'grade' => ['sometimes', 'required', 'string', 'max:100'],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
