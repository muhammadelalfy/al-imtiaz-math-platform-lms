<?php

namespace App\Contracts\Repositories;

interface DashboardMetricsCacheInterface
{
    public function forget(): void;
}
