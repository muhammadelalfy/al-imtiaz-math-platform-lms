<?php

namespace App\Repositories;

use App\Contracts\Repositories\SubscriptionPackageRepositoryInterface;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Collection;

class EloquentSubscriptionPackageRepository implements SubscriptionPackageRepositoryInterface
{
    public function activeCatalog(): Collection
    {
        return SubscriptionPackage::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    public function allForAdministration(): Collection
    {
        return SubscriptionPackage::query()->withCount('subscriptions')->orderBy('sort_order')->orderBy('id')->get();
    }

    public function findActiveOrFail(int $id): SubscriptionPackage
    {
        return SubscriptionPackage::query()->where('is_active', true)->findOrFail($id);
    }

    public function create(array $attributes): SubscriptionPackage
    {
        return SubscriptionPackage::query()->create($attributes);
    }

    public function update(SubscriptionPackage $package, array $attributes): SubscriptionPackage
    {
        $package->update($attributes);

        return $package->refresh();
    }
}
