<?php

namespace Modules\Teacher\Services;

use Modules\Teacher\Contracts\TeacherOnboardingBoundary;

final class LocalTeacherOnboardingBoundary implements TeacherOnboardingBoundary
{
    public function descriptor(): array
    {
        return [
            'module' => 'Teacher',
            'owns' => ['teacher registration', 'tenant onboarding', 'teacher subscription workspace'],
            'transport' => 'local-laravel',
        ];
    }
}
