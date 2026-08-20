<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'amount' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:pending,paid,overdue'],
            'due_at' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
