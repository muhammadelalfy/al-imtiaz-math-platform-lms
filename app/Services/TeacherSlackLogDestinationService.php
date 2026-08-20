<?php

namespace App\Services;

use App\Contracts\Repositories\TeacherSlackLogDestinationRepositoryInterface;
use App\Exceptions\TeacherSlackLogDestinationStorageException;
use App\Models\TeacherSlackLogDestination;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class TeacherSlackLogDestinationService
{
    public function __construct(private readonly TeacherSlackLogDestinationRepositoryInterface $destinations)
    {
    }

    public function findFor(User $teacher): ?TeacherSlackLogDestination
    {
        return $this->destinations->findFor($teacher);
    }

    /** @param array{channel_label?: string|null, webhook_url?: string|null, is_enabled: bool} $attributes */
    public function update(User $teacher, array $attributes): TeacherSlackLogDestination
    {
        try {
            return DB::transaction(function () use ($teacher, $attributes): TeacherSlackLogDestination {
                $destination = $this->destinations->findForUpdate($teacher)
                    ?? new TeacherSlackLogDestination(['user_id' => $teacher->id]);
                $webhookUrl = Arr::get($attributes, 'webhook_url');

                if (filled($webhookUrl)) {
                    $this->assertSlackWebhookUrl($webhookUrl);
                    $destination->webhook_url = $webhookUrl;
                }

                $destination->channel_label = Arr::get($attributes, 'channel_label');
                $destination->is_enabled = $attributes['is_enabled'];

                if ($destination->is_enabled && blank($destination->webhook_url)) {
                    throw ValidationException::withMessages([
                        'webhook_url' => 'أضف رابط Slack Incoming Webhook قبل تفعيل سجل القناة.',
                    ]);
                }

                return $this->destinations->save($destination);
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TeacherSlackLogDestinationStorageException('Unable to store Slack destination.', previous: $exception);
        }
    }

    public function clear(User $teacher): void
    {
        try {
            DB::transaction(function () use ($teacher): void {
                $this->destinations->deleteFor($teacher);
            }, 3);
        } catch (Throwable $exception) {
            throw new TeacherSlackLogDestinationStorageException('Unable to remove Slack destination.', previous: $exception);
        }
    }

    public function isTrustedSlackWebhook(string $url): bool
    {
        $parts = parse_url($url);
        $host = isset($parts['host']) ? strtolower($parts['host']) : null;

        return ($parts['scheme'] ?? null) === 'https'
            && in_array($host, ['hooks.slack.com', 'hooks.slack-gov.com'], true)
            && str_starts_with($parts['path'] ?? '', '/services/');
    }

    private function assertSlackWebhookUrl(string $url): void
    {
        if ($this->isTrustedSlackWebhook($url)) {
            return;
        }

        throw ValidationException::withMessages([
            'webhook_url' => 'استخدم رابط Slack Incoming Webhook آمناً يبدأ بـ https://hooks.slack.com/services/.',
        ]);
    }
}
