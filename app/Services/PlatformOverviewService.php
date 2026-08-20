<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlatformOverviewService
{
    /** @return array<string, mixed> */
    public function summary(): array
    {
        $startedAt = hrtime(true);
        DB::select('select 1');
        $databaseLatencyMs = round((hrtime(true) - $startedAt) / 1_000_000, 2);

        return [
            'health' => [
                'database' => 'healthy',
                'database_latency_ms' => $databaseLatencyMs,
                'storage' => Storage::disk('local')->exists('.') ? 'healthy' : 'attention',
                'php_version' => PHP_VERSION,
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
