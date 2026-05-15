<?php

namespace CMBcoreSeller\Modules\Billing\Http\Middleware;

use Closure;
use CMBcoreSeller\Modules\Billing\Services\OverQuotaCheckService;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SPEC 0020 — chặn ghi khi tenant vượt hạn mức quá grace period.
 *
 * Áp lên route group `tenant`. Logic:
 *   1. Chỉ áp cho write methods (POST/PATCH/PUT/DELETE).
 *   2. Whitelist các path quan trọng để user thoát lock được:
 *      - /billing/*           — nâng cấp gói
 *      - /channel-accounts/{id} DELETE  — gỡ kênh thừa
 *      - /auth/*              — đăng xuất, đổi profile
 *      - /admin/*             — không tới đây (admin không qua middleware `tenant`)
 *      - /media/image POST    — phục vụ form ảnh (banner upload, không tính là "tính năng nghiệp vụ")
 *   3. Subscription = null / plan = null ⇒ open (như EnforcePlanLimit hiện tại).
 *   4. Past grace + đang vượt ⇒ trả 402 `PLAN_QUOTA_EXCEEDED` với details `{resources, plan_code, warned_at}`.
 *
 * Idempotent + cheap: chỉ query subscription 1 lần per request (đã cache bởi Laravel container).
 */
class EnforcePlanQuotaLock
{
    /** Path prefix (sau `/api/v1/`) miễn áp khoá. */
    private const WHITELIST_PREFIX = [
        'billing/',
        'auth/',
        'media/image',
        'tenant',           // GET /tenant + members (read-mostly; controller tự gate `tenant.update`)
        'tenants',          // listing tenants user thuộc về
    ];

    public function __construct(
        protected CurrentTenant $tenant,
        protected SubscriptionService $subscriptions,
        protected OverQuotaCheckService $check,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Read methods (GET, HEAD, OPTIONS) — không tính, đi qua.
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Whitelist path để user thoát lock được + super-admin (admin không qua đây vì /admin/*
        // KHÔNG có middleware tenant).
        if ($this->isWhitelisted($request)) {
            return $next($request);
        }

        $tenantId = $this->tenant->id();
        if ($tenantId === null) {
            return $next($request); // không có tenant ⇒ middleware tenant sẽ xử lý
        }

        $sub = $this->subscriptions->currentFor($tenantId);
        if ($sub === null || $sub->plan === null) {
            return $next($request); // open (giống EnforcePlanLimit khi chưa seed plans)
        }

        if (! $this->check->isPastGrace($sub)) {
            return $next($request);
        }

        // Vẫn còn vượt? Re-check để tránh kẹt lock khi user đã gỡ kênh nhưng scheduler chưa chạy.
        $over = $this->check->overResources($sub);
        if ($over === []) {
            // Self-heal: clear timer rồi cho qua.
            $sub->forceFill(['over_quota_warned_at' => null])->save();

            return $next($request);
        }

        return response()->json([
            'error' => [
                'code' => 'PLAN_QUOTA_EXCEEDED',
                'message' => 'Tài khoản đang vượt hạn mức gói — vui lòng nâng cấp gói hoặc gỡ bớt kết nối thừa.',
                'details' => [
                    'resources' => $over,
                    'plan_code' => $sub->plan->code,
                    'warned_at' => $sub->over_quota_warned_at?->toIso8601String(),
                    'grace_hours' => (int) config('billing.over_quota_grace_hours', 48),
                ],
            ],
        ], 402);
    }

    private function isWhitelisted(Request $request): bool
    {
        // path() trả KHÔNG dấu `/` đầu; ví dụ "api/v1/billing/checkout".
        $path = $request->path();
        $rest = str_starts_with($path, 'api/v1/') ? substr($path, 7) : $path;

        // Cho phép DELETE /channel-accounts/{id} — user gỡ kênh thừa.
        if ($request->method() === 'DELETE'
            && preg_match('#^channel-accounts/\d+$#', $rest) === 1) {
            return true;
        }

        foreach (self::WHITELIST_PREFIX as $prefix) {
            if (str_starts_with($rest, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
