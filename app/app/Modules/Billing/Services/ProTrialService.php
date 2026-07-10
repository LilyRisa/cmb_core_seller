<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Support\ProTrialSettings;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Chế độ trải nghiệm Pro" — kiểm tra tenant có đủ điều kiện dùng thử Pro miễn phí
 * trong một cửa sổ thời gian giới hạn hay không. Nguồn cấu hình: ProTrialSettings (system_setting).
 */
class ProTrialService
{
    public function __construct(protected SubscriptionService $subscriptions) {}

    /** @return array{eligible:bool,reason:?string,duration_days:int,ends_preview:?string} */
    public function eligibility(int $tenantId): array
    {
        $days = ProTrialSettings::durationDays();
        $base = ['eligible' => false, 'reason' => null, 'duration_days' => $days, 'ends_preview' => null];

        if (! ProTrialSettings::enabled()) {
            return [...$base, 'reason' => 'mode_off'];
        }
        if (! ProTrialSettings::windowOpen()) {
            return [...$base, 'reason' => 'window_closed'];
        }
        $used = ProTrialGrant::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->exists();
        if ($used) {
            return [...$base, 'reason' => 'already_used'];
        }
        $current = $this->subscriptions->currentFor($tenantId);
        $code = $current?->plan?->code;
        if ($code !== null && ! in_array($code, [Plan::CODE_TRIAL, Plan::CODE_STARTER], true)) {
            return [...$base, 'reason' => 'plan_too_high'];
        }

        return [
            'eligible' => true, 'reason' => null, 'duration_days' => $days,
            'ends_preview' => Carbon::now()->addDays($days)->toIso8601String(),
        ];
    }

    /**
     * Đăng ký dùng thử Pro — kiểm tra lại eligibility trong transaction (chống race/double-submit),
     * ghi ProTrialGrant (lưu plan/cycle/period_end cũ để revert sau này), huỷ sub hiện tại rồi
     * tạo sub Pro active mới. Ném ValidationException(422) nếu không đủ điều kiện.
     */
    public function register(int $tenantId, string $termsVersion): Subscription
    {
        return DB::transaction(function () use ($tenantId, $termsVersion) {
            $elig = $this->eligibility($tenantId);
            if (! $elig['eligible']) {
                throw ValidationException::withMessages([
                    'plan' => 'Chưa đủ điều kiện đăng ký trải nghiệm.',
                ])->status(422);
            }

            $pro = Plan::query()->where('code', Plan::CODE_PRO)->where('is_active', true)->firstOrFail();
            $current = $this->subscriptions->currentFor($tenantId);
            $days = ProTrialSettings::durationDays();
            $now = now();

            ProTrialGrant::query()->create([
                'tenant_id' => $tenantId,
                'granted_at' => $now,
                'expires_at' => $now->copy()->addDays($days),
                'previous_plan_id' => $current?->plan_id,
                'previous_cycle' => $current?->billing_cycle,
                'previous_period_end' => $current?->current_period_end,
                'terms_accepted_at' => $now,
                'terms_version' => $termsVersion,
            ]);

            if ($current !== null) {
                $current->forceFill([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => $now, 'cancel_at' => $now, 'ended_at' => $now,
                ])->save();
            }

            return Subscription::query()->create([
                'tenant_id' => $tenantId,
                'plan_id' => $pro->getKey(),
                'status' => Subscription::STATUS_ACTIVE,
                'billing_cycle' => Subscription::CYCLE_MONTHLY,
                'current_period_start' => $now,
                'current_period_end' => $now->copy()->addDays($days),
                'meta' => [
                    'pro_trial' => true,
                    'revert_plan_id' => $current?->plan_id,
                    'revert_cycle' => $current?->billing_cycle,
                    'revert_period_end' => $current?->current_period_end?->toIso8601String(),
                ],
            ]);
        });
    }
}
