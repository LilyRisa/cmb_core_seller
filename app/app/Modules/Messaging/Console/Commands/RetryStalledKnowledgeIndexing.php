<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeItem;
use CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore;
use Illuminate\Console\Command;

/**
 * Lưới an toàn: mục "Kiến thức" (visual item) có thể kẹt kb_status=pending/failed vĩnh viễn nếu
 * job IndexKnowledgeItem lần đầu không chạy (vd race lúc deploy, mất job) — không có gì tự retry,
 * mục kẹt vô hình với AI (RAG chỉ đọc kb_status=ready) mà không cảnh báo ai. Định kỳ dò các mục
 * quá hạn rồi dispatch lại job index qua contract (luật module: không chạm model VisualSearch).
 */
class RetryStalledKnowledgeIndexing extends Command
{
    protected $signature = 'messaging:kb-retry-stalled {--minutes=15 : Ngưỡng phút coi là kẹt}';

    protected $description = 'Dò mục kiến thức kb_status pending/failed quá hạn, dispatch lại IndexKnowledgeItem.';

    public function handle(KnowledgeItemStore $items): int
    {
        $ids = $items->stalledIds((int) $this->option('minutes'));
        foreach ($ids as $id) {
            IndexKnowledgeItem::dispatch($id);
        }

        if ($ids !== []) {
            $this->info(sprintf('Đã dispatch lại index cho %d mục kẹt.', count($ids)));
        }

        return self::SUCCESS;
    }
}
