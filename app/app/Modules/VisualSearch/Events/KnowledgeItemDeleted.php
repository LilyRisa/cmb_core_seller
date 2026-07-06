<?php

namespace CMBcoreSeller\Modules\VisualSearch\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Mục tri thức (visual training item) bị xoá — Messaging nghe để purge chunk/vector
 * RAG (`PurgeVisualKnowledgeItem`), tránh rác trong `ai_knowledge_chunks`/Qdrant.
 */
class KnowledgeItemDeleted
{
    use Dispatchable;

    public function __construct(public int $itemId) {}
}
