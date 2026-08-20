<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class AssignWorksheetRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ];
    }
}
