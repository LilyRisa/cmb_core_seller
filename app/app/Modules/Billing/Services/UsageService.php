<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\UsageCounter;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Đếm hạn mức cho tenant. V1 chỉ 1 metric `channel_accounts` (đếm real-time, không cache).
 *
 * Lý do đếm real-time thay vì đọc `usage_counters`: dữ liệu nguồn (`channel_accounts`) là
 * tiền-điều-kiện cho cả gating + UI; nếu lệch ⇒ user bypass được gating. `usage_counters` v1
 * chỉ làm denormalized snapshot cho UI hiển thị + sau này extend (orders/tháng) thì rẻ hơn
 * khi cần lookup không qua source-of-truth.
 */
class UsageService
{
    /** Đếm số resource đang dùng của tenant. */
    public function count(int $tenantId, string $resource): int
    {
        return match ($resource) {
            'channel_accounts' => $this->channelAccounts($tenantId),
            default => 0,
        };
    }

    /** Số `channel_accounts` `status=active` của tenant. */
    public function channelAccounts(int $tenantId): int
    {
        return ChannelAccount::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('status', ChannelAccount::STATUS_ACTIVE)
            ->count();
    }

    /**
     * Cập nhật `usage_counters` denormalized — gọi từ listener / scheduler.
     * Idempotent qua unique `(tenant, metric, period)` + `updateOrCreate`.
     */
    public function refresh(int $tenantId, string $metric = 'channel_accounts'): UsageCounter
    {
        $value = $this->count($tenantId, $metric);

        return UsageCounter::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'metric' => $metric, 'period' => UsageCounter::PERIOD_CURRENT],
            ['value' => $value, 'last_updated_at' => now()],
        );
    }
}
