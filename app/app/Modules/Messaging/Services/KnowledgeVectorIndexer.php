<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\Log;

/**
 * RAG vector cho knowledge tin nhắn (SPEC-0024 — nâng từ keyword lên ngữ nghĩa).
 *
 * Embed chunk + câu hỏi qua **provider AI của tenant** (connector hỗ trợ `embed`,
 * vd OpenAI/custom_http) rồi upsert/search trên **Qdrant** ({@see VectorStore}).
 * TÁCH BIỆT, FAIL-SOFT: provider không hỗ trợ embed / Qdrant tắt / lỗi ⇒ trả null
 * ⇒ {@see KnowledgeRetriever} tự rơi về keyword. Filter tenant ở Qdrant; lọc theo
 * page/ready ở tầng PHP (tái dùng logic phạm vi tài liệu).
 *
 * Collection per-model: `messaging_kb__<model>` (cùng model ⇒ chung 1 collection,
 * filter `tenant_id` trong payload). Đổi model ⇒ chạy `messaging:kb-reindex --fresh`.
 */
class KnowledgeVectorIndexer
{
    public function __construct(
        private AiAssistantRegistry $registry,
        private VectorStore $store,
    ) {}

    /** Vector RAG khả dụng cho tenant? (Qdrant bật + provider có embed). */
    public function enabled(int $tenantId): bool
    {
        return $this->store->enabled() && $this->providerCode($tenantId) !== null;
    }

    public function model(): string
    {
        return (string) config('messaging.ai.embedding_model', 'text-embedding-3-small');
    }

    public function collection(): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '_', mb_strtolower($this->model())) ?: 'default';

        return 'messaging_kb__'.trim($slug, '_');
    }

    /** Embed 1 đoạn text qua provider tenant. Lỗi/không hỗ trợ ⇒ null (fail-soft). */
    public function embed(int $tenantId, string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        $code = $this->providerCode($tenantId);
        if ($code === null) {
            return null;
        }

        try {
            $connector = $this->registry->for($code);
            $dto = $connector->embed(
                new AiContext(tenantId: $tenantId, providerCode: $code, meta: ['embedding_model' => $this->model(), 'mode' => 'embedding']),
                $text,
            );

            return $dto->vector !== [] ? $dto->vector : null;
        } catch (\Throwable $e) {
            Log::warning('messaging.kb.embed_failed', ['tenant_id' => $tenantId, 'error' => substr($e->getMessage(), 0, 200)]);

            return null;
        }
    }

    /**
     * Embed + upsert các chunk của 1 tài liệu lên Qdrant. Trả số chunk đã vector hoá.
     *
     * @param  iterable<AiKnowledgeChunk>  $chunks
     */
    public function indexChunks(AiKnowledgeDocument $doc, iterable $chunks): int
    {
        if (! $this->store->enabled()) {
            return 0;
        }
        $collection = $this->collection();
        $points = [];
        $embedded = 0;
        $ensured = false;

        foreach ($chunks as $chunk) {
            $vec = $this->embed((int) $doc->tenant_id, (string) $chunk->chunk_text);
            if ($vec === null) {
                continue;
            }
            if (! $ensured) {
                $this->store->ensureCollection($collection, count($vec));
                $ensured = true;
            }
            $chunk->forceFill(['embedding' => $vec])->save();
            $points[] = [
                'id' => (string) $chunk->id,
                'vector' => $vec,
                'payload' => ['tenant_id' => (int) $doc->tenant_id, 'document_id' => (int) $doc->id],
            ];
            $embedded++;
            if (count($points) >= 64) {
                $this->store->upsert($collection, $points);
                $points = [];
            }
        }
        if ($points !== []) {
            $this->store->upsert($collection, $points);
        }
        if ($embedded > 0) {
            $doc->forceFill([
                'embedding_provider_code' => $this->providerCode((int) $doc->tenant_id),
                'embedding_model' => $this->model(),
                'embedding_version' => (int) $doc->embedding_version + 1,
            ])->save();
        }

        return $embedded;
    }

    /** Xoá point Qdrant của các chunk (cleanup khi re-index). */
    public function forget(array $chunkIds): void
    {
        $ids = array_values(array_map('strval', $chunkIds));
        if ($ids === [] || ! $this->store->enabled()) {
            return;
        }
        try {
            $this->store->deleteIds($this->collection(), $ids);
        } catch (\Throwable $e) {
            Log::warning('messaging.kb.forget_failed', ['error' => substr($e->getMessage(), 0, 200)]);
        }
    }

    /**
     * Tìm chunk gần nghĩa nhất (Qdrant) cho câu hỏi, filter theo tenant.
     * Lọc page/ready để tầng gọi tự làm trên document_id.
     *
     * @return array<int,float>|null  map chunkId ⇒ score; null nếu không dùng được vector
     */
    public function searchChunkScores(int $tenantId, string $query, int $topK): ?array
    {
        if (! $this->store->enabled()) {
            return null;
        }
        $vec = $this->embed($tenantId, $query);
        if ($vec === null) {
            return null;
        }
        try {
            $hits = $this->store->search($this->collection(), $vec, $topK, ['tenant_id' => $tenantId]);
        } catch (\Throwable $e) {
            Log::warning('messaging.kb.search_failed', ['tenant_id' => $tenantId, 'error' => substr($e->getMessage(), 0, 200)]);

            return null;
        }

        $out = [];
        foreach ($hits as $h) {
            $out[(int) $h['id']] = (float) $h['score'];
        }

        return $out;
    }

    /**
     * Re-embed + upsert toàn bộ chunk của tài liệu READY (dùng cho command).
     *
     * @return array{documents:int, embedded:int, qdrant:bool}
     */
    public function reindex(?int $tenantId, bool $fresh, ?callable $log = null): array
    {
        $log ??= fn () => null;
        if (! $this->store->enabled()) {
            return ['documents' => 0, 'embedded' => 0, 'qdrant' => false];
        }

        if ($fresh) {
            $probeTenant = $tenantId ?? (int) AiKnowledgeDocument::withoutGlobalScope(TenantScope::class)
                ->where('status', AiKnowledgeDocument::STATUS_READY)->value('tenant_id');
            $dim = $probeTenant > 0 ? $this->probeDim($probeTenant) : null;
            if ($dim !== null) {
                $this->store->recreateCollection($this->collection(), $dim);
                $log("recreate collection {$this->collection()} dim={$dim}");
            }
        }

        $docs = AiKnowledgeDocument::withoutGlobalScope(TenantScope::class)
            ->where('status', AiKnowledgeDocument::STATUS_READY)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get();

        $totalEmbedded = 0;
        foreach ($docs as $doc) {
            $chunks = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
                ->where('document_id', $doc->id)->orderBy('chunk_index')->get();
            $n = $this->indexChunks($doc, $chunks);
            $totalEmbedded += $n;
            $log("doc #{$doc->id} \"{$doc->title}\": {$n} chunk");
        }

        return ['documents' => $docs->count(), 'embedded' => $totalEmbedded, 'qdrant' => true];
    }

    private function probeDim(int $tenantId): ?int
    {
        $vec = $this->embed($tenantId, 'ping');

        return $vec !== null ? count($vec) : null;
    }

    /** Provider AI tenant chọn (fallback: provider active đầu tiên). Không có ⇒ null. */
    private function providerCode(int $tenantId): ?string
    {
        $active = $this->registry->activeProviders();
        if ($active === []) {
            return null;
        }
        $chosen = MessagingSetting::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->value('ai_provider_code');

        return ($chosen && in_array($chosen, $active, true)) ? (string) $chosen : $active[0];
    }
}
