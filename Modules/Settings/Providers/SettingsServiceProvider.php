<?php

namespace Modules\Settings\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Settings\Contracts\SettingsBoundary;
use Modules\Settings\Services\LocalSettingsBoundary;

final class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsBoundary::class, LocalSettingsBoundary::class);
    }
}
