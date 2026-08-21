<?php

namespace Modules\Platform\Services;

use Modules\Platform\Contracts\PlatformControlPlaneBoundary;

final class LocalPlatformControlPlaneBoundary implements PlatformControlPlaneBoundary
{
    public function descriptor(): array
    {
        return [
            'module' => 'Platform',
            'owns' => ['super-admin control plane', 'tenant lifecycle', 'subscription packages', 'platform health'],
            'transport' => 'local-laravel',
        ];
    }
}
