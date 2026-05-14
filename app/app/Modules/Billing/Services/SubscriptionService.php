<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

/**
 * Đầu mối tạo / kết thúc / chuyển gói cho subscription. SPEC 0018 §3, §4.5.
 *
 * - `startTrial(tenant)` — khởi động trial 14 ngày khi tenant mới (idempotent: tenant đã có
 *   alive subscription ⇒ no-op).
 * - `currentFor(tenantId)` — tra subscription "đang dùng" của tenant (trialing|active|past_due).
 *   Trả null nếu không có (chưa khởi tạo, edge case migration).
 * - `ensureTrialFallback(tenantId)` — nếu tenant không có alive subscription, tự tạo trial vĩnh
 *   viễn (period_end = now + 9999d). Đảm bảo middleware/Settings không 500 vì thiếu plan.
 *
 * Mọi truy vấn ở đây bỏ TenantScope để chạy được từ context không có current tenant
 * (listener queue, scheduler, …) — luôn `where('tenant_id', $tenantId)` tường minh.
 */
class SubscriptionService
{
    public function __construct() {}

    /**
     * Khởi động trial 14 ngày cho tenant mới.
     * Idempotent: nếu tenant đã có subscription "alive" thì không tạo trùng.
     * Plan `trial` chưa seed (vd test cũ không seed) ⇒ no-op (trả null) thay vì throw.
     */
    public function startTrial(int $tenantId): ?Subscription
    {
        return DB::transaction(function () use ($tenantId): ?Subscription {
            $existing = $this->currentFor($tenantId);
            if ($existing !== null) {
                return $existing;
            }

            $plan = Plan::query()->where('code', Plan::CODE_TRIAL)->first();
            if ($plan === null) {
                return null;
            }
            $now = now();
            $endsAt = $now->copy()->addDays($plan->trial_days > 0 ? $plan->trial_days : 14);

            return Subscription::query()->create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->getKey(),
                'status' => Subscription::STATUS_TRIALING,
                'billing_cycle' => Subscription::CYCLE_TRIAL,
                'trial_ends_at' => $endsAt,
                'current_period_start' => $now,
                'current_period_end' => $endsAt,
            ]);
        });
    }

    /**
     * Subscription "đang dùng" của tenant (trialing|active|past_due) — chỉ trả 1, đảm bảo
     * bởi partial unique index. Sort theo id giảm dần như tie-breaker an toàn.
     */
    public function currentFor(int $tenantId): ?Subscription
    {
        return Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', Subscription::ALIVE_STATUSES)
            ->orderByDesc('id')
            ->with('plan')
            ->first();
    }

    /**
     * Đảm bảo tenant LUÔN có alive subscription. Dùng làm lưới an toàn cho middleware
     * (tenant cũ trước Phase 6.4 ⇒ tự cấp trial vĩnh viễn để không khoá truy cập).
     *
     * Trả null nếu plan `trial` chưa seed (môi trường test cũ / dev chưa setup) — middleware
     * gating sẽ "open" (cho qua) khi không có plan, đảm bảo hệ thống không sập vì chưa seed.
     */
    public function ensureTrialFallback(int $tenantId): ?Subscription
    {
        $current = $this->currentFor($tenantId);
        if ($current !== null) {
            return $current;
        }

        $plan = Plan::query()->where('code', Plan::CODE_TRIAL)->first();
        if ($plan === null) {
            return null;
        }
        $now = now();

        return Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_TRIAL,
            'trial_ends_at' => null,
            'current_period_start' => $now,
            // Vĩnh viễn — sau khi rớt từ paid, user vẫn login & xem đơn cũ.
            'current_period_end' => $now->copy()->addYears(50),
            'meta' => ['fallback' => true],
        ]);
    }
}
