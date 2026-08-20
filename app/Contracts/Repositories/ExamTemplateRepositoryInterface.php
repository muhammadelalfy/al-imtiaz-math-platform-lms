<?php

namespace App\Contracts\Repositories;

use App\Models\ExamTemplate;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ExamTemplateRepositoryInterface
{
    public function paginateFor(User $user, int $perPage = 50): LengthAwarePaginator;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, User $creator): ExamTemplate;

    /** @param array<string, mixed> $attributes */
    public function update(ExamTemplate $template, array $attributes): ExamTemplate;

    public function delete(ExamTemplate $template): void;
}
