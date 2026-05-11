<?php

namespace CMBcoreSeller\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

/**
 * GET /api/v1/health — dependency probe for load balancers / uptime monitors.
 *
 * Checks the things the app can't run without (DB) plus best-effort checks for
 * Redis, cache and the queue worker. Returns 200 when every *critical* check is
 * "ok", 503 otherwise. Never throws — a failing dependency must not 500.
 * See docs/07-infra/observability-and-backup.md §4.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::connection()->select('select 1'), critical: true),
            'cache' => $this->check(function () {
                $key = 'health:'.uniqid('', true);
                Cache::put($key, 1, 5);

                return Cache::get($key) === 1 ?: throw new \RuntimeException('cache round-trip failed');
            }),
            'redis' => $this->check(fn () => Redis::connection()->ping()),
            'queue' => $this->queueCheck(),
        ];

        $ok = collect($checks)->every(fn (array $c) => $c['status'] === 'ok' || ! $c['critical']);

        return response()->json([
            'data' => [
                'status' => $ok ? 'ok' : 'degraded',
                'app' => config('app.name'),
                'env' => config('app.env'),
                'version' => trim((string) config('sentry.release')) ?: null,
                'time' => now()->toIso8601String(),
                'checks' => collect($checks)->map(fn (array $c) => collect($c)->except('critical'))->all(),
            ],
        ], $ok ? 200 : 503);
    }

    /**
     * @param  callable():mixed  $probe
     * @return array{status:string,critical:bool,error?:string}
     */
    private function check(callable $probe, bool $critical = false): array
    {
        try {
            $probe();

            return ['status' => 'ok', 'critical' => $critical];
        } catch (Throwable $e) {
            report($e);

            return ['status' => 'fail', 'critical' => $critical, 'error' => class_basename($e)];
        }
    }

    /**
     * Best-effort: is a Horizon master supervisor alive? Only meaningful when the
     * queue runs on Redis; otherwise reports "skipped".
     *
     * @return array{status:string,critical:bool,error?:string}
     */
    private function queueCheck(): array
    {
        if (config('queue.default') !== 'redis') {
            return ['status' => 'skipped', 'critical' => false];
        }

        try {
            $supervisors = app(MasterSupervisorRepository::class)->all();

            return ['status' => count($supervisors) > 0 ? 'ok' : 'fail', 'critical' => false];
        } catch (Throwable $e) {
            report($e);

            return ['status' => 'fail', 'critical' => false, 'error' => class_basename($e)];
        }
    }
}
