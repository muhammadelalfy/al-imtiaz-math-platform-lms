<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InAppNotificationResource;
use App\Models\NotificationDelivery;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationInboxController extends Controller
{
    public function index(Request $request)
    {
        return InAppNotificationResource::collection(
            $request->user()->notifications()->latest()->limit(100)->get(),
        );
    }

    public function markRead(Request $request, DatabaseNotification $notification)
    {
        abort_unless($notification->notifiable_id === $request->user()->id && $notification->notifiable_type === $request->user()::class, 404);

        $notification->markAsRead();
        $deliveryId = $notification->data['delivery_id'] ?? null;

        if (is_int($deliveryId) || ctype_digit((string) $deliveryId)) {
            NotificationDelivery::query()
                ->whereKey((int) $deliveryId)
                ->where('recipient_id', $request->user()->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return new InAppNotificationResource($notification->fresh());
    }
}
