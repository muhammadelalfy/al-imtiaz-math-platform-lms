<?php

namespace Modules\Exams\Services;

use Modules\Exams\Contracts\ExamBoundary;

final class LocalExamBoundary implements ExamBoundary
{
    public function descriptor(): array
    {
        return [
            'module' => 'Exams',
            'owns' => ['question bank', 'exam authoring', 'exam sessions', 'exam exports'],
            'transport' => 'local-laravel',
        ];
    }
}
