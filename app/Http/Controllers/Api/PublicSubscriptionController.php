<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\SubscriptionPackageRepositoryInterface;
use App\Exceptions\SubscriptionStorageException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterTenantTeacherRequest;
use App\Http\Resources\SubscriptionPackageResource;
use App\Http\Resources\TenantSubscriptionResource;
use App\Services\SubscriptionLifecycleService;
use Illuminate\Http\Request;

class PublicSubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionPackageRepositoryInterface $packages,
        private readonly SubscriptionLifecycleService $subscriptions,
    ) {
    }

    public function packages()
    {
        return SubscriptionPackageResource::collection($this->packages->activeCatalog());
    }

    public function registerTeacher(RegisterTenantTeacherRequest $request)
    {
        try {
            $teacher = $this->subscriptions->registerTeacher($request->validated());

            return response()->json([
                'user' => $teacher->only(['id', 'name', 'email', 'role', 'tenant_id']),
                'message' => 'تم إنشاء حساب المركز. سيظهر الاشتراك كقيد المراجعة حتى الاعتماد.',
            ], 201);
        } catch (SubscriptionStorageException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function mockRegistration(Request $request)
    {
        abort_unless(config('tenancy.mode') === 'shared_development', 404);

        try {
            $subscription = $this->subscriptions->createDevelopmentMockTenant();

            return response()->json([
                'message' => 'تم إنشاء مركز تجريبي وتجهيز مسار بياناته في بيئة Manus المشتركة.',
                'development_only' => true,
                'subscription' => (new TenantSubscriptionResource($subscription))->toArray($request),
            ], 201);
        } catch (SubscriptionStorageException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }
}
