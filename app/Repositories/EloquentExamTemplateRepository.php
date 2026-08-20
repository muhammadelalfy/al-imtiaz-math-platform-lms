<?php

namespace App\Repositories;

use App\Contracts\Repositories\ExamTemplateRepositoryInterface;
use App\Models\ExamTemplate;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentExamTemplateRepository implements ExamTemplateRepositoryInterface
{
    public function paginateFor(User $user, int $perPage = 50): LengthAwarePaginator
    {
        $query = ExamTemplate::query()->with(['department', 'questions']);

        if ($user->isAnyRole('student', 'parent')) {
            $query->where('status', 'published');
        } else {
            abort_unless($user->isAnyRole('admin', 'teacher'), 403);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $attributes, User $creator): ExamTemplate
    {
        return DB::transaction(function () use ($attributes, $creator): ExamTemplate {
            $questions = $attributes['questions'] ?? [];
            unset($attributes['questions']);

            $template = ExamTemplate::create([...$attributes, 'created_by' => $creator->id]);
            $this->synchronizeQuestions($template, $questions);

            return $this->loadPresentationRelations($template);
        });
    }

    public function update(ExamTemplate $template, array $attributes): ExamTemplate
    {
        return DB::transaction(function () use ($template, $attributes): ExamTemplate {
            $questions = $attributes['questions'] ?? null;
            unset($attributes['questions']);
            $template->update($attributes);

            if (is_array($questions)) {
                $this->synchronizeQuestions($template, $questions);
            }

            return $this->loadPresentationRelations($template->fresh() ?? $template);
        });
    }

    public function delete(ExamTemplate $template): void
    {
        $template->delete();
    }

    /** @param list<array<string, mixed>> $questions */
    private function synchronizeQuestions(ExamTemplate $template, array $questions): void
    {
        $existingIds = $template->questions()->pluck('id')->all();
        $incomingIds = [];

        foreach ($questions as $index => $question) {
            $questionId = $question['id'] ?? null;
            unset($question['id'], $question['sort_order']);

            if ($questionId !== null) {
                abort_unless(in_array($questionId, $existingIds, true), 422, 'السؤال لا ينتمي إلى هذا القالب.');
                $template->questions()->whereKey($questionId)->update([...$question, 'sort_order' => $index]);
                $incomingIds[] = $questionId;

                continue;
            }

            $incomingIds[] = $template->questions()->create([...$question, 'sort_order' => $index])->getKey();
        }

        if ($existingIds !== []) {
            $template->questions()->whereNotIn('id', $incomingIds)->delete();
        }
    }

    private function loadPresentationRelations(ExamTemplate $template): ExamTemplate
    {
        return $template->load(['department', 'questions']);
    }
}
