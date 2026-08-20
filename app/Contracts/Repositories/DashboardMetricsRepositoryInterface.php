<?php

namespace App\Contracts\Repositories;

interface DashboardMetricsRepositoryInterface
{
    /** @return array<string, mixed> */
    public function summary(): array;
}
