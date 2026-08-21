<?php

namespace Tests\Feature;

use App\Models\AcademicGroup;
use App\Models\Student;
use Database\Seeders\ArabicDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArabicDemoSeederLevelDataTest extends TestCase
{
    use RefreshDatabase;

    private function seedArabicDemoData(): void
    {
        $this->app->instance('env', 'testing');
        $this->seed(ArabicDemoSeeder::class);
    }

    public function test_it_seeds_five_thousand_idempotent_level_students_with_group_memberships(): void
    {
        $this->seedArabicDemoData();

        $firstStudent = Student::query()->where('phone', '01098000001')->firstOrFail();
        $originalQrToken = $firstStudent->getAttribute('qr_token');

        $this->assertDatabaseCount('students', 5000);
        $this->assertDatabaseCount('academic_groups', 21);
        $this->assertDatabaseCount('academic_group_student', 5000);
        $this->assertSame(7, Student::query()->distinct('grade')->count('grade'));
        $this->assertSame(3, AcademicGroup::query()->where('grade', 'الأول الإعدادي')->count());

        $gradeCounts = Student::query()
            ->selectRaw('grade, count(*) as students_count')
            ->groupBy('grade')
            ->pluck('students_count', 'grade');
        $this->assertCount(7, $gradeCounts);
        $this->assertLessThanOrEqual(3, $gradeCounts->max() - $gradeCounts->min());

        $groupCounts = AcademicGroup::query()->withCount('students')->get()->pluck('students_count');
        $this->assertCount(21, $groupCounts);
        $this->assertGreaterThan(225, $groupCounts->min());
        $this->assertLessThan(250, $groupCounts->max());

        $this->seedArabicDemoData();

        $this->assertDatabaseCount('students', 5000);
        $this->assertDatabaseCount('academic_groups', 21);
        $this->assertDatabaseCount('academic_group_student', 5000);
        $this->assertSame(
            $originalQrToken,
            Student::query()->where('phone', '01098000001')->value('qr_token'),
        );
    }
}
