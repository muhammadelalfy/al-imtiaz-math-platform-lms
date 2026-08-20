<?php

namespace App\Repositories;

use App\Contracts\Repositories\WorksheetRepositoryInterface;
use App\Models\User;
use App\Models\Worksheet;
use App\Models\WorksheetAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentWorksheetRepository implements WorksheetRepositoryInterface
{
    public function paginateFor(User $user, int $perPage = 25): LengthAwarePaginator
    {
        $query = Worksheet::query()->withCount([
            'assignments',
            'assignments as submitted_count' => fn ($builder) => $builder->where('status', 'submitted'),
        ]);

        if ($user->isAnyRole('student', 'parent')) {
            $account = $user->loadMissing('studentAccount')->studentAccount;
            abort_unless($account !== null, 403);
            $query
                ->where('status', 'published')
                ->whereHas('assignments', fn ($builder) => $builder->where('student_id', $account->student_id))
                ->with(['assignments' => fn ($builder) => $builder->where('student_id', $account->student_id)]);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findVisibleFor(User $user, Worksheet $worksheet): Worksheet
    {
        if ($user->isAnyRole('student', 'parent')) {
            $account = $user->loadMissing('studentAccount')->studentAccount;
            abort_unless($account !== null, 403);

            return Worksheet::query()
                ->whereKey($worksheet->getKey())
                ->where('status', 'published')
                ->whereHas('assignments', fn ($builder) => $builder->where('student_id', $account->student_id))
                ->with(['assignments' => fn ($builder) => $builder->where('student_id', $account->student_id)])
                ->firstOrFail();
        }

        return $worksheet->loadMissing('assignments.student');
    }

    public function create(array $attributes, User $creator): Worksheet
    {
        return Worksheet::create([...$attributes, 'created_by' => $creator->id]);
    }

    public function assign(Worksheet $worksheet, array $studentIds): Worksheet
    {
        $now = now();
        $rows = collect($studentIds)->map(fn (int $studentId) => [
            'worksheet_id' => $worksheet->id,
            'student_id' => $studentId,
            'assigned_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        WorksheetAssignment::upsert($rows, ['worksheet_id', 'student_id'], ['updated_at']);

        return Worksheet::query()->with('assignments.student')->findOrFail($worksheet->id);
    }

    public function submit(User $user, WorksheetAssignment $assignment, array $attributes): WorksheetAssignment
    {
        $account = $user->loadMissing('studentAccount')->studentAccount;
        abort_unless(
            $user->isAnyRole('admin', 'teacher') || ($account && $account->student_id === $assignment->student_id),
            403,
        );

        $assignment->update([...$attributes, 'status' => 'submitted', 'submitted_at' => now()]);

        return WorksheetAssignment::query()
            ->with(['worksheet', 'student'])
            ->findOrFail($assignment->id);
    }
}
