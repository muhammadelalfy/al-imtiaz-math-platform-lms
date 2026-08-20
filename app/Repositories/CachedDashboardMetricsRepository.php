<?php

namespace App\Repositories;

use App\Contracts\Repositories\DashboardMetricsCacheInterface;
use App\Contracts\Repositories\DashboardMetricsRepositoryInterface;
use App\Models\AttendanceRecord;
use App\Models\ExamResult;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;

final class CachedDashboardMetricsRepository implements DashboardMetricsRepositoryInterface, DashboardMetricsCacheInterface
{
    private const CACHE_KEY = 'lms:dashboard-metrics:v1';

    public function summary(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): array {
            $attendance = AttendanceRecord::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($total) => (int) $total)
                ->all();

            $exam = ExamResult::query()
                ->selectRaw('coalesce(sum(score), 0) as score, coalesce(sum(max_score), 0) as max_score')
                ->first();

            return [
                'students' => Student::query()->count(),
                'attendance' => $attendance,
                'exams' => [
                    'score' => (int) $exam->score,
                    'max_score' => (int) $exam->max_score,
                ],
                'payments' => Payment::query()
                    ->selectRaw('status, coalesce(sum(amount), 0) as amount, count(*) as total')
                    ->groupBy('status')
                    ->get()
                    ->map(fn (Payment $payment) => [
                        'status' => $payment->status,
                        'amount' => (int) $payment->amount,
                        'total' => (int) $payment->total,
                    ])
                    ->all(),
            ];
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
