<?php

namespace Modules\Exams\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Exams\Contracts\ExamBoundary;
use Modules\Exams\Services\LocalExamBoundary;

final class ExamsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExamBoundary::class, LocalExamBoundary::class);
    }
}
