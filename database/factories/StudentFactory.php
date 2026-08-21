<?php

namespace Database\Factories;

use App\Models\Student;
use App\Support\AcademicLevelCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /** @var array<int, string> */
    private const FIRST_NAMES = ['أحمد', 'سارة', 'يوسف', 'ملك', 'عمر', 'نورهان', 'آدم', 'جنى', 'زياد', 'ليان'];

    /** @var array<int, string> */
    private const FAMILY_NAMES = ['محمد علي', 'محمود حسن', 'خالد إبراهيم', 'أحمد السيد', 'سامح عبد الله', 'محمد سالم', 'كريم فؤاد', 'تامر حسين', 'أشرف مصطفى', 'هشام عادل'];

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(self::FIRST_NAMES).' '.fake()->randomElement(self::FAMILY_NAMES),
            'group' => fake()->randomElement(AcademicLevelCatalog::groupNames()),
            'grade' => fake()->randomElement(AcademicLevelCatalog::grades()),
            'phone' => fake()->unique()->numerify('010########'),
            'parent_phone' => fake()->unique()->numerify('011########'),
            'status' => fake()->randomElement(['excellent', 'average', 'weak']),
        ];
    }

    public function levelSeed(int $sequence): static
    {
        return $this->state(fn (): array => [
            'name' => self::FIRST_NAMES[$sequence % count(self::FIRST_NAMES)].' '.self::FAMILY_NAMES[intdiv($sequence, count(self::FIRST_NAMES)) % count(self::FAMILY_NAMES)],
            'group' => AcademicLevelCatalog::groupForSeed($sequence),
            'grade' => AcademicLevelCatalog::gradeForSeed($sequence),
            'phone' => self::levelSeedPhone($sequence),
            'parent_phone' => self::levelSeedParentPhone($sequence),
            'status' => ['excellent', 'average', 'weak'][$sequence % 3],
        ]);
    }

    public static function levelSeedPhone(int $sequence): string
    {
        return sprintf('01098%06d', $sequence + 1);
    }

    public static function levelSeedParentPhone(int $sequence): string
    {
        return sprintf('01198%06d', $sequence + 1);
    }
}
