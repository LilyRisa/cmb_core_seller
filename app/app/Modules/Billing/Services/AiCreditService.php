<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Billing\Exceptions\AiCreditException;
use CMBcoreSeller\Modules\Billing\Models\AiCreditWallet;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Lượt gọi AI của tenant (SPEC 0032).
 *
 * Mỗi kỳ: hạn mức TẶNG kèm gói (`plan.aiCreditsMonthly`, reset theo tháng dương lịch) + credit MUA
 * thêm (vĩnh viễn, cộng dồn ≤ 5000). Tiêu thụ: trừ hạn mức tặng trước, hết thì trừ credit mua.
 *
 * CHẶN khi hạ gói: chỉ dùng được khi gói hiện tại đang sống & có feature `ai`. Hạ gói/không gia hạn
 * ⇒ không gọi được (credit mua vẫn giữ nguyên, dùng lại khi nâng cấp). Gói có `ai_credits_monthly = -1`
 * ⇒ không giới hạn.
 */
class AiCreditService implements AiCreditMeter
{
    public function __construct(private SubscriptionService $subscriptions) {}

    /** Gói đang sống của tenant (null nếu không có/không sống). */
    private function activePlan(int $tenantId): ?Plan
    {
        $sub = $this->subscriptions->currentFor($tenantId);

        return $sub !== null && $sub->isAlive() ? $sub->plan : null;
    }

    /** AI bật? gói sống + có feature `ai`. */
    public function aiEnabled(int $tenantId): bool
    {
        return $this->activePlan($tenantId)?->hasFeature('ai') ?? false;
    }

    /** Gói AI không giới hạn lượt? (`ai_credits_monthly = -1`). */
    public function unlimited(int $tenantId): bool
    {
        $plan = $this->activePlan($tenantId);

        return $plan !== null && $plan->hasFeature('ai') && $plan->aiCreditsMonthly() < 0;
    }

    /** Hạn mức tặng kèm gói mỗi kỳ (0 nếu gói không có AI). */
    public function monthlyAllowance(int $tenantId): int
    {
        $plan = $this->activePlan($tenantId);
        if ($plan === null || ! $plan->hasFeature('ai')) {
            return 0;
        }

        return max(0, $plan->aiCreditsMonthly());
    }

    public function wallet(int $tenantId): AiCreditWallet
    {
        /** @var AiCreditWallet $w */
        $w = AiCreditWallet::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['purchased_balance' => 0, 'period_used' => 0, 'period_anchor' => now()->startOfDay()],
        );
        // Reset hạn mức tặng khi sang tháng mới.
        if ($w->period_anchor === null || $w->period_anchor->format('Y-m') !== now()->format('Y-m')) {
            $w->forceFill(['period_used' => 0, 'period_anchor' => now()->startOfDay()])->save();
        }

        return $w;
    }

    /** wallet() có khoá row (FOR UPDATE) — dùng trong mọi nhánh ghi (consume/record/grantPurchase/deduct). */
    private function lockedWallet(int $tenantId): AiCreditWallet
    {
        /** @var AiCreditWallet $w */
        $w = AiCreditWallet::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['purchased_balance' => 0, 'period_used' => 0, 'period_anchor' => now()->startOfDay()],
        );
        $w = AiCreditWallet::withoutGlobalScope(TenantScope::class)->where('id', $w->id)->lockForUpdate()->first();
        if ($w->period_anchor === null || $w->period_anchor->format('Y-m') !== now()->format('Y-m')) {
            $w->forceFill(['period_used' => 0, 'period_anchor' => now()->startOfDay()])->save();
        }

        return $w;
    }

    /** Lượt còn dùng được (PHP_INT_MAX nếu không giới hạn). */
    public function available(int $tenantId): int
    {
        if ($this->unlimited($tenantId)) {
            return PHP_INT_MAX;
        }
        $w = $this->wallet($tenantId);

        return max(0, $this->monthlyAllowance($tenantId) - $w->period_used) + $w->purchased_balance;
    }

    public function canUse(int $tenantId, int $n = 1): bool
    {
        if (! $this->aiEnabled($tenantId)) {
            return false;
        }

        return $this->unlimited($tenantId) || $this->available($tenantId) >= $n;
    }

    /**
     * Trừ `n` lượt (hạn mức tặng trước, rồi credit mua). Ném {@see AiCreditException} nếu
     * gói không có AI hoặc hết lượt.
     */
    public function consume(int $tenantId, int $n = 1): void
    {
        if (! $this->aiEnabled($tenantId)) {
            throw AiCreditException::unavailable();
        }
        if ($this->unlimited($tenantId)) {
            return;
        }
        DB::transaction(function () use ($tenantId, $n) {
            $w = $this->lockedWallet($tenantId);
            $allowanceLeft = max(0, $this->monthlyAllowance($tenantId) - $w->period_used);
            if ($allowanceLeft + $w->purchased_balance < $n) {
                throw AiCreditException::exhausted();
            }
            $fromAllowance = min($n, $allowanceLeft);
            $w->forceFill([
                'period_used' => $w->period_used + $fromAllowance,
                'purchased_balance' => $w->purchased_balance - ($n - $fromAllowance),
            ])->save();
        });
    }

    /**
     * Ghi nhận `n` lượt đã dùng SAU khi provider trả thành công (best-effort, KHÔNG ném).
     * Trừ hạn mức tặng trước rồi credit mua; clamp ở 0 (không âm). Bỏ qua wallet khi unlimited /
     * gói không có AI, NHƯNG luôn đếm vào ai_usage_counters (đã có 1 call thực sự xảy ra).
     * Khác {@see consume} (consume gate trước + ném khi hết).
     */
    public function record(int $tenantId, int $n = 1, ?string $feature = null, ?int $userId = null): void
    {
        if ($n <= 0) {
            return;
        }

        // Đếm lượt gọi AI (kể cả gói không giới hạn / không có AI — đã có 1 call thực sự xảy ra).
        $this->countUsage($tenantId, $n, $feature, $userId);

        if (! $this->aiEnabled($tenantId) || $this->unlimited($tenantId)) {
            return;
        }
        DB::transaction(function () use ($tenantId, $n) {
            $w = $this->lockedWallet($tenantId);
            $allowanceLeft = max(0, $this->monthlyAllowance($tenantId) - $w->period_used);
            $fromAllowance = min($n, $allowanceLeft);
            $fromPurchase = min($n - $fromAllowance, $w->purchased_balance);
            if ($fromAllowance === 0 && $fromPurchase === 0) {
                return;
            }
            $w->forceFill([
                'period_used' => $w->period_used + $fromAllowance,
                'purchased_balance' => $w->purchased_balance - $fromPurchase,
            ])->save();
        });
    }

    /** Best-effort: tăng bộ đếm lượt AI theo (tenant, user, tháng, tính năng). Không ném. */
    private function countUsage(int $tenantId, int $n, ?string $feature, ?int $userId): void
    {
        try {
            $uid = $userId ?? Auth::id() ?? 0;
            $ym = (int) now()->format('Ym');
            $feat = $feature ?? 'other';

            DB::transaction(function () use ($tenantId, $uid, $ym, $feat, $n) {
                $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                    ['tenant_id' => $tenantId, 'user_id' => (int) $uid, 'period_ym' => $ym, 'feature' => $feat],
                    ['count' => 0],
                );
                $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)
                    ->where('id', $row->id)->lockForUpdate()->first();
                $row->increment('count', $n);
            });
        } catch (\Throwable) {
            // Đếm lỗi không được phép làm vỡ luồng AI.
        }
    }

    /** Cộng credit MUA (cộng dồn, chặn trên 5000). Trả số thực cộng được. */
    public function grantPurchase(int $tenantId, int $amount): int
    {
        return DB::transaction(function () use ($tenantId, $amount) {
            $w = $this->lockedWallet($tenantId);
            $new = min(AiCreditWallet::PURCHASE_MAX_BALANCE, $w->purchased_balance + $amount);
            $added = $new - $w->purchased_balance;
            $w->forceFill(['purchased_balance' => $new])->save();

            return $added;
        });
    }

    /** Trừ credit MUA (sàn 0, không âm). Trả số thực trừ được (dương). */
    public function deduct(int $tenantId, int $amount): int
    {
        return DB::transaction(function () use ($tenantId, $amount) {
            $w = $this->lockedWallet($tenantId);
            $new = max(0, $w->purchased_balance - $amount);
            $removed = $w->purchased_balance - $new;
            $w->forceFill(['purchased_balance' => $new])->save();

            return $removed;
        });
    }

    /**
     * Tóm tắt cho UI / header.
     *
     * @return array{enabled:bool, unlimited:bool, monthly_allowance:int, period_used:int, purchased_balance:int, available:int|null}
     */
    public function summary(int $tenantId): array
    {
        $w = $this->wallet($tenantId);
        $unlimited = $this->unlimited($tenantId);

        return [
            'enabled' => $this->aiEnabled($tenantId),
            'unlimited' => $unlimited,
            'monthly_allowance' => $this->monthlyAllowance($tenantId),
            'period_used' => $w->period_used,
            'purchased_balance' => $w->purchased_balance,
            'available' => $unlimited ? null : max(0, $this->monthlyAllowance($tenantId) - $w->period_used) + $w->purchased_balance,
        ];
    }
}
