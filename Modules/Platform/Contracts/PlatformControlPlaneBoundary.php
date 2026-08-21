<?php

namespace Modules\Platform\Contracts;

interface PlatformControlPlaneBoundary
{
    /** @return array{module: string, owns: array<int, string>, transport: string} */
    public function descriptor(): array;
}
