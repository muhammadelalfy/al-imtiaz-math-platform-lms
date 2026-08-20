<?php

namespace App\Contracts\Repositories;

use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TenantSubscriptionRepositoryInterface
{
    public function currentForUser(User $user): ?TenantSubscription;

    public function findForUpdate(int $id): TenantSubscription;

    /** @return LengthAwarePaginator<int, TenantSubscription> */
    public function paginateForAdministration(int $perPage = 20): LengthAwarePaginator;
}
