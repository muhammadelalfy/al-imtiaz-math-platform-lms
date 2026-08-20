<?php

namespace App\Services;

use App\Jobs\DispatchNotificationCampaign;
use App\Models\NotificationCampaign;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationCampaignService
{
    /** @return array{grades: Collection<int, string>, recipients: Collection<int, User>} */
    public function audienceCatalog(): array
    {
        /** @var Collection<int, string> $grades */
        $grades = \App\Models\Student::query()
            ->whereNotNull('grade')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade')
            ->map(static fn (mixed $grade): string => (string) $grade)
            ->values();
        /** @var Collection<int, User> $recipients */
        $recipients = $this->recipientQuery()
            ->select(['id', 'name', 'email', 'role'])
            ->orderBy('role')
            ->orderBy('name')
            ->limit(250)
            ->get();

        return [
            'grades' => $grades,
            'recipients' => $recipients,
        ];
    }

    /** @param array{audience: 'all_parents'|'all_students'|'selected'|'grade'|'academic_group', title: string, body: string, grade?: string|null, academic_group_id?: int|null, recipient_ids?: array<int, int>, channels?: array<int, string>} $input */
    public function create(User $sender, array $input): NotificationCampaign
    {
        return DB::transaction(function () use ($sender, $input): NotificationCampaign {
            $recipientIds = $this->recipientQuery($input)->pluck('id')->all();

            $campaign = NotificationCampaign::query()->create([
                'sent_by' => $sender->id,
                'audience' => $input['audience'],
                'grade' => $input['audience'] === 'grade' ? $input['grade'] : null,
                'academic_group_id' => $input['audience'] === 'academic_group' ? $input['academic_group_id'] : null,
                'channels' => $input['channels'] ?? ['in_app'],
                'title' => $input['title'],
                'body' => $input['body'],
                'recipient_count' => count($recipientIds),
                'queued_at' => now(),
            ]);

            NotificationDelivery::query()->insert(array_map(fn (int $recipientId): array => [
                    'notification_campaign_id' => $campaign->id,
                    'recipient_id' => $recipientId,
                    'status' => 'pending',
                    'attempts' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $recipientIds));

            DispatchNotificationCampaign::dispatch($campaign->id)->afterCommit();

            return $campaign->load('sender');
        });
    }

    /** @param array{audience?: 'all_parents'|'all_students'|'selected'|'grade'|'academic_group', grade?: string|null, academic_group_id?: int|null, recipient_ids?: array<int, int>} $input */
    /** @return Builder<User> */
    private function recipientQuery(array $input = []): Builder
    {
        $query = $this->recipientQueryBase();

        return match ($input['audience'] ?? null) {
            'all_parents' => $query->where('role', 'parent'),
            'all_students' => $query->where('role', 'student'),
            'selected' => $query->whereIn('id', $input['recipient_ids'] ?? []),
            'grade' => $query->whereHas('studentAccount.student', fn (Builder $studentQuery): Builder => $studentQuery->where('grade', $input['grade'] ?? null)),
            'academic_group' => $query->whereHas('studentAccount.student.academicGroups', fn (Builder $groupQuery): Builder => $groupQuery->whereKey($input['academic_group_id'] ?? null)),
            default => $query,
        };
    }

    /** @return Builder<User> */
    private function recipientQueryBase(): Builder
    {
        return User::query()->whereIn('role', ['parent', 'student']);
    }
}
