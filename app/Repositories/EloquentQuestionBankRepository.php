<?php

namespace App\Repositories;

use App\Contracts\Repositories\QuestionBankRepositoryInterface;
use App\Models\QuestionBankQuestion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentQuestionBankRepository implements QuestionBankRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = QuestionBankQuestion::query()->with('department')->latest();

        if (($search = $filters['search'] ?? null) !== null && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('prompt_html', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        foreach (['type', 'grade'] as $filter) {
            if (($value = $filters[$filter] ?? null) !== null && $value !== '') {
                $query->where($filter, $value);
            }
        }

        if (array_key_exists('active', $filters)) {
            $query->where('is_active', $filters['active']);
        }

        return $query->paginate($perPage);
    }

    public function show(QuestionBankQuestion $question): QuestionBankQuestion
    {
        return $question->loadMissing('department');
    }

    public function create(array $attributes, User $creator): QuestionBankQuestion
    {
        return QuestionBankQuestion::create([...$attributes, 'created_by' => $creator->id])->load('department');
    }

    public function update(QuestionBankQuestion $question, array $attributes): QuestionBankQuestion
    {
        $question->update($attributes);

        return $question->fresh()->load('department');
    }

    public function delete(QuestionBankQuestion $question): void
    {
        $question->delete();
    }
}
