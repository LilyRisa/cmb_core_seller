<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Một SKU trong chương trình giảm giá (đã chuẩn hoá). Giá = integer VND.
 * `discountType` lấy theo chương trình (đồng nhất toàn chiến dịch — TikTok bắt buộc vậy):
 * 'percent' (giảm %) hoặc 'fixed' (đặt giá sale tuyệt đối). `salePrice` là giá sale tuyệt
 * đối ĐÃ tính sẵn ở core (connector nào cần giá tuyệt đối thì dùng thẳng).
 */
final readonly class PromotionItemDTO
{
    public function __construct(
        public string $externalProductId,
        public string $externalSkuId,
        public string $sellerSku,
        public int $basePrice,
        public string $discountType,
        public int $discountValue,
        public int $salePrice,
    ) {}
}
