<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Một chỉ số hiệu suất/sức khỏe của gian hàng (1 dòng trong scorecard).
 * Chuẩn hoá từ Lazada `/seller/performance/get`, Shopee `account_health.get_shop_performance`,
 * TikTok analytics — để FE hiển thị đồng nhất (giá trị · mục tiêu · đạt/không).
 */
final readonly class ShopMetricDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $key,
        public string $name,
        public string $group,        // fulfillment | listing | customer_service | rating | sales | other
        public ?float $value,
        public string $unit,         // percent | number | second | day | hour | minute | money
        public ?float $target = null,
        public ?string $comparator = null,  // <, <=, >, >=, =
        public ?bool $passed = null,        // value có đạt target không (nếu xác định được)
        public array $raw = [],
    ) {}
}
