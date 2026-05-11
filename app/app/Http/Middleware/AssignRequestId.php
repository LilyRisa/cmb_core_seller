<?php

namespace CMBcoreSeller\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assigns a stable `request_id` (a.k.a. trace_id) to every request, shares it
 * with the log context (so every line carries it — see
 * docs/07-infra/observability-and-backup.md §1), pins it on the Sentry scope,
 * exposes it on the request (`$request->attributes->get('request_id')`) so the
 * exception handler can return it in the error envelope, and echoes it back as
 * the `X-Request-Id` response header for client-side correlation.
 *
 * Honours an inbound `X-Request-Id` when present (e.g. set by a load balancer).
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) $request->headers->get('X-Request-Id') ?: (string) Str::uuid();

        $request->attributes->set('request_id', $id);

        Log::shareContext(['request_id' => $id]);

        if (class_exists(Scope::class)) {
            \Sentry\configureScope(function (Scope $scope) use ($id): void {
                $scope->setTag('request_id', $id);
            });
        }

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}
