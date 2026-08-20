<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionBankQuestionRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return $this->questionRules(false);
    }

    /** @return array<string, array<int, string>> */
    protected function questionRules(bool $partial): array
    {
        $presence = $partial ? ['sometimes'] : [];

        return [
            'department_id' => [...$presence, 'nullable', 'exists:exam_departments,id'],
            'type' => [...$presence, 'required', 'in:mcq,true_false,essay,math,geometry'],
            'title' => [...$presence, 'nullable', 'string', 'max:255'],
            'grade' => [...$presence, 'nullable', 'string', 'max:255'],
            'prompt_html' => [...$presence, 'required', 'string'],
            'options' => [...$presence, 'nullable', 'array'],
            'correct_answer' => [...$presence, 'nullable', 'string'],
            'points' => [...$presence, 'required', 'integer', 'min:1', 'max:100'],
            'tags' => [...$presence, 'nullable', 'string', 'max:500'],
            'is_active' => [...$presence, 'boolean'],
        ];
    }
}
