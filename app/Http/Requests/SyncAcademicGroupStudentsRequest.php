<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncAcademicGroupStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('groups.manage') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'student_ids' => ['present', 'array', 'max:500'],
            'student_ids.*' => ['integer', 'distinct', Rule::exists('students', 'id')],
        ];
    }
}
