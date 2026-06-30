<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Collection;

/**
 * Lấy top-K chunk knowledge liên quan câu hỏi để stitch vào prompt AI (RAG).
 *
 * Ưu tiên **vector (Qdrant)** — lọc theo NGỮ NGHĨA ({@see KnowledgeVectorIndexer}):
 * embed câu hỏi → Qdrant search filter `tenant_id` → lọc phạm vi page/ready ở PHP.
 * Provider không hỗ trợ embed / Qdrant tắt / không có kết quả ⇒ rơi về **keyword-
 * overlap** (đếm token trùng — chạy trên SQLite/Postgres, không cần vector).
 *
 * Dù dùng đường nào cũng chỉ trả top-K chunk (KHÔNG nhồi hết tài liệu vào prompt).
 */
class KnowledgeRetriever
{
    public function __construct(private KnowledgeVectorIndexer $vector) {}

    public function retrieve(int $tenantId, string $query, int $topK = 4, ?int $channelAccountId = null, ?string $provider = null): KnowledgeBase
    {
        $readyDocIds = $this->readyDocumentTitles($tenantId, $channelAccountId, $provider);
        if ($readyDocIds->isEmpty()) {
            return new KnowledgeBase(chunks: []);
        }

        // 1. Vector (ngữ nghĩa). Null ⇒ vector không khả dụng/không có kết quả ⇒ fallback.
        $viaVector = $this->retrieveByVector($tenantId, $query, $topK, $readyDocIds);
        if ($viaVector !== null) {
            return $viaVector;
        }

        // 2. Fallback keyword-overlap.
        return $this->retrieveByKeyword($tenantId, $query, $topK, $readyDocIds);
    }

    /**
     * Document READY thuộc tenant (+ phạm vi page SPEC 0035 + nền tảng). Trả map id ⇒ title.
     *
     * @return Collection<int,string>
     */
    private function readyDocumentTitles(int $tenantId, ?int $channelAccountId, ?string $provider = null): Collection
    {
        $docQuery = AiKnowledgeDocument::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('status', AiKnowledgeDocument::STATUS_READY);

        // Tách kho theo nền tảng: hội thoại Zalo OA KHÔNG dùng tài liệu Facebook và ngược lại.
        if ($provider !== null) {
            $docQuery->where('provider', $provider);
        }

        // SPEC 0035 — có page: chỉ tài liệu áp mọi trang HOẶC gán page này (pivot, tránh TenantScope).
        if ($channelAccountId !== null) {
            $docQuery->where(fn ($q) => $q
                ->where('applies_all_pages', true)
                ->orWhereExists(fn ($sub) => $sub
                    ->selectRaw('1')
                    ->from('ai_knowledge_document_page')
                    ->whereColumn('ai_knowledge_document_page.ai_knowledge_document_id', 'ai_knowledge_documents.id')
                    ->where('ai_knowledge_document_page.channel_account_id', $channelAccountId)));
        }

        return $docQuery->pluck('title', 'id');
    }

    /**
     * @param  Collection<int,string>  $readyDocIds
     */
    private function retrieveByVector(int $tenantId, string $query, int $topK, Collection $readyDocIds): ?KnowledgeBase
    {
        // Lấy rộng hơn topK (×5) rồi lọc theo phạm vi page/ready để vẫn đủ kết quả.
        $scores = $this->vector->searchChunkScores($tenantId, $query, $topK * 5);
        if ($scores === null || $scores === []) {
            return null;
        }

        $chunks = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('id', array_keys($scores))
            ->whereIn('document_id', $readyDocIds->keys())
            ->get(['id', 'document_id', 'chunk_text']);
        if ($chunks->isEmpty()) {
            return null;   // vector không có chunk thuộc phạm vi ⇒ thử keyword
        }

        $out = [];
        foreach ($chunks as $chunk) {
            $out[] = [
                'document_id' => (int) $chunk->document_id,
                'title' => (string) ($readyDocIds[$chunk->document_id] ?? ''),
                'chunk_text' => (string) $chunk->chunk_text,
                'score' => $scores[(int) $chunk->id] ?? 0.0,
            ];
        }
        usort($out, fn ($a, $b) => $b['score'] <=> $a['score']);

        return new KnowledgeBase(chunks: array_slice($out, 0, $topK));
    }

    /**
     * @param  Collection<int,string>  $readyDocIds
     */
    private function retrieveByKeyword(int $tenantId, string $query, int $topK, Collection $readyDocIds): KnowledgeBase
    {
        $tokens = $this->tokenize($query);
        if ($tokens === []) {
            return new KnowledgeBase(chunks: []);
        }

        $chunks = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('document_id', $readyDocIds->keys())
            ->get(['id', 'document_id', 'chunk_text']);

        $scored = [];
        foreach ($chunks as $chunk) {
            $haystack = mb_strtolower((string) $chunk->chunk_text);
            $score = 0;
            foreach ($tokens as $t) {
                if (str_contains($haystack, $t)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scored[] = [
                    'document_id' => (int) $chunk->document_id,
                    'title' => (string) ($readyDocIds[$chunk->document_id] ?? ''),
                    'chunk_text' => (string) $chunk->chunk_text,
                    'score' => $score / count($tokens),
                ];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return new KnowledgeBase(chunks: array_slice($scored, 0, $topK));
    }

    /** @return list<string> token unique, dài ≥ 3, lowercase. */
    private function tokenize(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($words as $w) {
            if (mb_strlen($w) >= 3 && ! in_array($w, $out, true)) {
                $out[] = $w;
            }
        }

        return $out;
    }
}
