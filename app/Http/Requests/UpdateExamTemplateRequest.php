<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExamTemplateRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1', 'max:600'],
            'instructions' => ['nullable', 'string'],
            'watermark_text' => ['nullable', 'string', 'max:255'],
            'watermark_opacity' => ['nullable', 'integer', 'min:0', 'max:50'],
            'print_header' => ['nullable', 'string', 'max:255'],
            'print_footer' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'questions' => ['sometimes', 'array'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.type' => ['required_with:questions', 'in:mcq,true_false,essay,math,geometry'],
            'questions.*.prompt_html' => ['required_with:questions', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.points' => ['required_with:questions', 'integer', 'min:1', 'max:100'],
            'questions.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
