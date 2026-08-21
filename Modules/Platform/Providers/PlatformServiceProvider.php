<?php

namespace Modules\Platform\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Platform\Contracts\PlatformControlPlaneBoundary;
use Modules\Platform\Services\LocalPlatformControlPlaneBoundary;

final class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlatformControlPlaneBoundary::class, LocalPlatformControlPlaneBoundary::class);
    }
}
