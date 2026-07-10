<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use CMBcoreSeller\Modules\Billing\Support\ProTrialSettings;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;

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
}
