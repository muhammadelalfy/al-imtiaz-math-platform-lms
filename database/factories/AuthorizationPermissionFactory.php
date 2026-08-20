<?php

namespace Database\Factories;

use App\Models\AuthorizationPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuthorizationPermission> */
class AuthorizationPermissionFactory extends Factory
{
    protected $model = AuthorizationPermission::class;

    public function definition(): array
    {
        $name = 'feature.'.fake()->unique()->slug(2);

        return [
            'name' => $name,
            'guard_name' => 'web',
            'label' => 'صلاحية '.fake()->unique()->word(),
            'description' => null,
            'is_system' => false,
        ];
    }
}
