<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Volunteers only see check-in: every other page sends them to /checkin.
 * Logout and email verification stay reachable.
 */
class RestrictVolunteers
{
    public const ALLOWED = ['checkin', 'checkin/*', 'logout', 'verify-email', 'verify-email/*', 'email/*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isVolunteer() && ! $request->is(...self::ALLOWED)) {
            return $request->isMethod('GET') || $request->isMethod('HEAD')
                ? redirect('/checkin')
                : abort(403);
        }

        return $next($request);
    }
}
