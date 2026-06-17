<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer;
use Illuminate\Console\Command;

/**
 * Re-embed + upsert toàn bộ chunk knowledge tin nhắn lên Qdrant (RAG vector).
 * Dùng sau khi bật embedding / đổi model / nạp lại Qdrant.
 *
 *   php artisan messaging:kb-reindex            # tất cả tenant
 *   php artisan messaging:kb-reindex --tenant=5 # 1 tenant
 *   php artisan messaging:kb-reindex --fresh    # tạo lại collection (đổi model)
 */
class ReindexKnowledge extends Command
{
    protected $signature = 'messaging:kb-reindex {--tenant= : Chỉ 1 tenant} {--fresh : Tạo lại collection Qdrant}';

    protected $description = 'Embed + upsert chunk knowledge tin nhắn lên Qdrant (RAG vector).';

    public function handle(KnowledgeVectorIndexer $indexer): int
    {
        $tenant = $this->option('tenant') !== null ? (int) $this->option('tenant') : null;
        $stats = $indexer->reindex($tenant, (bool) $this->option('fresh'), fn (string $m) => $this->line('  '.$m));

        if (! $stats['qdrant']) {
            $this->warn('Qdrant chưa bật (config integrations.vector.qdrant.url) — bỏ qua. RAG vẫn chạy keyword.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Xong: %d tài liệu, %d chunk có vector, collection=%s', $stats['documents'], $stats['embedded'], $indexer->collection()));

        return self::SUCCESS;
    }
}
