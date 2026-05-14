<?php

namespace CMBcoreSeller\Modules\Billing\Http\Middleware;

use Closure;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware gating tính năng nâng cao theo gói. SPEC 0018 §3.6.
 *
 * Cách dùng:
 *   Route::get('finance/settlements', ...)->middleware('plan.feature:finance_settlements');
 *
 * Trả `402 PLAN_FEATURE_LOCKED` khi plan không bật feature.
 *
 * Hỗ trợ truyền nhiều feature (OR): `plan.feature:procurement|fifo_cogs` — bật bất kỳ feature
 * nào trong list ⇒ qua.
 */
class EnforcePlanFeature
{
    public function __construct(
        protected CurrentTenant $tenant,
        protected SubscriptionService $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next, string $featureSpec): Response
    {
        $tenantId = $this->tenant->id();
        if ($tenantId === null) {
            return response()->json(['error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Thiếu tenant.']], 400);
        }

        $sub = $this->subscriptions->currentFor($tenantId)
            ?? $this->subscriptions->ensureTrialFallback($tenantId);

        // Không có subscription / plan ⇒ "open" (xem comment ở EnforcePlanLimit).
        if ($sub === null) {
            return $next($request);
        }
        $plan = $sub->plan;
        if ($plan === null) {
            return $next($request);
        }

        $features = explode('|', $featureSpec);

        foreach ($features as $feature) {
            $feature = trim($feature);
            if ($feature !== '' && $plan->hasFeature($feature)) {
                return $next($request);
            }
        }

        return response()->json([
            'error' => [
                'code' => 'PLAN_FEATURE_LOCKED',
                'message' => 'Tính năng này có ở gói cao hơn — vui lòng nâng cấp để mở khoá.',
                'details' => [
                    'features' => $features,
                    'plan_code' => $plan->code,
                    'upgrade_to' => 'pro',
                ],
            ],
        ], 402);
    }
}
