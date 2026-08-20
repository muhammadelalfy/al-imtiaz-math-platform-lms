<?php

namespace Database\Factories;

use App\Models\AuthorizationRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuthorizationRole> */
class AuthorizationRoleFactory extends Factory
{
    protected $model = AuthorizationRole::class;

    public function definition(): array
    {
        return [
            'name' => 'role-'.fake()->unique()->slug(2),
            'guard_name' => 'web',
            'label' => 'دور '.fake()->unique()->word(),
            'description' => null,
            'is_system' => false,
        ];
    }
}
