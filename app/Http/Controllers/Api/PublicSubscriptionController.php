<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\SubscriptionPackageRepositoryInterface;
use App\Exceptions\SubscriptionStorageException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterTenantTeacherRequest;
use App\Http\Resources\SubscriptionPackageResource;
use App\Services\SubscriptionLifecycleService;

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
}
