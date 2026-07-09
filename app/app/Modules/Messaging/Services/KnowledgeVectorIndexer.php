<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\Http;
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

    /** Vector RAG khả dụng cho tenant? (Qdrant bật + có endpoint embed chuyên dụng HOẶC provider chat có embed). */
    public function enabled(int $tenantId): bool
    {
        return $this->store->enabled() && ($this->embeddingConfigured() || $this->providerCode($tenantId) !== null);
    }

    public function model(): string
    {
        return $this->embeddingConfig()['model'];
    }

    public function collection(): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '_', mb_strtolower($this->model())) ?: 'default';

        return 'messaging_kb__'.trim($slug, '_');
    }

    /** Embed 1 đoạn text. Lỗi/không hỗ trợ ⇒ null (fail-soft). */
    public function embed(int $tenantId, string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Ưu tiên endpoint embedding CHUYÊN DỤNG (tái dùng cấu hình Hỏi AI / HELP_ASSISTANT_*) —
        // tách khỏi provider chat (tránh 403 khi cổng chat không phục vụ /v1/embeddings).
        if ($this->embeddingConfigured()) {
            return $this->embedViaDedicated($text);
        }

        // Fallback: provider chat của tenant (nếu hỗ trợ embed).
        return $this->embedViaProvider($tenantId, $text);
    }

    /** @return array{base_url:string,api_key:string,model:string} mặc định tái dùng Hỏi AI. */
    private function embeddingConfig(): array
    {
        return [
            'base_url' => rtrim((string) system_setting('help_assistant.embedding_base_url', config('messaging.ai.embedding.base_url', '')), '/'),
            'api_key' => (string) system_setting('help_assistant.embedding_api_key', config('messaging.ai.embedding.api_key', '')),
            'model' => (string) system_setting('help_assistant.embedding_model', config('messaging.ai.embedding.model', 'text-embedding-3-small')),
        ];
    }

    private function embeddingConfigured(): bool
    {
        $c = $this->embeddingConfig();

        return $c['base_url'] !== '' && $c['api_key'] !== '';
    }

    /** Embed qua endpoint OpenAI-compatible chuyên dụng (giống SupportAiClient). Lỗi ⇒ null. */
    private function embedViaDedicated(string $text): ?array
    {
        $c = $this->embeddingConfig();
        try {
            $res = Http::withToken($c['api_key'])
                ->timeout((int) config('ai.http.embed_timeout', 90))
                ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
                ->post($c['base_url'].'/v1/embeddings', ['model' => $c['model'], 'input' => $text]);
            if (! $res->successful()) {
                Log::warning('messaging.kb.embed_failed', ['source' => 'dedicated', 'status' => $res->status()]);

                return null;
            }
            $vector = array_map('floatval', (array) $res->json('data.0.embedding', []));

            return $vector !== [] ? $vector : null;
        } catch (\Throwable $e) {
            Log::warning('messaging.kb.embed_failed', ['source' => 'dedicated', 'error' => substr($e->getMessage(), 0, 200)]);

            return null;
        }
    }

    private function embedViaProvider(int $tenantId, string $text): ?array
    {
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
            Log::warning('messaging.kb.embed_failed', ['source' => 'provider', 'tenant_id' => $tenantId, 'error' => substr($e->getMessage(), 0, 200)]);

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
        $embedded = $this->upsertChunks((int) $doc->tenant_id, $chunks, ['document_id' => (int) $doc->id]);
        if ($embedded > 0) {
            $doc->forceFill([
                'embedding_provider_code' => $this->providerCode((int) $doc->tenant_id),
                'embedding_model' => $this->model(),
                'embedding_version' => (int) $doc->embedding_version + 1,
            ])->save();
        }

        return $embedded;
    }

    /** Embed + upsert chunk của 1 visual item (tri thức hợp nhất). Payload item_id. Nhận primitive (luật module). */
    public function indexItemChunks(int $itemId, int $tenantId, iterable $chunks): int
    {
        if (! $this->store->enabled()) {
            return 0;
        }

        return $this->upsertChunks($tenantId, $chunks, ['item_id' => $itemId]);
    }

    /**
     * Embed từng chunk + upsert Qdrant (batch 64) với payload `['tenant_id'=>...] + $extraPayload`.
     * Lưu embedding về chunk row. Trả số chunk đã vector hoá.
     *
     * @param  iterable<AiKnowledgeChunk>  $chunks
     * @param  array<string,int>  $extraPayload
     */
    private function upsertChunks(int $tenantId, iterable $chunks, array $extraPayload): int
    {
        $collection = $this->collection();
        $points = [];
        $embedded = 0;
        $ensured = false;

        foreach ($chunks as $chunk) {
            $vec = $this->embed($tenantId, (string) $chunk->chunk_text);
            if ($vec === null) {
                continue;
            }
            if (! $ensured) {
                $this->store->ensureCollection($collection, count($vec));
                $ensured = true;
            }
            $chunk->forceFill(['embedding' => $vec])->save();
            $points[] = [
                // Qdrant CHỈ chấp nhận point id kiểu unsigned integer hoặc UUID — id dạng CHUỖI ("123")
                // bị từ chối 400 ⇒ upsert fail-soft âm thầm ⇒ collection RỖNG ⇒ RAG luôn rơi keyword.
                'id' => (int) $chunk->id,
                'vector' => $vec,
                'payload' => ['tenant_id' => $tenantId] + $extraPayload,
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

        return $embedded;
    }

    /** Xoá point Qdrant của các chunk (cleanup khi re-index). */
    public function forget(array $chunkIds): void
    {
        // Point id phải là int (xem upsertChunks) — Qdrant từ chối id chuỗi.
        $ids = array_values(array_map('intval', $chunkIds));
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
     * @return array<int,float>|null map chunkId ⇒ score; null nếu không dùng được vector
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
     * @return array{documents:int, items:int, embedded:int, qdrant:bool}
     */
    public function reindex(?int $tenantId, bool $fresh, ?callable $log = null): array
    {
        $log ??= fn () => null;
        if (! $this->store->enabled()) {
            return ['documents' => 0, 'items' => 0, 'embedded' => 0, 'qdrant' => false];
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

        // Visual item (tri thức hợp nhất) — chunk nằm CHUNG bảng ai_knowledge_chunks (visual_item_id).
        // Trước đây reindex bỏ sót ⇒ kiến thức sản phẩm không lên Qdrant. Re-embed từ chunk_text sẵn có
        // (không cần dựng lại text qua contract) rồi upsert payload item_id.
        $itemGroups = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->whereNotNull('visual_item_id')
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('visual_item_id')->orderBy('chunk_index')->get()
            ->groupBy('visual_item_id');
        foreach ($itemGroups as $itemId => $chunks) {
            $n = $this->indexItemChunks((int) $itemId, (int) $chunks->first()->tenant_id, $chunks);
            $totalEmbedded += $n;
            $log("item #{$itemId}: {$n} chunk");
        }

        return ['documents' => $docs->count(), 'items' => $itemGroups->count(), 'embedded' => $totalEmbedded, 'qdrant' => true];
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
