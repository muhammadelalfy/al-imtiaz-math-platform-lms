<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfflineSyncOperationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'operations' => ['required', 'array', 'min:1', 'max:50'],
            'operations.*.id' => ['required', 'uuid'],
            'operations.*.type' => [
                'required',
                'string',
                Rule::in([
                    'attendance.create',
                    'exam_result.create',
                    'payment.create',
                    'worksheet_submission.submit',
                ]),
            ],
            'operations.*.occurred_at' => ['required', 'date'],
            'operations.*.base_updated_at' => ['nullable', 'date'],
            'operations.*.data' => ['required', 'array'],
        ];
    }
}
