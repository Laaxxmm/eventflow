<?php

namespace App\Http\Middleware;

use App\Domains\DomainRouter;
use App\Domains\HostName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves an organiser's custom domain (Session P1). On an active custom host, "/" is the event
 * page (or the workspace page); every other public path (the registration POST, /pay, /r,
 * /build assets) works as on the app host. Organiser and account pages redirect to the app.
 * Unknown hosts and the app's own hosts pass straight through.
 */
class RouteCustomDomain
{
    /** First path segments that belong to the app, not to a public race page. */
    public const APP_ONLY = [
        'dashboard', 'organiser', 'login', 'logout', 'register', 'signup', 'platform', 'profile', 'app',
        'account', 'pricing', 'templates', 'forgot-password', 'reset-password', 'verify-email', 'confirm-password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        if (HostName::isAppHost($host) || ($domain = DomainRouter::resolve($host)) === null) {
            return $next($request);
        }

        $first = explode('/', trim($request->path(), '/'))[0];
        if (in_array($first, self::APP_ONLY, true)) {
            return redirect()->away(rtrim((string) config('app.url'), '/').'/'.ltrim($request->getRequestUri(), '/'));
        }

        if ($request->path() !== '/') {
            return $next($request);
        }

        $server = $request->server->all();
        $query = $request->getQueryString();
        $server['REQUEST_URI'] = $domain['path'].($query ? '?'.$query : '');
        $rewritten = $request->duplicate(server: $server);

        return $next($rewritten);
    }
}
