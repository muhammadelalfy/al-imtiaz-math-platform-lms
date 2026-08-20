<?php

namespace App\Repositories;

use App\Contracts\Repositories\TenantSubscriptionRepositoryInterface;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentTenantSubscriptionRepository implements TenantSubscriptionRepositoryInterface
{
    public function currentForUser(User $user): ?TenantSubscription
    {
        if (! $user->tenant_id) {
            return null;
        }

        return TenantSubscription::query()->with(['tenant', 'package'])
            ->where('tenant_id', $user->tenant_id)->latest('id')->first();
    }

    public function findForUpdate(int $id): TenantSubscription
    {
        return TenantSubscription::query()->with(['tenant', 'package'])->lockForUpdate()->findOrFail($id);
    }

    public function paginateForAdministration(int $perPage = 20): LengthAwarePaginator
    {
        return TenantSubscription::query()->with(['tenant', 'package', 'activatedBy'])->latest('id')->paginate($perPage);
    }
}
