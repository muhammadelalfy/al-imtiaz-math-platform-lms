<?php

namespace App\Contracts\Repositories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StudentRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function paginateFor(User $user, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function findDetailed(Student $student): Student;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Student;

    /** @param array<string, mixed> $attributes */
    public function update(Student $student, array $attributes): Student;

    public function delete(Student $student): void;
}
