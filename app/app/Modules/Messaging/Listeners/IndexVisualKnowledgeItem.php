<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeItem;
use CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemSaved;

/**
 * Nghe `KnowledgeItemSaved` (VisualSearch tạo/sửa item) → dispatch job
 * `IndexKnowledgeItem` (queue messaging-ai) để (re)index text RAG. Listener
 * chạy ĐỒNG BỘ (không ShouldQueue) — bản thân nó chỉ dispatch 1 job đã queued,
 * chi phí rẻ, tránh phải thêm supervisor Horizon mới.
 */
class IndexVisualKnowledgeItem
{
    public function handle(KnowledgeItemSaved $event): void
    {
        IndexKnowledgeItem::dispatch($event->itemId);
    }
}
