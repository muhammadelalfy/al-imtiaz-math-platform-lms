<?php

namespace App\Contracts\Services;

use App\Models\Tenant;

interface TenantSchemaProvisionerInterface
{
    public function provision(Tenant $tenant): Tenant;

    public function isReady(Tenant $tenant): bool;
}
