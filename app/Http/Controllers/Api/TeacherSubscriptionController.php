<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantSubscriptionResource;
use App\Services\SubscriptionLifecycleService;
use Illuminate\Http\Request;

class TeacherSubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionLifecycleService $subscriptions)
    {
    }

    public function show(Request $request)
    {
        $result = $this->subscriptions->teacherSubscription($request->user());

        return [
            'subscription' => $result['subscription'] ? new TenantSubscriptionResource($result['subscription']) : null,
            'show_expiry_reminder' => $result['show_expiry_reminder'],
        ];
    }
}
