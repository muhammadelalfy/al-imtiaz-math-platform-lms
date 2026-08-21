<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Contracts\AuthenticationBoundary;
use Modules\Auth\Services\LocalAuthenticationBoundary;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Auth', 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->singleton(AuthenticationBoundary::class, LocalAuthenticationBoundary::class);
    }
}
