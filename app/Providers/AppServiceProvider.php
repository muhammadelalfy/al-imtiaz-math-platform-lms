<?php

namespace App\Providers;

use App\Contracts\Repositories\DashboardMetricsCacheInterface;
use App\Contracts\Repositories\DashboardMetricsRepositoryInterface;
use App\Contracts\Repositories\ExamTemplateRepositoryInterface;
use App\Contracts\Repositories\StudentRepositoryInterface;
use App\Contracts\Repositories\WorksheetRepositoryInterface;
use App\Contracts\Observability\CacheObservabilityInterface;
use App\Repositories\CachedDashboardMetricsRepository;
use App\Repositories\EloquentExamTemplateRepository;
use App\Repositories\EloquentStudentRepository;
use App\Repositories\EloquentWorksheetRepository;
use App\Services\LogCacheObservability;
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
        $this->app->bind(ExamTemplateRepositoryInterface::class, EloquentExamTemplateRepository::class);
        $this->app->bind(WorksheetRepositoryInterface::class, EloquentWorksheetRepository::class);
        $this->app->singleton(CacheObservabilityInterface::class, LogCacheObservability::class);

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
