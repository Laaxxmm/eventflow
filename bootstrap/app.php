<?php

use App\Http\Middleware\RestrictVolunteers;
use App\Http\Middleware\RouteCustomDomain;
use App\Http\Middleware\ServerTiming;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->web(append: [
            RestrictVolunteers::class,
        ]);

        $middleware->append(ServerTiming::class);
        // Session P1: an organiser's own domain serves their event page (before routing).
        $middleware->append(RouteCustomDomain::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
