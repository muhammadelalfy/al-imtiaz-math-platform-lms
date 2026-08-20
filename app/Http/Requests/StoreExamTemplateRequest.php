<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamTemplateRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'exists:exam_departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'instructions' => ['nullable', 'string'],
            'watermark_text' => ['nullable', 'string', 'max:255'],
            'watermark_opacity' => ['nullable', 'integer', 'min:0', 'max:50'],
            'print_header' => ['nullable', 'string', 'max:255'],
            'print_footer' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'questions' => ['array'],
            'questions.*.type' => ['required', 'in:mcq,true_false,essay,math,geometry'],
            'questions.*.prompt_html' => ['required', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
