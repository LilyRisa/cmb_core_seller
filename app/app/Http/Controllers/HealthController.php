<?php

namespace CMBcoreSeller\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

/**
 * GET /api/v1/health — dependency probe for load balancers / uptime monitors.
 *
 * Checks the things the app can't run without (DB) plus best-effort checks for
 * Redis, cache and the queue worker. Returns 200 when every *critical* check is
 * "ok", 503 otherwise. **Never throws** — a failing dependency must not 500 (it
 * just logs a warning and reports the dependency as down).
 * See docs/07-infra/observability-and-backup.md §4.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check('database', fn () => DB::connection()->select('select 1'), critical: true),
            'cache' => $this->check('cache', function () {
                $key = 'health:'.uniqid('', true);
                Cache::put($key, 1, 5);

                return Cache::get($key) === 1 ?: throw new \RuntimeException('cache round-trip failed');
            }),
            'redis' => $this->redisCheck(),
            'queue' => $this->queueCheck(),
        ];

        $ok = collect($checks)->every(fn (array $c) => in_array($c['status'], ['ok', 'skipped'], true) || ! $c['critical']);

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
    private function check(string $name, callable $probe, bool $critical = false): array
    {
        try {
            $probe();

            return ['status' => 'ok', 'critical' => $critical];
        } catch (Throwable $e) {
            Log::warning('health.check_failed', ['check' => $name, 'error' => $e->getMessage(), 'class' => $e::class]);

            return ['status' => 'fail', 'critical' => $critical, 'error' => class_basename($e)];
        }
    }

    /** Redis is best-effort: skip cleanly if the client extension isn't even loaded. */
    private function redisCheck(): array
    {
        $client = (string) config('database.redis.client', 'phpredis');
        $available = ($client === 'phpredis' && extension_loaded('redis')) || extension_loaded('relay') || $client === 'predis';
        if (! $available) {
            return ['status' => 'skipped', 'critical' => false];
        }

        return $this->check('redis', fn () => Redis::connection()->ping());
    }

    /** Best-effort: is a Horizon master supervisor alive? Only meaningful when the queue runs on Redis. */
    private function queueCheck(): array
    {
        if (config('queue.default') !== 'redis') {
            return ['status' => 'skipped', 'critical' => false];
        }

        try {
            $supervisors = app(MasterSupervisorRepository::class)->all();

            return ['status' => count($supervisors) > 0 ? 'ok' : 'fail', 'critical' => false];
        } catch (Throwable $e) {
            Log::warning('health.queue_check_failed', ['error' => $e->getMessage(), 'class' => $e::class]);

            return ['status' => 'fail', 'critical' => false, 'error' => class_basename($e)];
        }
    }
}
