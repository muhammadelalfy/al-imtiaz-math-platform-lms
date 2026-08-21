<?php

namespace App\Http\Requests;

use App\Services\TeacherDashboardLayoutService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherDashboardLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof \App\Models\User && $user->role === 'teacher';
    }

    public function rules(): array
    {
        return [
            'card_order' => ['required', 'array', 'size:'.count(TeacherDashboardLayoutService::DEFAULT_CARD_ORDER)],
            'card_order.*' => ['required', 'string', 'distinct', Rule::in(TeacherDashboardLayoutService::DEFAULT_CARD_ORDER)],
        ];
    }
}
