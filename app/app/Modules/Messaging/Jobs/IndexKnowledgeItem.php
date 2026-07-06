<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Index text của 1 mục tri thức hợp nhất (visual item) cho RAG: dựng text (qua KnowledgeItemStore)
 * → chunk → ghi ai_knowledge_chunks(visual_item_id) → embed Qdrant (fail-soft) → markIndexed qua
 * contract. Ảnh nằm ở pipeline CLIP riêng (EmbedTrainingImage) — job này CHỈ lo phần text.
 * Đọc/ghi item QUA contract (luật module: Messaging không chạm model VisualSearch). Queue messaging-ai, tries 2.
 */
class IndexKnowledgeItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $itemId)
    {
        $this->onQueue('messaging-ai');
    }

    public function handle(KnowledgeItemStore $items, KnowledgeVectorIndexer $vectorIndexer): void
    {
        $source = $items->textFor($this->itemId);
        if ($source === null) {
            return; // item đã bị xoá
        }

        try {
            // Xoá chunk cũ + point Qdrant (re-index idempotent).
            $oldIds = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
                ->where('visual_item_id', $this->itemId)->pluck('id')->all();
            $vectorIndexer->forget($oldIds);
            AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
                ->where('visual_item_id', $this->itemId)->delete();

            $chunks = $this->chunk($source->text);
            $created = [];
            foreach ($chunks as $i => $chunkText) {
                $created[] = AiKnowledgeChunk::create([
                    'tenant_id' => $source->tenantId,
                    'visual_item_id' => $this->itemId,
                    'chunk_index' => $i,
                    'chunk_text' => $chunkText,
                    'embedding' => null,
                    'token_count' => (int) ceil(mb_strlen($chunkText) / 4),
                ]);
            }

            $vectorIndexer->indexItemChunks($this->itemId, $source->tenantId, $created);
            $items->markIndexed($this->itemId, count($chunks), $vectorIndexer->model());
        } catch (\Throwable $e) {
            $items->markFailed($this->itemId);
        }
    }

    /** @return list<string> Cắt 800 ký tự (như doc free-text). */
    private function chunk(string $text, int $size = 800): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return [];
        }

        return array_values(array_filter(mb_str_split($text, $size), fn ($c) => trim($c) !== ''));
    }
}
