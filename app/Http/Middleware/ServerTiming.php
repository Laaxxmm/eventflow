<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds `Server-Timing: app;dur=<ms>` so the browser's Network tab shows how much
 * of a slow page is the app itself versus the network/host (region, database).
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $start = defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT', microtime(true));
        $response->headers->set('Server-Timing', sprintf('app;desc="EventFlow";dur=%.1f', (microtime(true) - $start) * 1000), false);

        return $response;
    }
}
