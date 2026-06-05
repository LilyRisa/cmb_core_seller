<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Jobs\GenerateAdForecast;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdForecast;
use CMBcoreSeller\Modules\Marketing\Services\AdsForecastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * AI strategic forecast per ad account. POST generates (cooldown-guarded — within
 * the window it returns the cache WITHOUT calling AI, saving quota). GET reads cache.
 */
class AdForecastController extends Controller
{
    public function __construct(private AdsForecastService $service) {}

    /** GET /api/v1/marketing/ad-accounts/{id}/forecast — cached forecast (no AI). */
    public function show(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        return response()->json(['data' => $this->format($this->service->cached($account))]);
    }

    /** POST /api/v1/marketing/ad-accounts/{id}/forecast — async generate (cooldown-gated). */
    public function generate(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        $existing = $this->service->cached($account);
        $cooldown = (int) config('marketing.forecast_cooldown_minutes', 360);
        if ($existing !== null && $existing->generated_at->gt(now()->subMinutes($cooldown))) {
            return response()->json(['data' => $this->format($existing), 'status' => 'cached', 'queued' => false]);
        }

        GenerateAdForecast::dispatch($account->id);

        return response()->json(['data' => $this->format($existing), 'status' => 'generating', 'queued' => true]);
    }

    /** @return array<string,mixed>|null */
    private function format(?AdForecast $f): ?array
    {
        if ($f === null) {
            return null;
        }

        return [
            'payload' => $f->payload,
            'provider_code' => $f->provider_code,
            'generated_at' => $f->generated_at->toIso8601String(),
        ];
    }
}
