<?php

namespace Database\Factories;

use App\Models\TeacherDashboardLayout;
use App\Models\User;
use App\Services\TeacherDashboardLayoutService;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeacherDashboardLayout> */
class TeacherDashboardLayoutFactory extends Factory
{
    protected $model = TeacherDashboardLayout::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'teacher']),
            'card_order' => TeacherDashboardLayoutService::DEFAULT_CARD_ORDER,
        ];
    }
}
