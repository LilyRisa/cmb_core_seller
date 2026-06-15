<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Contracts;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\PromotionDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\PromotionItemDTO;
use CMBcoreSeller\Integrations\Channels\DTO\PromotionResultDTO;
use CMBcoreSeller\Integrations\Channels\DTO\PromotionSyncDTO;

/**
 * Trục KHUYẾN MÃI/GIẢM GIÁ (tách biệt khỏi {@see ProductPublishingConnector}). Connector
 * sàn nào hỗ trợ chương trình giảm giá nhiều SKU thì implement thêm interface này.
 *
 * Core KHÔNG biết tên sàn: chỉ làm việc qua DTO chuẩn + capability. Khác biệt sàn được
 * cô lập trong connector (Shopee/TikTok có đối tượng chương trình; Lazada chỉ rải SalePrice).
 */
interface PromotionConnector
{
    /**
     * Năng lực giảm giá của sàn (để core chunk batch + render UI khớp sàn).
     *
     * @return array{max_items_per_call:int, supports_percent:bool, has_program_object:bool, supports_time_of_day:bool}
     */
    public function promotionCapabilities(): array;

    /**
     * Tạo chương trình trên sàn. Sàn không có đối tượng chương trình (Lazada) ⇒ trả
     * `externalPromotionId = null` (no-op), core vẫn quản lý chiến dịch ở DB.
     */
    public function createPromotion(AuthContext $auth, PromotionDraftDTO $draft): PromotionResultDTO;

    /**
     * Đặt/đẩy giảm giá cho 1 BATCH item (core đã chunk theo `max_items_per_call`).
     * `$campaign` mang title/thời gian/kiểu giảm; `$itemsBatch` là tập SKU của batch này.
     *
     * @param  list<PromotionItemDTO>  $itemsBatch
     */
    public function putPromotionItems(AuthContext $auth, ?string $externalPromotionId, PromotionDraftDTO $campaign, array $itemsBatch): void;

    /**
     * Kết thúc/huỷ chương trình. Lazada: gỡ SalePrice cho `$items` (cần danh sách SKU).
     *
     * @param  list<PromotionItemDTO>  $items
     */
    public function endPromotion(AuthContext $auth, ?string $externalPromotionId, PromotionDraftDTO $campaign, array $items = []): void;

    /**
     * Liệt kê chương trình ĐANG có trên sàn (upcoming + ongoing) kèm SKU — cho đồng bộ
     * tab "đã đẩy" và phát hiện SKU đang bận. Sàn không có đối tượng chương trình ⇒ [].
     *
     * @return list<PromotionSyncDTO>
     */
    public function listPromotions(AuthContext $auth): array;
}
