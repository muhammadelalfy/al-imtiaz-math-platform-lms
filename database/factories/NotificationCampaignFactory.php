<?php

namespace Database\Factories;

use App\Models\NotificationCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationCampaign> */
class NotificationCampaignFactory extends Factory
{
    protected $model = NotificationCampaign::class;

    public function definition(): array
    {
        return [
            'sent_by' => User::factory()->state(['role' => 'teacher']),
            'audience' => 'all_students',
            'title' => 'تنبيه دراسي',
            'body' => 'يرجى مراجعة واجب الرياضيات قبل الحصة القادمة.',
            'recipient_count' => 0,
            'queued_at' => now(),
        ];
    }
}
