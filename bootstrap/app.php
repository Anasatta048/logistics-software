<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Coolify / a reverse proxy: trust X-Forwarded-* so HTTPS URLs and
        // secure cookies are generated correctly.
        // Set TRUSTED_PROXIES to your proxy's address(es) when it is known: the demo's per-IP limit trusts the client IP they report.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES') ?: '*');

        $middleware->alias(['module' => \App\Http\Middleware\EnsurePlanModule::class]);

        // Pins the request to the signed-in user's tenant (see ResolveTenant).
        $middleware->web(append: [\App\Http\Middleware\ResolveTenant::class, \App\Http\Middleware\EnforceDemo::class, \App\Http\Middleware\SecurityHeaders::class, 'throttle:app']);

        // The tenant must be known BEFORE route-model binding runs, or `/drivers/{id}` would
        // look the record up across all tenants and only be refused afterwards.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\ResolveTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Reports unhandled errors to Sentry when SENTRY_LARAVEL_DSN is set; does nothing otherwise.
        \Sentry\Laravel\Integration::handles($exceptions);
    })->create();
