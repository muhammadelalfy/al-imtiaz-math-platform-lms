<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherAcademyIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof \App\Models\User
            && $user->role === 'teacher'
            && $user->tenant_id !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'academy_name' => trim((string) $this->input('academy_name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'academy_name' => ['required', 'string', 'min:2', 'max:120'],
        ];
    }
}
