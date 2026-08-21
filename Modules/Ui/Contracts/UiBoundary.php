<?php

namespace Modules\Ui\Contracts;

interface UiBoundary
{
    /** @return array{module: string, owns: array<int, string>, transport: string} */
    public function descriptor(): array;
}
