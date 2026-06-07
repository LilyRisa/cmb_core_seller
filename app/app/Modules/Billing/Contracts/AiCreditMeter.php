<?php

namespace CMBcoreSeller\Modules\Billing\Contracts;

use CMBcoreSeller\Modules\Billing\Services\AiCreditService;

/**
 * Đầu mối tiêu thụ / kiểm lượt gọi AI cho các module khác (Marketing, Messaging) —
 * theo luật module: chỉ phụ thuộc Contract của Billing, không chạm Services/ nội bộ.
 * Cài đặt: {@see AiCreditService} (SPEC 0032).
 */
interface AiCreditMeter
{
    /** Gói hiện tại có AI (sống + feature `ai`)? */
    public function aiEnabled(int $tenantId): bool;

    /** Còn lượt để gọi `n` lần? */
    public function canUse(int $tenantId, int $n = 1): bool;

    /**
     * Trừ `n` lượt. Ném \CMBcoreSeller\Modules\Billing\Exceptions\AiCreditException
     * (→ 402) nếu gói không có AI hoặc hết lượt.
     */
    public function consume(int $tenantId, int $n = 1): void;

    /**
     * Ghi nhận `n` lượt ĐÃ dùng — gọi SAU khi 1 request tới provider AI trả về THÀNH CÔNG.
     * KHÔNG ném (best-effort, clamp ở 0): một reply đã sinh xong không được vỡ vì hết hạn mức.
     * Bỏ qua khi gói không giới hạn / không có AI. Đây là đầu mối đếm "mỗi response provider = 1 lượt".
     */
    public function record(int $tenantId, int $n = 1): void;

    /** Cộng credit MUA (cộng dồn, ≤ 5000). Trả số thực cộng được. */
    public function grantPurchase(int $tenantId, int $amount): int;

    /**
     * @return array{enabled:bool, unlimited:bool, monthly_allowance:int, period_used:int, purchased_balance:int, available:int|null}
     */
    public function summary(int $tenantId): array;
}
