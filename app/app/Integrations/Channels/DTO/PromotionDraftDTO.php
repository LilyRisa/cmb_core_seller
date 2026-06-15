<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

/**
 * Chiến dịch giảm giá chuẩn hoá để đẩy lên sàn. `discountType` đồng nhất toàn chiến dịch
 * ('percent'|'fixed'). `items` đã được core tính `salePrice` tuyệt đối.
 */
final readonly class PromotionDraftDTO
{
    /** @param  list<PromotionItemDTO>  $items */
    public function __construct(
        public string $title,
        public CarbonImmutable $startAt,
        public CarbonImmutable $endAt,
        public string $discountType,
        public array $items = [],
    ) {}
}
