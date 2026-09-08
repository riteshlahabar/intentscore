<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\ForceHttps::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->alias(['role'=>\App\Http\Middleware\EnsureAdminRole::class]);
        // The local Instagram worker posts with a shared secret, not a browser session,
        // so it has no CSRF token to send.
        $middleware->validateCsrfTokens(except: ['api/instagram/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})->create();
