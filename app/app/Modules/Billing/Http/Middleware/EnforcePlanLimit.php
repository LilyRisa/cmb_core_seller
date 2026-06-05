<?php

namespace CMBcoreSeller\Modules\Billing\Http\Middleware;

use Closure;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Billing\Services\UsageService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware gating "hạn mức cứng" theo gói. SPEC 0018 §3.6.
 *
 * Cách dùng (chỉ áp lên route cần kiểm — KHÔNG áp global):
 *   Route::post('channel-accounts/{provider}/connect', ...)->middleware('plan.limit:channel_accounts');
 *
 * Trả `402 PLAN_LIMIT_REACHED` với details `{resource, current, limit, upgrade_to}` khi đạt/quá hạn mức.
 *
 * Phải chạy SAU middleware `tenant` (cần CurrentTenant). Quy ước resource hiện hỗ trợ:
 *   - `channel_accounts` → đếm `channel_accounts` `status=active` của tenant, so với `plan.limits.max_channel_accounts`.
 *
 * `-1` ở `limit` = không giới hạn.
 */
class EnforcePlanLimit
{
    public function __construct(
        protected CurrentTenant $tenant,
        protected SubscriptionService $subscriptions,
        protected UsageService $usage,
    ) {}

    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $tenantId = $this->tenant->id();
        if ($tenantId === null) {
            return response()->json(['error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Thiếu tenant.']], 400);
        }

        $sub = $this->subscriptions->currentFor($tenantId)
            ?? $this->subscriptions->ensureTrialFallback($tenantId);

        // Không có subscription (plan `trial` chưa seed) hoặc subscription trỏ tới plan bị xoá ⇒ "open"
        // (bỏ qua gating). Production luôn có plans seed; chỉ test cũ chưa migrate gói mới rơi vào case này.
        if ($sub === null) {
            return $next($request);
        }
        $plan = $sub->plan;
        if ($plan === null) {
            return $next($request);
        }

        $limit = match ($resource) {
            'channel_accounts' => $plan->maxChannelAccounts(),
            default => null,
        };

        // Resource không quản lý → để qua (gating không áp dụng).
        if ($limit === null) {
            return $next($request);
        }

        // -1 (tổng) = không giới hạn tổng, NHƯNG vẫn có thể có hạn mức theo nền tảng bên dưới.
        $current = $this->usage->count($tenantId, $resource);

        if ($limit >= 0 && $current >= $limit) {
            return response()->json([
                'error' => [
                    'code' => 'PLAN_LIMIT_REACHED',
                    'message' => "Gói hiện tại chỉ cho phép {$limit} {$resource}. Vui lòng nâng cấp để thêm.",
                    'details' => [
                        'resource' => $resource,
                        'current' => $current,
                        'limit' => $limit,
                        'plan_code' => $plan->code,
                        'upgrade_to' => $this->suggestUpgrade($plan->code),
                    ],
                ],
            ], 402);
        }

        // SPEC 0032 — per-platform cap on the marketplace connect route (has a {provider} param).
        if ($resource === 'channel_accounts') {
            $provider = $request->route('provider');
            $perPlatform = $plan->maxChannelAccountsPerPlatform();
            if (is_string($provider) && $perPlatform >= 0) {
                $usedForProvider = $this->usage->channelAccountsForProvider($tenantId, $provider);
                if ($usedForProvider >= $perPlatform) {
                    return response()->json([
                        'error' => [
                            'code' => 'PLAN_PLATFORM_LIMIT_REACHED',
                            'message' => "Gói hiện tại chỉ cho phép {$perPlatform} gian hàng cho mỗi nền tảng. Vui lòng nâng cấp để thêm.",
                            'details' => [
                                'resource' => 'channel_accounts',
                                'provider' => $provider,
                                'current' => $usedForProvider,
                                'limit' => $perPlatform,
                                'plan_code' => $plan->code,
                                'upgrade_to' => $this->suggestUpgrade($plan->code),
                            ],
                        ],
                    ], 402);
                }
            }
        }

        return $next($request);
    }

    /** Plan kế tiếp lên để gợi ý nâng cấp khi vượt hạn mức. */
    protected function suggestUpgrade(string $currentCode): string
    {
        return match ($currentCode) {
            'trial' => 'starter',
            default => 'pro',
        };
    }
}
