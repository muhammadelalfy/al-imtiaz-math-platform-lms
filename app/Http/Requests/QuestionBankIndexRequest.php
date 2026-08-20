<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class QuestionBankIndexRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:mcq,true_false,essay,math,geometry'],
            'grade' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
