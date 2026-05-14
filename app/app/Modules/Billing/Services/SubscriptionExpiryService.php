<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

/**
 * Logic gia hạn / hết hạn / fallback trial — SPEC 0018 §3.4.
 *
 * Đầy đủ luồng:
 *   - Subscription `current_period_end < now` & chưa thanh toán ⇒ `past_due`.
 *   - `past_due` quá 7 ngày ⇒ `expired` + auto-tạo subscription trial vĩnh viễn cho tenant.
 *   - Trial hết hạn (trial_ends_at < now) ⇒ `expired` ngay (không grace) — user phải checkout.
 *
 * Idempotent: chạy lại không lặp side-effect (chỉ chuyển state theo điều kiện).
 *
 * Lưu ý: việc tạo invoice gia hạn (-7 ngày) sẽ wire ở PR2 sau khi gateway thật sẵn sàng;
 * v1 chỉ chuyển state + fallback (không tự tạo invoice — yêu cầu user vào /settings/plan checkout).
 */
class SubscriptionExpiryService
{
    public function __construct(protected SubscriptionService $subscriptions) {}

    /**
     * Quét toàn bộ subscriptions "alive" và áp luật state machine.
     *
     * @return array{past_due:int,expired:int,fallback_created:int}
     */
    public function run(): array
    {
        $now = now();
        $past = 0;
        $expired = 0;
        $fallback = 0;

        // 1) Trial hết hạn ⇒ expired ngay.
        Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('status', Subscription::STATUS_TRIALING)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', $now)
            ->orderBy('id')
            ->each(function (Subscription $sub) use (&$expired, &$fallback) {
                DB::transaction(function () use ($sub, &$expired, &$fallback) {
                    $sub->forceFill([
                        'status' => Subscription::STATUS_EXPIRED,
                        'ended_at' => now(),
                    ])->save();
                    $expired++;
                    if ($this->createTrialFallbackIfMissing((int) $sub->tenant_id)) {
                        $fallback++;
                    }
                });
            });

        // 2) Active quá hạn (chưa paid) ⇒ past_due.
        Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('current_period_end', '<', $now)
            ->orderBy('id')
            ->each(function (Subscription $sub) use (&$past) {
                $sub->forceFill(['status' => Subscription::STATUS_PAST_DUE])->save();
                $past++;
            });

        // 3) Past due quá 7 ngày ⇒ expired + fallback trial.
        $graceDeadline = $now->copy()->subDays(7);
        Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('status', Subscription::STATUS_PAST_DUE)
            ->where('current_period_end', '<', $graceDeadline)
            ->orderBy('id')
            ->each(function (Subscription $sub) use (&$expired, &$fallback) {
                DB::transaction(function () use ($sub, &$expired, &$fallback) {
                    $sub->forceFill([
                        'status' => Subscription::STATUS_EXPIRED,
                        'ended_at' => now(),
                    ])->save();
                    $expired++;
                    if ($this->createTrialFallbackIfMissing((int) $sub->tenant_id)) {
                        $fallback++;
                    }
                });
            });

        // 4) Subscription đã cancel + chạy hết kỳ ⇒ expired + fallback.
        Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('status', Subscription::STATUS_CANCELLED)
            ->whereNotNull('cancel_at')
            ->where('cancel_at', '<', $now)
            ->orderBy('id')
            ->each(function (Subscription $sub) use (&$expired, &$fallback) {
                DB::transaction(function () use ($sub, &$expired, &$fallback) {
                    $sub->forceFill([
                        'status' => Subscription::STATUS_EXPIRED,
                        'ended_at' => now(),
                    ])->save();
                    $expired++;
                    if ($this->createTrialFallbackIfMissing((int) $sub->tenant_id)) {
                        $fallback++;
                    }
                });
            });

        return [
            'past_due' => $past,
            'expired' => $expired,
            'fallback_created' => $fallback,
        ];
    }

    /** Tạo trial fallback nếu tenant không còn alive subscription. Trả true nếu đã tạo. */
    protected function createTrialFallbackIfMissing(int $tenantId): bool
    {
        $exists = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', Subscription::ALIVE_STATUSES)
            ->exists();
        if ($exists) {
            return false;
        }
        $plan = Plan::query()->where('code', Plan::CODE_TRIAL)->first();
        if ($plan === null) {
            return false;
        }
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_TRIAL,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addYears(50),
            'meta' => ['fallback' => true, 'created_from' => 'expiry'],
        ]);

        return true;
    }
}
