<?php

namespace CMBcoreSeller\Modules\VisualSearch\Contracts;

use CMBcoreSeller\Modules\VisualSearch\DTO\KnowledgeItemText;

/**
 * Cổng cho Messaging index/truy hồi text của mục tri thức hợp nhất (visual item).
 * Giữ VisualSearch sở hữu model — Messaging chỉ chạm qua interface (luật module).
 */
interface KnowledgeItemStore
{
    public function textFor(int $itemId): ?KnowledgeItemText;

    /** @return array<int,string> itemId ⇒ name (item kb_status=ready, đúng scope). */
    public function readyTitles(int $tenantId, ?int $channelAccountId, ?string $provider): array;

    public function markIndexed(int $itemId, int $chunkCount, ?string $embeddingModel): void;

    public function markFailed(int $itemId): void;

    /** @return list<int> id các mục kb_status pending/failed đã quá hạn (chưa index xong sau X phút). */
    public function stalledIds(int $olderThanMinutes): array;
}
