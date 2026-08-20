<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExamResultRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:180'],
            'score' => ['sometimes', 'integer', 'min:0'],
            'max_score' => ['sometimes', 'integer', 'min:1'],
            'taken_at' => ['sometimes', 'date'],
        ];
    }
}
