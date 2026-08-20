<?php

namespace App\Contracts\Repositories;

use App\Models\AcademicGroup;
use Illuminate\Support\Collection;

interface AcademicGroupRepositoryInterface
{
    /** @return Collection<int, AcademicGroup> */
    public function all(): Collection;

    public function create(array $attributes): AcademicGroup;

    public function update(AcademicGroup $group, array $attributes): AcademicGroup;

    /** @param array<int, int> $studentIds */
    public function syncStudents(AcademicGroup $group, array $studentIds): AcademicGroup;

    public function delete(AcademicGroup $group): void;
}
