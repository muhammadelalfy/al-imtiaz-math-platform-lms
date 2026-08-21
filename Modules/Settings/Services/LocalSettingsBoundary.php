<?php

namespace Modules\Settings\Services;

use Modules\Settings\Contracts\SettingsBoundary;

final class LocalSettingsBoundary implements SettingsBoundary
{
    public function descriptor(): array
    {
        return [
            'module' => 'Settings',
            'owns' => ['academy identity', 'notification channels', 'dashboard preferences'],
            'transport' => 'local-laravel',
        ];
    }
}
