<?php

namespace App\Http\Requests;

use App\Models\WorksheetAssignment;
use Illuminate\Foundation\Http\FormRequest;

class SubmitWorksheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $assignment = $this->route('assignment');

        if (!$user || !$assignment instanceof WorksheetAssignment) {
            return false;
        }

        $account = $user->loadMissing('studentAccount')->studentAccount;

        return $user->isAnyRole('admin', 'teacher') || ($account && $account->student_id === $assignment->student_id);
    }

    public function rules(): array
    {
        return [
            'score' => ['nullable', 'integer', 'min:0'],
            'max_score' => ['nullable', 'integer', 'min:1'],
            'feedback' => ['nullable', 'string'],
        ];
    }
}
