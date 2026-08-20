<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\ExamResult;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Models\Worksheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_snapshot_is_bounded_and_omits_phone_fields(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = $this->student('سجل دون اتصال');
        AttendanceRecord::create([
            'student_id' => $student->id,
            'date_at' => now(),
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
            'recorded_by' => $teacher->id,
        ]);

        Sanctum::actingAs($teacher);
        $response = $this->getJson('/api/sync/snapshot')->assertOk();

        $response->assertJsonPath('data.scope.role', 'teacher')
            ->assertJsonPath('data.students.0.id', $student->id)
            ->assertJsonPath('data.attendance.0.student_id', $student->id);
        $this->assertArrayNotHasKey('phone', $response->json('data.students.0'));
        $this->assertArrayNotHasKey('parent_phone', $response->json('data.students.0'));
    }

    public function test_staff_reconciliation_applies_recorded_operations_once(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = $this->student('طالب مزامنة');
        Sanctum::actingAs($teacher);

        $operations = [
            $this->operation('attendance.create', [
                'student_id' => $student->id,
                'date_at' => '2026-08-20T08:30:00Z',
                'status' => 'present',
                'note' => 'سجل محلي',
            ]),
            $this->operation('exam_result.create', [
                'student_id' => $student->id,
                'title' => 'اختبار مزامنة',
                'score' => 18,
                'max_score' => 20,
                'taken_at' => '2026-08-20T09:00:00Z',
            ]),
            $this->operation('payment.create', [
                'student_id' => $student->id,
                'amount' => 350,
                'status' => 'paid',
                'due_at' => '2026-08-20T00:00:00Z',
                'paid_at' => '2026-08-20T09:30:00Z',
            ]),
        ];

        $this->postJson('/api/sync/operations', ['operations' => $operations])
            ->assertOk()
            ->assertJsonPath('data.operations.0.outcome', 'applied')
            ->assertJsonPath('data.operations.1.outcome', 'applied')
            ->assertJsonPath('data.operations.2.outcome', 'applied');

        $this->assertDatabaseCount('offline_sync_operations', 3);
        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseCount('exam_results', 1);
        $this->assertDatabaseCount('payments', 1);

        $this->postJson('/api/sync/operations', ['operations' => $operations])
            ->assertOk()
            ->assertJsonPath('data.operations.0.outcome', 'duplicate')
            ->assertJsonPath('data.operations.1.outcome', 'duplicate')
            ->assertJsonPath('data.operations.2.outcome', 'duplicate');

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseCount('exam_results', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_student_cannot_reconcile_staff_only_records(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = $this->student('طالب مقيد');
        $student->account()->create(['user_id' => $studentUser->id, 'relationship' => 'student']);
        Sanctum::actingAs($studentUser);

        $this->postJson('/api/sync/operations', ['operations' => [$this->operation('attendance.create', [
            'student_id' => $student->id,
            'date_at' => '2026-08-20T08:30:00Z',
            'status' => 'present',
        ])]])->assertOk()->assertJsonPath('data.operations.0.outcome', 'rejected')
            ->assertJsonPath('data.operations.0.error_code', 'forbidden');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_student_submission_detects_a_stale_assignment_without_overwriting_server_data(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = $this->student('طالب شيت');
        $student->account()->create(['user_id' => $studentUser->id, 'relationship' => 'student']);
        $worksheet = Worksheet::create([
            'title' => 'شيت التزامن',
            'subject' => 'الجبر',
            'grade' => $student->grade,
            'status' => 'published',
            'created_by' => $teacher->id,
        ]);
        $assignment = $worksheet->assignments()->create([
            'student_id' => $student->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
        $assignment->update(['feedback' => 'تعديل أحدث من الخادم']);

        Sanctum::actingAs($studentUser);
        $this->postJson('/api/sync/operations', ['operations' => [$this->operation('worksheet_submission.submit', [
            'assignment_id' => $assignment->id,
            'score' => 18,
            'max_score' => 20,
        ], now()->subMinute()->toIso8601String())]])
            ->assertOk()
            ->assertJsonPath('data.operations.0.outcome', 'conflict')
            ->assertJsonPath('data.operations.0.error_code', 'stale_record');

        $this->assertDatabaseHas('worksheet_assignments', [
            'id' => $assignment->id,
            'status' => 'assigned',
            'feedback' => 'تعديل أحدث من الخادم',
        ]);
    }

    private function student(string $name): Student
    {
        return Student::create([
            'name' => $name,
            'group' => 'بنين',
            'grade' => 'ثانية إعدادى',
            'phone' => '0100000000',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function operation(string $type, array $data, ?string $baseUpdatedAt = null): array
    {
        return array_filter([
            'id' => fake()->uuid(),
            'type' => $type,
            'occurred_at' => now()->toIso8601String(),
            'base_updated_at' => $baseUpdatedAt,
            'data' => $data,
        ], fn (mixed $value): bool => $value !== null);
    }
}
