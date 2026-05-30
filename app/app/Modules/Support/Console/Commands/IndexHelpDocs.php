<?php

namespace CMBcoreSeller\Modules\Support\Console\Commands;

use CMBcoreSeller\Modules\Support\Services\HelpIndexer;
use Illuminate\Console\Command;

/**
 * Index tài liệu trợ giúp (docs_user/rag_chunks.jsonl) vào help_chunks + Qdrant.
 *
 *   php artisan help:index            # cập nhật (idempotent)
 *   php artisan help:index --fresh    # xoá rồi index lại từ đầu
 */
class IndexHelpDocs extends Command
{
    protected $signature = 'help:index {--fresh : Xoá help_chunks cũ trước khi index}';

    protected $description = 'Index tài liệu trợ giúp (docs_user/rag_chunks.jsonl) cho trợ lý hỏi-đáp';

    public function handle(HelpIndexer $indexer): int
    {
        try {
            $stats = $indexer->index((bool) $this->option('fresh'), fn (string $m) => $this->line('  '.$m));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Xong: %d chunk, %d có vector, Qdrant=%s, provider=%s',
            $stats['total'],
            $stats['embedded'],
            $stats['qdrant'] ? 'on' : 'off',
            $stats['provider'] ?? 'none',
        ));
        if ($stats['embedded'] === 0) {
            $this->warn('Chưa tạo vector — trợ lý sẽ dùng tìm kiếm từ khoá (keyword). Cấu hình HELP_ASSISTANT_PROVIDER + QDRANT_URL để bật RAG vector.');
        }

        return self::SUCCESS;
    }
}
