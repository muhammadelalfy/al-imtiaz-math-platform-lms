<?php

namespace App\Providers;

use App\Contracts\Repositories\DashboardMetricsCacheInterface;
use App\Contracts\Repositories\DashboardMetricsRepositoryInterface;
use App\Contracts\Repositories\StudentRepositoryInterface;
use App\Repositories\CachedDashboardMetricsRepository;
use App\Repositories\EloquentStudentRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StudentRepositoryInterface::class, EloquentStudentRepository::class);

        $this->app->singleton(CachedDashboardMetricsRepository::class);
        $this->app->bind(DashboardMetricsRepositoryInterface::class, fn ($app) => $app->make(CachedDashboardMetricsRepository::class));
        $this->app->bind(DashboardMetricsCacheInterface::class, fn ($app) => $app->make(CachedDashboardMetricsRepository::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading();
        JsonResource::withoutWrapping();
    }
}
