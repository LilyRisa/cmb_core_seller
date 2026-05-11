<?php

use CMBcoreSeller\Modules\Tenancy\Http\Middleware\EnsureTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
        then: function () {
            // Marketplace / carrier webhooks: no CSRF, no auth — verified by signature.
            Route::middleware('api')
                ->prefix('webhook')
                ->name('webhook.')
                ->group(base_path('routes/webhook.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sanctum SPA: cookie-based auth for same-domain frontend on the `api` group.
        $middleware->statefulApi();

        $middleware->alias([
            'tenant' => EnsureTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
