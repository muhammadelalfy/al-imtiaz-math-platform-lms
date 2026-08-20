<?php

namespace App\Repositories;

use App\Contracts\Repositories\AcademicGroupRepositoryInterface;
use App\Models\AcademicGroup;
use Illuminate\Support\Collection;

class EloquentAcademicGroupRepository implements AcademicGroupRepositoryInterface
{
    /** @return Collection<int, AcademicGroup> */
    public function all(): Collection
    {
        return AcademicGroup::query()->withCount('students')->orderBy('grade')->orderBy('name')->get();
    }

    public function create(array $attributes): AcademicGroup
    {
        return AcademicGroup::query()->create($attributes)->loadCount('students');
    }

    public function update(AcademicGroup $group, array $attributes): AcademicGroup
    {
        $group->update($attributes);

        return $group->fresh()->loadCount('students');
    }

    /** @param array<int, int> $studentIds */
    public function syncStudents(AcademicGroup $group, array $studentIds): AcademicGroup
    {
        $group->students()->sync($studentIds);

        return $group->fresh()->load([
            'students' => fn ($query) => $query->select(['students.id', 'students.name', 'students.grade', 'students.group']),
        ])->loadCount('students');
    }

    public function delete(AcademicGroup $group): void
    {
        $group->delete();
    }
}
