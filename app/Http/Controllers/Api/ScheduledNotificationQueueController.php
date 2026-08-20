<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class ScheduledNotificationQueueController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $processed = 0;

        for ($job = 0; $job < 10; $job++) {
            Artisan::call('queue:work', [
                '--once' => true,
                '--queue' => 'notifications',
                '--tries' => 3,
                '--timeout' => 60,
            ]);
            $processed++;
        }

        return response()->json(['ok' => true, 'queue' => 'notifications', 'attempts' => $processed]);
    }
}
