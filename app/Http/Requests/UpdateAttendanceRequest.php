<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'date_at' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:present,absent,late'],
            'note' => ['nullable', 'string'],
        ];
    }
}
