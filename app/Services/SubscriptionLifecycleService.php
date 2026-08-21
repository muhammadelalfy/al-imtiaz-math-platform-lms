<?php

namespace App\Services;

use App\Contracts\Repositories\SubscriptionPackageRepositoryInterface;
use App\Contracts\Repositories\TenantSubscriptionRepositoryInterface;
use App\Contracts\Services\TenantSchemaProvisionerInterface;
use App\Exceptions\SubscriptionStorageException;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubscriptionLifecycleService
{
    public function __construct(
        private readonly SubscriptionPackageRepositoryInterface $packages,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantSchemaProvisionerInterface $schemas,
        private readonly TenantDomainService $domains,
    ) {
    }

    /** @param array{name: string, email: string, password: string, organization_name: string, tenant_slug: string, package_id: int} $attributes */
    public function registerTeacher(array $attributes): User
    {
        try {
            return DB::transaction(function () use ($attributes): User {
                $package = $this->packages->findActiveOrFail($attributes['package_id']);
                $tenant = Tenant::query()->create([
                    'name' => $attributes['organization_name'],
                    'slug' => $attributes['tenant_slug'],
                    'domain_status' => 'pending',
                ]);
                $teacher = User::query()->create([
                    'name' => $attributes['name'],
                    'email' => $attributes['email'],
                    'password' => $attributes['password'],
                    'role' => 'teacher',
                    'tenant_id' => $tenant->id,
                ]);
                TenantSubscription::query()->create([
                    'tenant_id' => $tenant->id,
                    'subscription_package_id' => $package->id,
                    'status' => 'pending',
                    'payment_status' => $package->price_cents === 0 ? 'paid' : 'unpaid',
                    'paid_at' => $package->price_cents === 0 ? now() : null,
                ]);

                return $teacher->load('tenant');
            }, 3);
        } catch (Throwable $exception) {
            throw new SubscriptionStorageException('تعذر إنشاء اشتراك المركز. حاول مرة أخرى.', previous: $exception);
        }
    }

    /** @param array{subscription_package_id?: int, payment_status: string, status: string, starts_at?: string|null, ends_at?: string|null, payment_reference?: string|null, admin_note?: string|null} $attributes */
    public function updateSubscription(int $subscriptionId, array $attributes, User $superAdmin): TenantSubscription
    {
        try {
            $subscription = DB::transaction(function () use ($subscriptionId, $attributes, $superAdmin): TenantSubscription {
                $subscription = $this->subscriptions->findForUpdate($subscriptionId);
                $package = isset($attributes['subscription_package_id'])
                    ? $this->packages->findActiveOrFail($attributes['subscription_package_id'])
                    : $subscription->getRelation('package');
                if (! $package instanceof SubscriptionPackage) {
                    throw new SubscriptionStorageException('تعذر تحديد باقة الاشتراك.');
                }
                $existingStart = $subscription->getAttribute('starts_at');
                $existingEnd = $subscription->getAttribute('ends_at');
                $startsAt = isset($attributes['starts_at']) && $attributes['starts_at']
                    ? CarbonImmutable::parse($attributes['starts_at'])
                    : ($existingStart instanceof CarbonInterface ? CarbonImmutable::instance($existingStart) : now()->toImmutable());
                $isActive = $attributes['status'] === 'active';
                $endsAt = isset($attributes['ends_at']) && $attributes['ends_at']
                    ? CarbonImmutable::parse($attributes['ends_at'])
                    : ($isActive ? $startsAt->addDays($package->getAttribute('duration_days')) : ($existingEnd instanceof CarbonInterface ? CarbonImmutable::instance($existingEnd) : null));

                $subscription->fill([
                    'subscription_package_id' => $package->id,
                    'status' => $attributes['status'],
                    'payment_status' => $attributes['payment_status'],
                    'starts_at' => $isActive ? $startsAt : $subscription->starts_at,
                    'ends_at' => $endsAt,
                    'paid_at' => $attributes['payment_status'] === 'paid' ? ($subscription->paid_at ?? now()) : null,
                    'payment_reference' => $attributes['payment_reference'] ?? null,
                    'admin_note' => $attributes['admin_note'] ?? null,
                    'activated_by' => $isActive ? $superAdmin->id : $subscription->activated_by,
                ])->save();

                return $subscription->refresh()->load(['tenant', 'package', 'activatedBy']);
            }, 3);

            if ($subscription->status === 'active' && $subscription->payment_status === 'paid') {
                $tenant = $subscription->getRelation('tenant');
                if ($tenant instanceof Tenant) {
                    $this->domains->assignSubscriptionDomain($this->schemas->provision($tenant));
                }
            }

            return $subscription->refresh()->load(['tenant', 'package', 'activatedBy']);
        } catch (Throwable $exception) {
            throw new SubscriptionStorageException('تعذر حفظ حالة الاشتراك. لم يتم تطبيق أي تغيير.', previous: $exception);
        }
    }

    /** @return array{subscription: TenantSubscription|null, show_expiry_reminder: bool} */
    public function teacherSubscription(User $teacher): array
    {
        $subscription = $this->subscriptions->currentForUser($teacher);
        $endsAt = $subscription?->getAttribute('ends_at');
        if (! $subscription || ! $endsAt instanceof CarbonInterface || $subscription->status !== 'active') {
            return ['subscription' => $subscription, 'show_expiry_reminder' => false];
        }

        $remainingDays = now()->startOfDay()->diffInDays($endsAt->startOfDay(), false);
        $shouldShow = $remainingDays >= 0 && $remainingDays <= 7 && $subscription->reminder_shown_at === null;
        if ($shouldShow) {
            DB::transaction(function () use ($subscription): void {
                TenantSubscription::query()->whereKey($subscription->getKey())->whereNull('reminder_shown_at')
                    ->update(['reminder_shown_at' => now()]);
            }, 3);
            $subscription->refresh();
        }

        return ['subscription' => $subscription, 'show_expiry_reminder' => $shouldShow];
    }
}
