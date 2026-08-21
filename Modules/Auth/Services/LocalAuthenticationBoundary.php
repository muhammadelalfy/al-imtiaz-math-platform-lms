<?php

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\AuthenticationBoundary;

final class LocalAuthenticationBoundary implements AuthenticationBoundary
{
    public function descriptor(): array
    {
        return [
            'module' => 'Auth',
            'owns' => ['session authentication', 'role portal authentication', 'authenticated identity'],
            'transport' => 'local-laravel',
        ];
    }
}
