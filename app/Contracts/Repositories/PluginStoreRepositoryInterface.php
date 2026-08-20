<?php

namespace App\Contracts\Repositories;

use App\Models\InstalledModule;
use App\Models\PluginProduct;
use App\Models\PluginPurchase;
use App\Models\User;
use Illuminate\Support\Collection;

interface PluginStoreRepositoryInterface
{
    /** @return Collection<int, PluginProduct> */
    public function catalogFor(User $user): Collection;

    /** @return Collection<int, InstalledModule> */
    public function installed(): Collection;

    public function purchase(User $user, PluginProduct $plugin): PluginPurchase;

    public function hasCompletedPurchase(User $user, PluginProduct $plugin): bool;

    public function installedModuleFor(PluginProduct $plugin): InstalledModule;

    public function markUninstalled(InstalledModule $module): void;
}
