<?php

namespace App\Services;

use App\Contracts\Repositories\SubscriptionPackageRepositoryInterface;
use App\Exceptions\SubscriptionStorageException;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubscriptionPackageManagementService
{
    public function __construct(private readonly SubscriptionPackageRepositoryInterface $packages)
    {
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): SubscriptionPackage
    {
        try {
            return DB::transaction(fn (): SubscriptionPackage => $this->packages->create($attributes), 3);
        } catch (Throwable $exception) {
            throw new SubscriptionStorageException('تعذر إنشاء الباقة. لم يتم حفظ أي تغيير.', previous: $exception);
        }
    }

    /** @param array<string, mixed> $attributes */
    public function update(SubscriptionPackage $package, array $attributes): SubscriptionPackage
    {
        try {
            return DB::transaction(fn (): SubscriptionPackage => $this->packages->update($package, $attributes), 3);
        } catch (Throwable $exception) {
            throw new SubscriptionStorageException('تعذر تحديث الباقة. لم يتم حفظ أي تغيير.', previous: $exception);
        }
    }
}
