<?php

namespace CMBcoreSeller\Modules\VisualSearch\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Mục tri thức (visual training item) được tạo/sửa — Messaging nghe để (re)index
 * text RAG (`IndexVisualKnowledgeItem` → `IndexKnowledgeItem` job). VisualSearch
 * KHÔNG biết Messaging tồn tại — event phẳng, chỉ mang itemId.
 */
class KnowledgeItemSaved
{
    use Dispatchable;

    public function __construct(public int $itemId) {}
}
