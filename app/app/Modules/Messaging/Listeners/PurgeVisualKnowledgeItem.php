<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemDeleted;

/**
 * Nghe `KnowledgeItemDeleted` (VisualSearch xoá item) → xoá chunk
 * (`ai_knowledge_chunks.visual_item_id`) + gỡ vector Qdrant (fail-soft qua
 * `KnowledgeVectorIndexer::forget`), tránh rác RAG sau khi item đã mất. Chạy
 * ĐỒNG BỘ (không ShouldQueue) — thao tác rẻ, không cần supervisor riêng.
 */
class PurgeVisualKnowledgeItem
{
    public function handle(KnowledgeItemDeleted $event): void
    {
        $ids = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->where('visual_item_id', $event->itemId)->pluck('id')->all();

        app(KnowledgeVectorIndexer::class)->forget($ids);

        AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->where('visual_item_id', $event->itemId)->delete();
    }
}
