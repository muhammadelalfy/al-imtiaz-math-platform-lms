<?php

namespace Modules\Ui\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Ui\Contracts\UiBoundary;
use Modules\Ui\Services\LocalUiBoundary;

final class UiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UiBoundary::class, LocalUiBoundary::class);
    }
}
