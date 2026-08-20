<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\OfflineSyncServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfflineSyncOperationsRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    public function snapshot(Request $request, OfflineSyncServiceInterface $offlineSync): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json(['data' => $offlineSync->snapshot($user)]);
    }

    public function reconcile(StoreOfflineSyncOperationsRequest $request, OfflineSyncServiceInterface $offlineSync): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        /** @var list<array<string, mixed>> $operations */
        $operations = $request->validated('operations');

        return response()->json(['data' => ['operations' => $offlineSync->reconcile($user, $operations)]]);
    }
}
