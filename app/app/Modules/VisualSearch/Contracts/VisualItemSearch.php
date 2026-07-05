<?php

namespace CMBcoreSeller\Modules\VisualSearch\Contracts;

use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemImage;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualLookupOptions;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;

/**
 * Cổng tiêu thụ visual search cho module khác (Messaging) + API seller.
 * Triết lý: KHÔNG ném — tắt/lỗi/không khớp ⇒ VisualMatchResult::notFound() / [].
 */
interface VisualItemSearch
{
    public function lookup(int $tenantId, VisualImageInput $image, VisualLookupOptions $opts): VisualMatchResult;

    /**
     * Tra item theo TÊN/mã trong câu khách nhắn (case-insensitive). 1 khớp ⇒ matched,
     * nhiều ⇒ ambiguous, không ⇒ not_found. Lọc per-page nếu có $channelAccountId.
     */
    public function findByName(int $tenantId, string $text, ?int $channelAccountId = null): VisualMatchResult;

    /**
     * Ảnh của 1 item (ảnh primary trước), tối đa $limit. Bytes đọc từ disk của module.
     * Không có / lỗi đọc ⇒ [].
     *
     * @return list<VisualItemImage>
     */
    public function imagesForItem(int $tenantId, int $itemId, int $limit = 3): array;
}
