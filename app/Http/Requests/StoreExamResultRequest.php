<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamResultRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'title' => ['required', 'string', 'max:180'],
            'score' => ['required', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'min:1'],
            'taken_at' => ['required', 'date'],
        ];
    }
}
