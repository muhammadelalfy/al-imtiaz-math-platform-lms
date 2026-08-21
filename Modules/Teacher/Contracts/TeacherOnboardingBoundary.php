<?php

namespace Modules\Teacher\Contracts;

interface TeacherOnboardingBoundary
{
    /** @return array{module: string, owns: array<int, string>, transport: string} */
    public function descriptor(): array;
}
