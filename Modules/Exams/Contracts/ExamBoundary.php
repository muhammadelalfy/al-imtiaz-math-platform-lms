<?php

namespace Modules\Exams\Contracts;

interface ExamBoundary
{
    /** @return array{module: string, owns: array<int, string>, transport: string} */
    public function descriptor(): array;
}
