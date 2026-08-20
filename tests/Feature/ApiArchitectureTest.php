<?php

namespace Tests\Feature;

use App\Contracts\Repositories\DashboardMetricsRepositoryInterface;
use App\Contracts\Repositories\StudentRepositoryInterface;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_contracts_are_resolved_and_strict_loading_is_enabled(): void
    {
        $this->assertInstanceOf(StudentRepositoryInterface::class, app(StudentRepositoryInterface::class));
        $this->assertInstanceOf(DashboardMetricsRepositoryInterface::class, app(DashboardMetricsRepositoryInterface::class));
        $this->assertTrue(Model::preventsLazyLoading());
    }

    public function test_student_form_request_and_resource_preserve_the_api_contract(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'teacher']));

        $this->postJson('/api/students', [
            'name' => 'طالب بنمط واضح',
            'group' => 'بنين',
            'grade' => 'أولى إعدادى',
            'phone' => '0100000000',
            'status' => 'excellent',
        ])->assertCreated()->assertJsonPath('name', 'طالب بنمط واضح')->assertJsonMissingPath('data');

        $this->postJson('/api/students', [
            'name' => 'طالب بدون هاتف',
            'group' => 'بنين',
            'grade' => 'أولى إعدادى',
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');
    }

    public function test_dashboard_metrics_cache_is_invalidated_after_a_student_write(): void
    {
        Cache::flush();
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        Student::create([
            'name' => 'الطالب الأول',
            'group' => 'بنين',
            'grade' => 'ثانية إعدادى',
            'phone' => '0100000001',
        ]);

        $this->getJson('/api/reports/summary')->assertOk()->assertJsonPath('students', 1);

        $this->postJson('/api/students', [
            'name' => 'الطالب الثاني',
            'group' => 'بنات',
            'grade' => 'ثانية إعدادى',
            'phone' => '0100000002',
        ])->assertCreated();

        $this->getJson('/api/reports/summary')->assertOk()->assertJsonPath('students', 2);
    }

    public function test_student_scoped_collection_has_explicitly_loaded_data_under_strict_mode(): void
    {
        $student = Student::create([
            'name' => 'طالب الحساب',
            'group' => 'بنين',
            'grade' => 'ثالثة إعدادى',
            'phone' => '0100000003',
        ]);
        $user = User::factory()->create(['role' => 'student']);
        $student->account()->create(['user_id' => $user->id, 'relationship' => 'student']);

        Sanctum::actingAs($user);

        $this->getJson('/api/students')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $student->id);
    }

    public function test_dashboard_metrics_cache_is_invalidated_after_attendance_exam_and_payment_writes(): void
    {
        Cache::flush();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = Student::create([
            'name' => 'طالب المقاييس',
            'group' => 'بنات',
            'grade' => 'أولى ثانوى',
            'phone' => '0100000004',
        ]);
        Sanctum::actingAs($teacher);

        $this->getJson('/api/reports/summary')->assertOk()->assertJsonPath('exams.score', 0);

        $this->postJson('/api/attendance', [
            'student_id' => $student->id,
            'date_at' => '2026-08-20 09:00:00',
            'status' => 'present',
        ])->assertCreated();
        $this->getJson('/api/reports/summary')->assertOk()->assertJsonPath('attendance.present', 1);

        $this->postJson('/api/exams', [
            'student_id' => $student->id,
            'title' => 'اختبار المقاييس',
            'score' => 15,
            'max_score' => 20,
            'taken_at' => '2026-08-20',
        ])->assertCreated();
        $this->getJson('/api/reports/summary')->assertOk()->assertJsonPath('exams.score', 15);

        $this->postJson('/api/payments', [
            'student_id' => $student->id,
            'amount' => 500,
            'status' => 'paid',
            'due_at' => '2026-08-20',
        ])->assertCreated();
        $this->getJson('/api/reports/summary')->assertOk()->assertJsonPath('payments.0.amount', 500);
    }
}
