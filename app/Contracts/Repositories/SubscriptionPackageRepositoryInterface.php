<?php

namespace App\Contracts\Repositories;

use App\Models\SubscriptionPackage;
use Illuminate\Support\Collection;

interface SubscriptionPackageRepositoryInterface
{
    /** @return Collection<int, SubscriptionPackage> */
    public function activeCatalog(): Collection;

    /** @return Collection<int, SubscriptionPackage> */
    public function allForAdministration(): Collection;

    public function findActiveOrFail(int $id): SubscriptionPackage;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): SubscriptionPackage;

    /** @param array<string, mixed> $attributes */
    public function update(SubscriptionPackage $package, array $attributes): SubscriptionPackage;
}
