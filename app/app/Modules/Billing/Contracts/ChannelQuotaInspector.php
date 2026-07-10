<?php

namespace CMBcoreSeller\Modules\Billing\Contracts;

use CMBcoreSeller\Modules\Billing\BillingServiceProvider;

/**
 * Tra cứu hạn mức gian hàng theo gói cho các module khác (vd Messaging kết nối Facebook Page).
 *
 * Ranh giới module: module ngoài KHÔNG import Services nội bộ của Billing — chỉ dùng Contract này.
 * Bind ở {@see BillingServiceProvider}.
 */
interface ChannelQuotaInspector
{
    /**
     * Số gian hàng CÒN được thêm cho `$provider` theo hạn mức "/ nền tảng" của gói tenant.
     *
     * @return int|null null = không giới hạn (gói cho unlimited hoặc không có subscription); >=0 = số còn lại.
     */
    public function remainingForProvider(int $tenantId, string $provider): ?int;
}
