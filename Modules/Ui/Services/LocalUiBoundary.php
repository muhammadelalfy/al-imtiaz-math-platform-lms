<?php

namespace Modules\Ui\Services;

use Modules\Ui\Contracts\UiBoundary;

final class LocalUiBoundary implements UiBoundary
{
    public function descriptor(): array
    {
        return [
            'module' => 'Ui',
            'owns' => ['RTL shell', 'design tokens', 'request feedback', 'shared dashboard cards'],
            'transport' => 'local-vite',
        ];
    }
}
