<?php

namespace Database\Factories;

use App\Models\AcademicGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcademicGroup> */
class AcademicGroupFactory extends Factory
{
    protected $model = AcademicGroup::class;

    public function definition(): array
    {
        return ['grade' => 'الأول الإعدادي', 'name' => 'مجموعة التفوق', 'is_active' => true];
    }
}
