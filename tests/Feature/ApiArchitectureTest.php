<?php

namespace Tests\Feature;

use App\Contracts\Observability\CacheObservabilityInterface;
use App\Contracts\Repositories\DashboardMetricsRepositoryInterface;
use App\Contracts\Repositories\ExamTemplateRepositoryInterface;
use App\Contracts\Repositories\PluginStoreRepositoryInterface;
use App\Contracts\Repositories\QuestionBankRepositoryInterface;
use App\Contracts\Repositories\StudentRepositoryInterface;
use App\Contracts\Repositories\WorksheetRepositoryInterface;
use App\Models\ExamDepartment;
use App\Models\PluginProduct;
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
        $this->assertInstanceOf(WorksheetRepositoryInterface::class, app(WorksheetRepositoryInterface::class));
        $this->assertInstanceOf(ExamTemplateRepositoryInterface::class, app(ExamTemplateRepositoryInterface::class));
        $this->assertInstanceOf(QuestionBankRepositoryInterface::class, app(QuestionBankRepositoryInterface::class));
        $this->assertInstanceOf(PluginStoreRepositoryInterface::class, app(PluginStoreRepositoryInterface::class));
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

    public function test_dashboard_metrics_records_cache_miss_then_hit_without_sensitive_dimensions(): void
    {
        Cache::flush();
        $metrics = app(CacheObservabilityInterface::class);
        Sanctum::actingAs(User::factory()->create(['role' => 'teacher']));

        $this->getJson('/api/reports/summary')->assertOk();
        $this->assertSame(['hits' => 0, 'misses' => 1], $metrics->snapshot('dashboard-metrics'));

        $this->getJson('/api/reports/summary')->assertOk();
        $this->assertSame(['hits' => 1, 'misses' => 1], $metrics->snapshot('dashboard-metrics'));
    }

    public function test_worksheet_repository_resource_and_exam_template_resource_preserve_nested_contracts(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = Student::create([
            'name' => 'طالب الواجب',
            'group' => 'بنين',
            'grade' => 'أولى إعدادى',
            'phone' => '0100000005',
        ]);
        $department = ExamDepartment::create(['name' => 'الجبر', 'slug' => 'algebra', 'is_active' => true]);
        Sanctum::actingAs($teacher);

        $worksheetId = $this->postJson('/api/worksheets', [
            'title' => 'واجب التناسب',
            'subject' => 'الجبر',
            'grade' => 'أولى إعدادى',
            'status' => 'published',
        ])->assertCreated()->assertJsonPath('title', 'واجب التناسب')->json('id');

        $this->postJson("/api/worksheets/{$worksheetId}/assign", ['student_ids' => [$student->id]])
            ->assertOk()
            ->assertJsonPath('assignments.0.student.id', $student->id);
        $this->getJson('/api/worksheets')->assertOk()->assertJsonPath('data.0.id', $worksheetId);

        $this->postJson('/api/exam-templates', [
            'department_id' => $department->id,
            'title' => 'اختبار التناسب',
            'duration_minutes' => 45,
            'status' => 'published',
            'questions' => [[
                'type' => 'mcq',
                'prompt_html' => '<p>اختر الإجابة</p>',
                'options' => ['أ', 'ب'],
                'correct_answer' => 'أ',
                'points' => 1,
            ]],
        ])->assertCreated()
            ->assertJsonPath('department.slug', 'algebra')
            ->assertJsonPath('questions.0.prompt_html', '<p>اختر الإجابة</p>');

        $this->getJson('/api/exam-templates')
            ->assertOk()
            ->assertJsonPath('data.0.department.id', $department->id)
            ->assertJsonPath('data.0.questions.0.type', 'mcq');
    }

    public function test_question_bank_and_plugin_store_repositories_preserve_resource_contracts(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $department = ExamDepartment::create(['name' => 'الهندسة', 'slug' => 'geometry', 'is_active' => true]);
        Sanctum::actingAs($teacher);

        $this->postJson('/api/question-bank', [
            'department_id' => $department->id,
            'type' => 'geometry',
            'title' => 'سؤال مورد',
            'prompt_html' => '<p>احسب المحيط.</p>',
            'points' => 2,
            'is_active' => true,
        ])->assertCreated()
            ->assertJsonPath('department.slug', 'geometry')
            ->assertJsonPath('is_active', true);

        PluginProduct::create([
            'slug' => 'repository-plugin',
            'name' => 'إضافة المستودعات',
            'version' => '1.0.0',
            'module_name' => 'RepositoryPlugin',
            'artifact_path' => 'plugins/artifacts/repository-plugin.zip',
            'price' => 0,
            'is_active' => true,
        ]);

        $this->getJson('/api/plugins')
            ->assertOk()
            ->assertJsonPath('data.0.purchased', false)
            ->assertJsonPath('data.0.installed', false);
    }
}
