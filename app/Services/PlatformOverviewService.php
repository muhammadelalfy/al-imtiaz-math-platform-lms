<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PlatformOverviewService
{
    /** @return array<string, mixed> */
    public function summary(): array
    {
        $summaryStartedAt = hrtime(true);
        $startedAt = hrtime(true);
        DB::select('select 1');
        $databaseLatencyMs = round((hrtime(true) - $startedAt) / 1_000_000, 2);
        $pendingJobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return [
            'health' => [
                'database' => 'healthy',
                'database_latency_ms' => $databaseLatencyMs,
                'storage' => Storage::disk('local')->exists('.') ? 'healthy' : 'attention',
                'php_version' => PHP_VERSION,
            ],
            'queue' => [
                'driver' => (string) config('queue.default'),
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ],
            'runtime' => [
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1_048_576, 1),
                'summary_duration_ms' => round((hrtime(true) - $summaryStartedAt) / 1_000_000, 2),
                'observed_at' => now()->toIso8601String(),
            ],
            'counts' => [
                'tenants' => Tenant::query()->count(),
                'teachers' => User::query()->where('role', 'teacher')->count(),
                'students' => Student::query()->count(),
                'active_subscriptions' => TenantSubscription::query()->where('status', 'active')->count(),
                'paid_subscriptions' => TenantSubscription::query()->where('payment_status', 'paid')->count(),
                'unpaid_subscriptions' => TenantSubscription::query()->whereIn('payment_status', ['unpaid', 'pending'])->count(),
                'expiring_within_week' => TenantSubscription::query()->where('status', 'active')->whereBetween('ends_at', [now(), now()->addDays(7)])->count(),
            ],
        ];
    }
}
