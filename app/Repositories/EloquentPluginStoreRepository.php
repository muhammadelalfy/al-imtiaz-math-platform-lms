<?php

namespace App\Repositories;

use App\Contracts\Repositories\PluginStoreRepositoryInterface;
use App\Models\InstalledModule;
use App\Models\PluginProduct;
use App\Models\PluginPurchase;
use App\Models\User;
use Illuminate\Support\Collection;

final class EloquentPluginStoreRepository implements PluginStoreRepositoryInterface
{
    public function catalogFor(User $user): Collection
    {
        return PluginProduct::query()
            ->where('is_active', true)
            ->with([
                'purchases' => fn ($query) => $query->where('user_id', $user->id)->where('status', 'completed'),
                'installations' => fn ($query) => $query->where('status', 'installed'),
                'paymentTransactions' => fn ($query) => $query->where('user_id', $user->id)->whereIn('status', ['pending', 'submitted'])->latest(),
            ])
            ->orderBy('name')
            ->get()
            ->each(function (PluginProduct $plugin): void {
                $installation = $plugin->installations->first();
                $payment = $plugin->paymentTransactions->first();

                $plugin->setAttribute('purchased', $plugin->purchases->isNotEmpty());
                $plugin->setAttribute('installed', $installation !== null);
                $plugin->setAttribute('installed_module', $installation?->getAttribute('module_name'));
                $plugin->setAttribute('payment_status', $payment?->getAttribute('status'));
            });
    }

    public function installed(): Collection
    {
        return InstalledModule::query()->with('plugin')->latest('installed_at')->get();
    }

    public function purchase(User $user, PluginProduct $plugin): PluginPurchase
    {
        $purchase = PluginPurchase::query()->firstOrCreate(
            ['user_id' => $user->id, 'plugin_product_id' => $plugin->id],
            ['status' => 'completed', 'purchased_at' => now()],
        );

        return $purchase->load('plugin');
    }

    public function hasCompletedPurchase(User $user, PluginProduct $plugin): bool
    {
        return $user->pluginPurchases()
            ->where('plugin_product_id', $plugin->id)
            ->where('status', 'completed')
            ->exists();
    }

    public function installedModuleFor(PluginProduct $plugin): InstalledModule
    {
        return InstalledModule::query()->where('plugin_product_id', $plugin->id)->firstOrFail();
    }

    public function markUninstalled(InstalledModule $module): void
    {
        $module->update(['status' => 'uninstalled']);
    }
}
