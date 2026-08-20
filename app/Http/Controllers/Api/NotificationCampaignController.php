<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationCampaignRequest;
use App\Http\Resources\NotificationCampaignResource;
use App\Services\NotificationCampaignService;
use Illuminate\Http\Request;

class NotificationCampaignController extends Controller
{
    public function __construct(private readonly NotificationCampaignService $campaigns)
    {
    }

    public function audienceCatalog()
    {
        $catalog = $this->campaigns->audienceCatalog();

        return [
            'grades' => $catalog['grades']->values(),
            'recipients' => $catalog['recipients']->values(),
            'academic_groups' => \App\Models\AcademicGroup::query()
                ->withCount('students')
                ->where('is_active', true)
                ->orderBy('grade')
                ->orderBy('name')
                ->get(),
            'channels' => app(\App\Services\NotificationChannelConfigurationService::class)
                ->all()
                ->map(fn (\App\Models\NotificationChannelSetting $channel): array => [
                    'code' => $channel->code,
                    'label' => $channel->label,
                    'is_enabled' => $channel->is_enabled,
                    'is_provider_ready' => app(\App\Services\NotificationChannelConfigurationService::class)->isProviderReady($channel->code),
                ]),
        ];
    }

    public function index(Request $request)
    {
        return NotificationCampaignResource::collection(
            \App\Models\NotificationCampaign::query()
                ->with('sender:id,name')
                ->latest()
                ->limit(50)
                ->get(),
        );
    }

    public function store(StoreNotificationCampaignRequest $request)
    {
        /** @var \App\Models\User $sender */
        $sender = $request->user();

        return (new NotificationCampaignResource($this->campaigns->create($sender, $request->validated())))
            ->response()
            ->setStatusCode(201);
    }
}
