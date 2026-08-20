<?php

namespace App\Contracts\Repositories;

use App\Models\QuestionBankQuestion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuestionBankRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator;

    public function show(QuestionBankQuestion $question): QuestionBankQuestion;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, User $creator): QuestionBankQuestion;

    /** @param array<string, mixed> $attributes */
    public function update(QuestionBankQuestion $question, array $attributes): QuestionBankQuestion;

    public function delete(QuestionBankQuestion $question): void;
}
