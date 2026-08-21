<?php

namespace Modules\Auth\Contracts;

interface AuthenticationBoundary
{
    /** @return array{module: string, owns: array<int, string>, transport: string} */
    public function descriptor(): array;
}
