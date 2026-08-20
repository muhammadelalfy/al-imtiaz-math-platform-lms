<?php

namespace Database\Factories;

use App\Models\OfflineSyncOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfflineSyncOperation>
 */
class OfflineSyncOperationFactory extends Factory
{
    protected $model = OfflineSyncOperation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_operation_id' => fake()->uuid(),
            'type' => 'attendance.create',
            'status' => 'applied',
            'payload' => ['student_id' => 1, 'status' => 'present'],
            'result' => ['domain' => 'attendance', 'record_id' => 1],
            'occurred_at' => now()->subMinute(),
            'processed_at' => now(),
        ];
    }
}
