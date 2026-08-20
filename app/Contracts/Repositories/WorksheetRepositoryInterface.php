<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use App\Models\Worksheet;
use App\Models\WorksheetAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WorksheetRepositoryInterface
{
    public function paginateFor(User $user, int $perPage = 25): LengthAwarePaginator;

    public function findVisibleFor(User $user, Worksheet $worksheet): Worksheet;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, User $creator): Worksheet;

    /** @param list<int> $studentIds */
    public function assign(Worksheet $worksheet, array $studentIds): Worksheet;

    /** @param array<string, mixed> $attributes */
    public function submit(User $user, WorksheetAssignment $assignment, array $attributes): WorksheetAssignment;
}
