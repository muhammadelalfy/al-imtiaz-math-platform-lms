<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:160'],
            'group' => ['sometimes', 'string', 'max:32'],
            'grade' => ['sometimes', 'string', 'max:80'],
            'phone' => ['sometimes', 'string', 'max:32'],
            'parent_phone' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'in:excellent,average,weak'],
        ];
    }
}
