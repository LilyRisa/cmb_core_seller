<?php

namespace CMBcoreSeller\Modules\Billing\Contracts;

/**
 * Đầu mối ĐỌC thống kê lượt gọi AI cho module khác (Admin) — theo luật module:
 * Admin chỉ phụ thuộc Contract này, không chạm bảng ai_usage_counters trực tiếp.
 */
interface AiUsageReporter
{
    /**
     * Tổng lượt AI theo user (tháng hiện tại + tất cả). user_id không có dòng ⇒ 0/0.
     *
     * @param  list<int>  $userIds
     * @return array<int, array{this_month:int, all_time:int}>
     */
    public function usageForUsers(array $userIds): array;

    /**
     * Phân rã lượt AI của 1 user: tổng, theo tháng (mới→cũ), theo tính năng (nhiều→ít).
     *
     * @return array{all_time:int, by_month:list<array{period_ym:int,count:int}>, by_feature:list<array{feature:string,count:int}>}
     */
    public function breakdownForUser(int $userId): array;

    /**
     * Phân rã lượt AI của 1 tenant: tổng, theo tháng (mới→cũ), theo tính năng (nhiều→ít).
     *
     * @return array{all_time:int, by_month:list<array{period_ym:int,count:int}>, by_feature:list<array{feature:string,count:int}>}
     */
    public function breakdownForTenant(int $tenantId): array;

    /**
     * Top N tenant theo lượt gọi AI tháng hiện tại (nhiều→ít). Dùng cho dashboard admin.
     *
     * @return list<array{tenant_id:int, calls_this_month:int}>
     */
    public function topTenantsByUsageThisMonth(int $limit): array;

    /** Tổng lượt gọi AI toàn hệ thống (mọi tenant) tháng hiện tại. Dùng cho dashboard admin. */
    public function totalCallsThisMonth(): int;
}
