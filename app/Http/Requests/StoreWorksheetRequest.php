<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorksheetRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'subject' => ['required', 'string', 'max:80'],
            'grade' => ['required', 'string', 'max:80'],
            'instructions' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,published'],
        ];
    }
}
