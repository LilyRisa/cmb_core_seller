<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Lấy top-K chunk knowledge liên quan câu hỏi để stitch vào prompt AI (RAG).
 *
 * S6 = KEYWORD-OVERLAP fallback (chạy trên SQLite/Postgres, không cần vector):
 * đếm số token câu hỏi xuất hiện trong chunk. Khi 1 connector hỗ trợ `embedding`
 * được cấu hình + Postgres pgvector bật, nâng lên cosine-similarity + HNSW index
 * (lọc tenant_id trước) — xem ADR-0020. Framework retrieval giữ nguyên; chỉ đổi
 * hàm scoring.
 */
class KnowledgeRetriever
{
    public function retrieve(int $tenantId, string $query, int $topK = 4, ?int $channelAccountId = null): KnowledgeBase
    {
        $tokens = $this->tokenize($query);
        if ($tokens === []) {
            return new KnowledgeBase(chunks: []);
        }

        // Chỉ chunk thuộc document đã ready của tenant.
        $docQuery = AiKnowledgeDocument::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('status', AiKnowledgeDocument::STATUS_READY);

        // SPEC 0035 — khi có page: chỉ tài liệu áp mọi trang HOẶC gán page này
        // (whereExists pivot, tránh TenantScope của ChannelAccount). Null ⇒ lấy tất cả (vd trợ lý help).
        if ($channelAccountId !== null) {
            $docQuery->where(fn ($q) => $q
                ->where('applies_all_pages', true)
                ->orWhereExists(fn ($sub) => $sub
                    ->selectRaw('1')
                    ->from('ai_knowledge_document_page')
                    ->whereColumn('ai_knowledge_document_page.ai_knowledge_document_id', 'ai_knowledge_documents.id')
                    ->where('ai_knowledge_document_page.channel_account_id', $channelAccountId)));
        }

        $readyDocIds = $docQuery->pluck('title', 'id');

        if ($readyDocIds->isEmpty()) {
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
