<?php

namespace Modules\Teacher\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Teacher\Contracts\TeacherOnboardingBoundary;
use Modules\Teacher\Services\LocalTeacherOnboardingBoundary;

final class TeacherServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Teacher', 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->singleton(TeacherOnboardingBoundary::class, LocalTeacherOnboardingBoundary::class);
    }
}
