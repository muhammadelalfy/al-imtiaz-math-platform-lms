<?php

namespace App\Providers;

use App\Contracts\Repositories\DashboardMetricsCacheInterface;
use App\Contracts\Repositories\DashboardMetricsRepositoryInterface;
use App\Contracts\Repositories\AcademicGroupRepositoryInterface;
use App\Contracts\Repositories\ExamTemplateRepositoryInterface;
use App\Contracts\Repositories\PluginStoreRepositoryInterface;
use App\Contracts\Repositories\QuestionBankRepositoryInterface;
use App\Contracts\Repositories\StudentRepositoryInterface;
use App\Contracts\Repositories\TeacherSlackLogDestinationRepositoryInterface;
use App\Contracts\Repositories\SubscriptionPackageRepositoryInterface;
use App\Contracts\Repositories\TenantSubscriptionRepositoryInterface;
use App\Contracts\Repositories\WorksheetRepositoryInterface;
use App\Contracts\Observability\CacheObservabilityInterface;
use App\Contracts\Notifications\NotificationChannelDispatcherInterface;
use App\Contracts\Services\OfflineSyncServiceInterface;
use App\Repositories\CachedDashboardMetricsRepository;
use App\Repositories\EloquentAcademicGroupRepository;
use App\Repositories\EloquentExamTemplateRepository;
use App\Repositories\EloquentPluginStoreRepository;
use App\Repositories\EloquentQuestionBankRepository;
use App\Repositories\EloquentStudentRepository;
use App\Repositories\EloquentTeacherSlackLogDestinationRepository;
use App\Repositories\EloquentSubscriptionPackageRepository;
use App\Repositories\EloquentTenantSubscriptionRepository;
use App\Repositories\EloquentWorksheetRepository;
use App\Services\LogCacheObservability;
use App\Services\OfflineSyncService;
use App\Services\NotificationChannels\InAppNotificationChannel;
use App\Services\NotificationChannels\TwilioSmsNotificationChannel;
use App\Services\NotificationChannels\WhatsAppCloudNotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AcademicGroupRepositoryInterface::class, EloquentAcademicGroupRepository::class);
        $this->app->bind(StudentRepositoryInterface::class, EloquentStudentRepository::class);
        $this->app->bind(ExamTemplateRepositoryInterface::class, EloquentExamTemplateRepository::class);
        $this->app->bind(QuestionBankRepositoryInterface::class, EloquentQuestionBankRepository::class);
        $this->app->bind(PluginStoreRepositoryInterface::class, EloquentPluginStoreRepository::class);
        $this->app->bind(WorksheetRepositoryInterface::class, EloquentWorksheetRepository::class);
        $this->app->bind(TeacherSlackLogDestinationRepositoryInterface::class, EloquentTeacherSlackLogDestinationRepository::class);
        $this->app->bind(SubscriptionPackageRepositoryInterface::class, EloquentSubscriptionPackageRepository::class);
        $this->app->bind(TenantSubscriptionRepositoryInterface::class, EloquentTenantSubscriptionRepository::class);
        $this->app->bind(OfflineSyncServiceInterface::class, OfflineSyncService::class);
        $this->app->singleton(CacheObservabilityInterface::class, LogCacheObservability::class);

        $this->app->singleton(CachedDashboardMetricsRepository::class);
        $this->app->bind(DashboardMetricsRepositoryInterface::class, fn ($app) => $app->make(CachedDashboardMetricsRepository::class));
        $this->app->bind(DashboardMetricsCacheInterface::class, fn ($app) => $app->make(CachedDashboardMetricsRepository::class));
        $this->app->tag([InAppNotificationChannel::class, WhatsAppCloudNotificationChannel::class, TwilioSmsNotificationChannel::class], NotificationChannelDispatcherInterface::class);
        $this->app->bind(\App\Services\NotificationChannelDeliveryService::class, fn ($app) => new \App\Services\NotificationChannelDeliveryService(
            $app->tagged(NotificationChannelDispatcherInterface::class),
            $app->make(\App\Services\NotificationChannelConfigurationService::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading();
        JsonResource::withoutWrapping();

        Gate::before(static fn (User $user): ?bool => $user->isAnyRole('admin') ? true : null);
    }
}
