<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'group' => ['required', 'string', 'max:32'],
            'grade' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:32'],
            'parent_phone' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'in:excellent,average,weak'],
        ];
    }
}
