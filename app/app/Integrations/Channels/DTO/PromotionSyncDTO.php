<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

/**
 * Một chương trình giảm giá đọc TỪ sàn (đồng bộ tab "đã đẩy"). `status` chuẩn hoá:
 * upcoming | ongoing | ended.
 */
final readonly class PromotionSyncDTO
{
    /** @param  list<array{external_product_id:string,external_sku_id:string,sale_price:int}>  $items */
    public function __construct(
        public string $externalPromotionId,
        public string $title,
        public string $status,
        public ?CarbonImmutable $startAt,
        public ?CarbonImmutable $endAt,
        public array $items = [],
    ) {}
}
