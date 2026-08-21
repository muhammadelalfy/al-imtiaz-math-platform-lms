<?php

namespace Modules\Settings\Contracts;

interface SettingsBoundary
{
    /** @return array{module: string, owns: array<int, string>, transport: string} */
    public function descriptor(): array;
}
