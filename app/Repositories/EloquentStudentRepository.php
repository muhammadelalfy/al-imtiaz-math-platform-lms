<?php

namespace App\Repositories;

use App\Contracts\Repositories\StudentRepositoryInterface;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentStudentRepository implements StudentRepositoryInterface
{
    public function paginateFor(User $user, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Student::query()
            ->when(filled($filters['grade'] ?? null), fn ($builder) => $builder->where('grade', $filters['grade']))
            ->when(filled($filters['group'] ?? null), fn ($builder) => $builder->where('group', $filters['group']))
            ->when(filled($filters['search'] ?? null), fn ($builder) => $builder->where('name', 'like', '%'.$filters['search'].'%'));

        if ($user->isAnyRole('student', 'parent')) {
            $account = $user->loadMissing('studentAccount')->studentAccount;
            abort_unless($account !== null, 403);
            $query->whereKey($account->student_id);
        }

        return $query
            ->withCount(['assignments', 'attendanceRecords', 'examResults', 'payments'])
            ->latest()
            ->paginate($perPage);
    }

    public function findDetailed(Student $student): Student
    {
        return $student->loadMissing([
            'assignments.worksheet',
            'attendanceRecords',
            'examResults',
            'payments',
        ]);
    }

    public function create(array $attributes): Student
    {
        return Student::create($attributes);
    }

    public function update(Student $student, array $attributes): Student
    {
        $student->update($attributes);

        return $student->fresh();
    }

    public function delete(Student $student): void
    {
        $student->delete();
    }
}
