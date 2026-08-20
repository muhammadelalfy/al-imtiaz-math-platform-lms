<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccessTokenMatchesRole
{
    /** @param string ...$roles */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);
        abort_unless(in_array($user->role, $roles, true), 403);
        abort_unless($user->tokenCan('guard:'.$user->role), 403);

        return $next($request);
    }
}
