<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationChannelSettingRequest;
use App\Http\Resources\NotificationChannelSettingResource;
use App\Models\NotificationChannelSetting;
use App\Services\NotificationChannelConfigurationService;

class NotificationChannelSettingController extends Controller
{
    public function __construct(private readonly NotificationChannelConfigurationService $channels)
    {
    }

    public function index()
    {
        abort_unless(request()->user()?->can('notifications.channels.manage'), 403);

        return NotificationChannelSettingResource::collection($this->channels->all());
    }

    public function update(UpdateNotificationChannelSettingRequest $request, NotificationChannelSetting $notificationChannelSetting)
    {
        return new NotificationChannelSettingResource($this->channels->update($notificationChannelSetting, $request->validated(), $request->user()));
    }
}
