<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TeacherSlackRequestLogDispatcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DispatchTeacherSlackRequestLog
{
    public function __construct(private readonly TeacherSlackRequestLogDispatcher $dispatcher)
    {
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('teacher_slack_log_started_at', hrtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $teacher = $request->user();
        if (!$teacher instanceof User || !$teacher->isAnyRole('teacher')) {
            return;
        }

        $startedAt = (int) $request->attributes->get('teacher_slack_log_started_at', hrtime(true));
        $durationMilliseconds = (int) ((hrtime(true) - $startedAt) / 1_000_000);
        $this->dispatcher->dispatch($teacher, $request, $response, $durationMilliseconds);
    }
}
