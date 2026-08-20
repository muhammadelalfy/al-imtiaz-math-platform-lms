<?php

namespace App\Services;

use App\Contracts\Repositories\DashboardMetricsCacheInterface;
use App\Contracts\Repositories\WorksheetRepositoryInterface;
use App\Contracts\Services\OfflineSyncServiceInterface;
use App\Models\AttendanceRecord;
use App\Models\ExamResult;
use App\Models\OfflineSyncOperation;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Models\Worksheet;
use App\Models\WorksheetAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Attendance\Services\AttendanceDomainService;

final class OfflineSyncService implements OfflineSyncServiceInterface
{
    private const SNAPSHOT_RECORD_LIMIT = 500;

    public function __construct(
        private readonly AttendanceDomainService $attendance,
        private readonly DashboardMetricsCacheInterface $metricsCache,
        private readonly WorksheetRepositoryInterface $worksheets,
    ) {
    }

    public function snapshot(User $user): array
    {
        $studentId = $this->studentScopeId($user);

        return [
            'generated_at' => now()->toIso8601String(),
            'scope' => [
                'user_id' => $user->id,
                'role' => $user->role,
                'student_id' => $studentId,
            ],
            'students' => $this->students($studentId),
            'worksheets' => $this->worksheets($studentId),
            'attendance' => $this->attendance($studentId),
            'exams' => $this->exams($studentId),
            'payments' => $this->payments($studentId),
        ];
    }

    public function reconcile(User $user, array $operations): array
    {
        return array_map(fn (array $operation): array => $this->reconcileOperation($user, $operation), $operations);
    }

    /**
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    private function reconcileOperation(User $user, array $operation): array
    {
        /** @var OfflineSyncOperation $stored */
        $stored = OfflineSyncOperation::query()->firstOrCreate(
            ['user_id' => $user->id, 'client_operation_id' => (string) $operation['id']],
            [
                'type' => (string) $operation['type'],
                'status' => 'processing',
                'payload' => $operation['data'],
                'occurred_at' => Carbon::parse((string) $operation['occurred_at']),
            ],
        );

        if (! $stored->wasRecentlyCreated) {
            return $this->storedOutcome($stored);
        }

        try {
            $result = DB::transaction(fn (): array => $this->apply($user, $operation));
            $stored->update(['status' => 'applied', 'result' => $result, 'processed_at' => now()]);
        } catch (OfflineSyncConflictException) {
            $stored->update(['status' => 'conflict', 'error_code' => 'stale_record', 'processed_at' => now()]);
        } catch (AuthorizationException) {
            $stored->update(['status' => 'rejected', 'error_code' => 'forbidden', 'processed_at' => now()]);
        } catch (ValidationException) {
            $stored->update(['status' => 'rejected', 'error_code' => 'invalid_data', 'processed_at' => now()]);
        } catch (ModelNotFoundException) {
            $stored->update(['status' => 'rejected', 'error_code' => 'not_found', 'processed_at' => now()]);
        }

        /** @var OfflineSyncOperation $fresh */
        $fresh = $stored->fresh();

        return $this->storedOutcome($fresh, true);
    }

    /**
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    private function apply(User $user, array $operation): array
    {
        /** @var array<string, mixed> $data */
        $data = $operation['data'];
        $type = (string) $operation['type'];

        return match ($type) {
            'attendance.create' => $this->createAttendance($user, $data),
            'exam_result.create' => $this->createExamResult($user, $data),
            'payment.create' => $this->createPayment($user, $data),
            'worksheet_submission.submit' => $this->submitWorksheet($user, $data, $operation['base_updated_at'] ?? null),
            default => throw ValidationException::withMessages(['operations' => 'Unsupported offline operation.']),
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function createAttendance(User $user, array $data): array
    {
        $this->requireStaff($user);
        $validated = Validator::validate($data, [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'date_at' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late'],
            'note' => ['nullable', 'string'],
        ]);
        $record = $this->attendance->create($validated, $user->id);

        return $this->recordResult('attendance', $record->id, $record->updated_at);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function createExamResult(User $user, array $data): array
    {
        $this->requireStaff($user);
        $validated = Validator::validate($data, [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'title' => ['required', 'string', 'max:180'],
            'score' => ['required', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'min:1'],
            'taken_at' => ['required', 'date'],
        ]);
        $record = ExamResult::create([...$validated, 'recorded_by' => $user->id]);
        $this->metricsCache->forget();

        return $this->recordResult('exam_result', $record->id, $record->updated_at);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function createPayment(User $user, array $data): array
    {
        $this->requireStaff($user);
        $validated = Validator::validate($data, [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'amount' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:pending,paid,overdue'],
            'due_at' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);
        $record = Payment::create([...$validated, 'recorded_by' => $user->id]);
        $this->metricsCache->forget();

        return $this->recordResult('payment', $record->id, $record->updated_at);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function submitWorksheet(User $user, array $data, mixed $baseUpdatedAt): array
    {
        $validated = Validator::validate($data, [
            'assignment_id' => ['required', 'integer', 'exists:worksheet_assignments,id'],
            'score' => ['nullable', 'integer', 'min:0'],
            'max_score' => ['nullable', 'integer', 'min:1'],
            'feedback' => ['nullable', 'string'],
        ]);
        $assignment = WorksheetAssignment::query()->findOrFail($validated['assignment_id']);

        if ($baseUpdatedAt !== null && ! $assignment->updated_at->equalTo(Carbon::parse((string) $baseUpdatedAt))) {
            throw new OfflineSyncConflictException();
        }

        $record = $this->worksheets->submit($user, $assignment, $validated);

        return $this->recordResult('worksheet_submission', $record->id, $record->updated_at);
    }

    /**
     * @return array<string, mixed>
     */
    private function storedOutcome(OfflineSyncOperation $operation, bool $isFirstProcessing = false): array
    {
        return [
            'id' => $operation->client_operation_id,
            'outcome' => $operation->status === 'applied' && $isFirstProcessing ? 'applied' : ($operation->status === 'applied' ? 'duplicate' : $operation->status),
            'result' => $operation->result,
            'error_code' => $operation->error_code,
        ];
    }

    /**
     * @return array{domain: string, record_id: int, updated_at: string}
     */
    private function recordResult(string $domain, int $recordId, mixed $updatedAt): array
    {
        return ['domain' => $domain, 'record_id' => $recordId, 'updated_at' => Carbon::parse((string) $updatedAt)->toIso8601String()];
    }

    private function requireStaff(User $user): void
    {
        if (! $user->isAnyRole('admin', 'teacher')) {
            throw new AuthorizationException();
        }
    }

    private function studentScopeId(User $user): ?int
    {
        if ($user->isAnyRole('admin', 'teacher')) {
            return null;
        }

        $account = $user->loadMissing('studentAccount')->studentAccount;
        if ($account === null) {
            throw new AuthorizationException();
        }

        return $account->student_id;
    }

    /** @return list<array<string, mixed>> */
    private function students(?int $studentId): array
    {
        $query = Student::query()->select(['id', 'name', 'grade', 'group'])->orderBy('id')->limit(self::SNAPSHOT_RECORD_LIMIT);
        if ($studentId !== null) {
            $query->whereKey($studentId);
        }

        /** @var list<array<string, mixed>> $items */
        $items = [];
        foreach ($query->get() as $student) {
            $items[] = [
                'id' => $student->id,
                'name' => $student->name,
                'grade' => $student->grade,
                'group' => $student->group,
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private function attendance(?int $studentId): array
    {
        $query = AttendanceRecord::query()->select(['id', 'student_id', 'date_at', 'attendance_date', 'status', 'note', 'updated_at'])
            ->latest('date_at')->limit(self::SNAPSHOT_RECORD_LIMIT);
        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        /** @var list<array<string, mixed>> $items */
        $items = [];
        foreach ($query->get() as $record) {
            $items[] = [
                'id' => $record->id,
                'student_id' => $record->student_id,
                'date_at' => Carbon::parse((string) $record->date_at)->toIso8601String(),
                'attendance_date' => $record->attendance_date,
                'status' => $record->status,
                'note' => $record->note,
                'updated_at' => Carbon::parse((string) $record->updated_at)->toIso8601String(),
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private function exams(?int $studentId): array
    {
        $query = ExamResult::query()->select(['id', 'student_id', 'title', 'score', 'max_score', 'taken_at', 'updated_at'])
            ->latest('taken_at')->limit(self::SNAPSHOT_RECORD_LIMIT);
        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        /** @var list<array<string, mixed>> $items */
        $items = [];
        foreach ($query->get() as $record) {
            $items[] = [
                'id' => $record->id,
                'student_id' => $record->student_id,
                'title' => $record->title,
                'score' => $record->score,
                'max_score' => $record->max_score,
                'taken_at' => Carbon::parse((string) $record->taken_at)->toIso8601String(),
                'updated_at' => Carbon::parse((string) $record->updated_at)->toIso8601String(),
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private function payments(?int $studentId): array
    {
        $query = Payment::query()->select(['id', 'student_id', 'amount', 'status', 'due_at', 'paid_at', 'note', 'updated_at'])
            ->latest('due_at')->limit(self::SNAPSHOT_RECORD_LIMIT);
        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        /** @var list<array<string, mixed>> $items */
        $items = [];
        foreach ($query->get() as $record) {
            $items[] = [
                'id' => $record->id,
                'student_id' => $record->student_id,
                'amount' => (int) $record->amount,
                'status' => $record->status,
                'due_at' => Carbon::parse((string) $record->due_at)->toIso8601String(),
                'paid_at' => $record->paid_at === null ? null : Carbon::parse((string) $record->paid_at)->toIso8601String(),
                'note' => $record->note,
                'updated_at' => Carbon::parse((string) $record->updated_at)->toIso8601String(),
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private function worksheets(?int $studentId): array
    {
        $query = WorksheetAssignment::query()
            ->select(['id', 'worksheet_id', 'student_id', 'status', 'score', 'max_score', 'feedback', 'assigned_at', 'submitted_at', 'updated_at'])
            ->with('worksheet:id,title,subject,grade,status')
            ->latest('updated_at')
            ->limit(self::SNAPSHOT_RECORD_LIMIT);
        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        /** @var array<int, array<string, mixed>> $byWorksheet */
        $byWorksheet = [];
        foreach ($query->get() as $assignment) {
            /** @var Worksheet|null $worksheet */
            $worksheet = $assignment->worksheet;
            if ($worksheet === null) {
                continue;
            }

            if (! isset($byWorksheet[$worksheet->id])) {
                $byWorksheet[$worksheet->id] = [
                    'id' => $worksheet->id,
                    'title' => $worksheet->title,
                    'subject' => $worksheet->subject,
                    'grade' => $worksheet->grade,
                    'status' => $worksheet->status,
                    'assignments' => [],
                ];
            }

            /** @var list<array<string, mixed>> $assignments */
            $assignments = $byWorksheet[$worksheet->id]['assignments'];
            $assignments[] = [
                'id' => $assignment->id,
                'worksheet_id' => $assignment->worksheet_id,
                'student_id' => $assignment->student_id,
                'status' => $assignment->status,
                'score' => $assignment->score,
                'max_score' => $assignment->max_score,
                'feedback' => $assignment->feedback,
                'assigned_at' => $this->nullableTimestamp($assignment->getRawOriginal('assigned_at')),
                'submitted_at' => $this->nullableTimestamp($assignment->getRawOriginal('submitted_at')),
                'updated_at' => Carbon::parse((string) $assignment->updated_at)->toIso8601String(),
            ];
            $byWorksheet[$worksheet->id]['assignments'] = $assignments;
        }

        /** @var list<array<string, mixed>> $items */
        $items = [];
        foreach ($byWorksheet as $worksheet) {
            $items[] = $worksheet;
        }

        return $items;
    }

    private function nullableTimestamp(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse((string) $value)->toIso8601String();
    }
}
