<?php

use CMBcoreSeller\Http\Middleware\AssignRequestId;
use CMBcoreSeller\Modules\Tenancy\Http\Middleware\EnsureTenant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Every request gets a request_id / trace_id (log context + Sentry tag +
        // X-Request-Id header). Runs first so it covers the whole pipeline.
        $middleware->prepend(AssignRequestId::class);

        // Sanctum SPA: cookie-based auth for same-domain frontend on the `api` group.
        $middleware->statefulApi();

        // Unauthenticated browser hits go to the SPA login (API clients sending
        // Accept: application/json get a 401 JSON instead). Avoids relying on a
        // named `login` route.
        $middleware->redirectGuestsTo('/login');

        $middleware->alias([
            'tenant' => EnsureTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Report unhandled exceptions to Sentry (web + queue). No-op without a DSN.
        Integration::handles($exceptions);

        // /api/* and /webhook/* always speak JSON — never redirect to a login page.
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*', 'webhook/*') || $request->expectsJson()
        );

        // Normalize JSON error responses to the {error:{code,message,...}} envelope
        // (see docs/05-api/conventions.md). Controllers already use this shape;
        // this covers framework-thrown exceptions.
        $envelope = function (Throwable $e, Request $request) {
            if (! ($request->is('api/*', 'webhook/*') || $request->expectsJson())) {
                return null; // let the default (HTML / redirect) handler run for web routes
            }

            [$status, $code] = match (true) {
                $e instanceof ValidationException => [422, 'VALIDATION_FAILED'],
                $e instanceof AuthenticationException => [401, 'UNAUTHENTICATED'],
                $e instanceof AuthorizationException => [403, 'FORBIDDEN'],
                $e instanceof ModelNotFoundException => [404, 'NOT_FOUND'],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), match ($e->getStatusCode()) {
                    400 => 'BAD_REQUEST', 403 => 'FORBIDDEN', 404 => 'NOT_FOUND', 405 => 'METHOD_NOT_ALLOWED',
                    409 => 'CONFLICT', 419 => 'PAGE_EXPIRED', 429 => 'TOO_MANY_REQUESTS', default => 'HTTP_ERROR',
                }],
                default => [500, 'SERVER_ERROR'],
            };

            $body = ['error' => [
                'code' => $code,
                'message' => $e instanceof ValidationException
                    ? 'Dữ liệu không hợp lệ.'
                    : ($status < 500 ? $e->getMessage() : 'Đã có lỗi xảy ra phía máy chủ.'),
                'trace_id' => $request->attributes->get('request_id'),
            ]];

            if ($e instanceof ValidationException) {
                $body['error']['details'] = $e->errors();
            }
            if (config('app.debug') && $status >= 500) {
                $body['error']['debug'] = ['exception' => $e::class, 'message' => $e->getMessage()];
            }

            return response()->json($body, $status);
        };

        $exceptions->render(fn (Throwable $e, Request $request) => $envelope($e, $request));
    })->create();
