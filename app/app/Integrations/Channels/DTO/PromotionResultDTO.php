<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Kết quả tạo chương trình. `externalPromotionId` null với sàn KHÔNG có đối tượng chương
 * trình (Lazada — chỉ rải SalePrice theo SKU).
 */
final readonly class PromotionResultDTO
{
    public function __construct(
        public ?string $externalPromotionId,
        public string $rawStatus = '',
    ) {}
}
