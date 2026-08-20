<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TeacherSlackRequestLogDispatcher
{
    public function __construct(private readonly TeacherSlackLogDestinationService $destinations)
    {
    }

    public function dispatch(User $teacher, Request $request, Response $response, int $durationMilliseconds): void
    {
        if (!$this->shouldSend($request, $response)) {
            return;
        }

        $destination = $this->destinations->findFor($teacher);
        if (!$destination?->is_enabled || blank($destination->webhook_url) || !$this->destinations->isTrustedSlackWebhook($destination->webhook_url)) {
            return;
        }

        $route = Str::of($request->path())
            ->replaceMatches('/\\b\\d+\\b/', ':id')
            ->toString();
        $text = implode("\n", [
            '🧮 *سجل منصة الامتياز*',
            "العملية: {$request->method()} /{$route}",
            "الحالة: HTTP {$response->getStatusCode()} · {$durationMilliseconds}ms",
        ]);

        try {
            Http::asJson()
                ->timeout(2)
                ->post($destination->webhook_url, ['text' => $text]);
        } catch (ConnectionException) {
            // Delivery is best effort and must never delay or expose LMS activity to application storage.
        }
    }

    private function shouldSend(Request $request, Response $response): bool
    {
        if (str_starts_with($request->path(), 'api/teacher/slack-log-destination')) {
            return false;
        }

        return !$request->isMethod('GET') || $response->getStatusCode() >= 400;
    }
}
