<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesStaffRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    use AuthorizesStaffRequest;

    public function authorize(): bool
    {
        return $this->isStaff();
    }

    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:pending,paid,overdue'],
            'due_at' => ['sometimes', 'date'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
