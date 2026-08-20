<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\SubscriptionPackageRepositoryInterface;
use App\Contracts\Repositories\TenantSubscriptionRepositoryInterface;
use App\Exceptions\SubscriptionStorageException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionPackageRequest;
use App\Http\Requests\UpdateSubscriptionPackageRequest;
use App\Http\Requests\UpdateTenantDomainRequest;
use App\Http\Requests\UpdateTenantSubscriptionRequest;
use App\Http\Resources\SubscriptionPackageResource;
use App\Http\Resources\TenantResource;
use App\Http\Resources\TenantSubscriptionResource;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\PlatformOverviewService;
use App\Services\SubscriptionLifecycleService;
use App\Services\SubscriptionPackageManagementService;
use App\Services\TenantDomainService;
use Illuminate\Http\Request;

class SuperAdminSubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionPackageRepositoryInterface $packages,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly SubscriptionLifecycleService $lifecycle,
        private readonly SubscriptionPackageManagementService $packageManagement,
        private readonly TenantDomainService $domains,
        private readonly PlatformOverviewService $overview,
    ) {
    }

    public function overview(Request $request)
    {
        $this->authorizeSuperAdmin($request);
        return $this->overview->summary();
    }

    public function packages(Request $request)
    {
        $this->authorizeSuperAdmin($request);
        return SubscriptionPackageResource::collection($this->packages->allForAdministration());
    }

    public function storePackage(StoreSubscriptionPackageRequest $request)
    {
        try {
            $package = $this->packageManagement->create($request->validated());
            return (new SubscriptionPackageResource($package))->response()->setStatusCode(201);
        } catch (SubscriptionStorageException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function updatePackage(UpdateSubscriptionPackageRequest $request, SubscriptionPackage $subscriptionPackage)
    {
        try {
            return new SubscriptionPackageResource($this->packageManagement->update($subscriptionPackage, $request->validated()));
        } catch (SubscriptionStorageException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function subscriptions(Request $request)
    {
        $this->authorizeSuperAdmin($request);
        return TenantSubscriptionResource::collection($this->subscriptions->paginateForAdministration());
    }

    public function updateSubscription(UpdateTenantSubscriptionRequest $request, TenantSubscription $tenantSubscription)
    {
        try {
            return new TenantSubscriptionResource($this->lifecycle->updateSubscription($tenantSubscription->id, $request->validated(), $request->user()));
        } catch (SubscriptionStorageException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function updateTenantDomain(UpdateTenantDomainRequest $request, Tenant $tenant)
    {
        return new TenantResource($this->domains->updateDomain($tenant, $request->validated('login_domain')));
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->is_super_admin === true, 403);
    }
}
