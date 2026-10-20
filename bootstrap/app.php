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
        $middleware->trustProxies(at: '*');

        // Pins the request to the signed-in user's tenant (see ResolveTenant).
        $middleware->web(append: [\App\Http\Middleware\ResolveTenant::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
