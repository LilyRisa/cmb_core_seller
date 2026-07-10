<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Contracts\ChannelQuotaInspector;

/**
 * Tính số gian hàng còn được thêm cho 1 provider theo hạn mức "/ nền tảng" của gói.
 * Dùng cho chặn-khi-kết-nối (vd Facebook Page) ở module Messaging qua {@see ChannelQuotaInspector}.
 */
class ChannelQuotaService implements ChannelQuotaInspector
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected UsageService $usage,
    ) {}

    public function remainingForProvider(int $tenantId, string $provider): ?int
    {
        $sub = $this->subscriptions->currentFor($tenantId);
        $plan = $sub?->plan;
        if ($plan === null) {
            return null; // không có gói/subscription ⇒ không gate (an toàn, giống EnforcePlanLimit).
        }
        $perPlatform = $plan->maxChannelAccountsPerPlatform();
        if ($perPlatform < 0) {
            return null; // -1 = không giới hạn.
        }
        $used = $this->usage->channelAccountsForProvider($tenantId, $provider);

        return max(0, $perPlatform - $used);
    }
}
