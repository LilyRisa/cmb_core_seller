<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore;
use Illuminate\Support\Collection;

/**
 * Lấy top-K chunk knowledge liên quan câu hỏi để stitch vào prompt AI (RAG).
 *
 * Nguồn tri thức DUY NHẤT là "Kiến thức" (visual item — mỗi mục 1 sản phẩm, có thể kèm ảnh), chunk
 * lưu ở `ai_knowledge_chunks` (visual_item_id). Hệ tài liệu text thuần cũ (AiKnowledgeDocument) ĐÃ GỠ.
 *
 * Ưu tiên **vector (Qdrant)** — lọc theo NGỮ NGHĨA ({@see KnowledgeVectorIndexer}): embed câu hỏi →
 * Qdrant search filter `tenant_id` → lọc phạm vi page/ready ở PHP. Provider không hỗ trợ embed /
 * Qdrant tắt / không có kết quả ⇒ rơi về **keyword-overlap** (đếm token trùng). Dù đường nào cũng chỉ
 * trả top-K chunk (KHÔNG nhồi hết tri thức vào prompt).
 */
class KnowledgeRetriever
{
    /**
     * Boost cho chunk có mã/từ ĐẶC TRƯNG khớp câu hỏi (vd khách hỏi "D900" → chunk tên "…D900"
     * được cộng điểm). Các sản phẩm cùng dòng (D800/D900/D100, ZK-MT21/ZK-HT21) có mô tả ~90%
     * giống nhau ⇒ điểm ngữ nghĩa/keyword gần bằng nhau ⇒ dễ lẫn; mã model là tín hiệu phân biệt
     * chắc chắn nhất nên được đánh trọng số mạnh (lớn hơn hẳn dải điểm gốc 0..1).
     */
    private const TITLE_MATCH_BOOST = 1.0;

    /** Loại chunk có điểm thấp hơn hẳn chunk tốt nhất (× hệ số) — chống nhồi SP khác/chunk nhiễu. */
    private const RELATIVE_FLOOR = 0.55;

    public function __construct(
        private KnowledgeVectorIndexer $vector,
        private KnowledgeItemStore $items,
    ) {}

    public function retrieve(int $tenantId, string $query, int $topK = 4, ?int $channelAccountId = null, ?string $provider = null): KnowledgeBase
    {
        /** @var Collection<int,string> $readyItemIds */
        $readyItemIds = collect($this->items->readyTitles($tenantId, $channelAccountId, $provider));
        if ($readyItemIds->isEmpty()) {
            return new KnowledgeBase(chunks: []);
        }

        // 1. Vector (ngữ nghĩa). Null ⇒ vector không khả dụng/không có kết quả ⇒ fallback.
        $viaVector = $this->retrieveByVector($tenantId, $query, $topK, $readyItemIds);
        if ($viaVector !== null) {
            return $viaVector;
        }

        // 2. Fallback keyword-overlap.
        return $this->retrieveByKeyword($tenantId, $query, $topK, $readyItemIds);
    }

    /**
     * @param  Collection<int,string>  $readyItemIds
     */
    private function retrieveByVector(int $tenantId, string $query, int $topK, Collection $readyItemIds): ?KnowledgeBase
    {
        // Lấy rộng hơn topK (×5) rồi lọc theo phạm vi page/ready để vẫn đủ kết quả.
        $scores = $this->vector->searchChunkScores($tenantId, $query, $topK * 5);
        if ($scores === null || $scores === []) {
            return null;
        }

        $chunks = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('id', array_keys($scores))
            ->whereIn('visual_item_id', $readyItemIds->keys())
            ->get(['id', 'visual_item_id', 'chunk_text']);
        if ($chunks->isEmpty()) {
            return null;   // vector không có chunk thuộc phạm vi ⇒ thử keyword
        }

        $out = [];
        foreach ($chunks as $chunk) {
            $out[] = [
                'document_id' => 0,
                'title' => (string) ($readyItemIds[$chunk->visual_item_id] ?? ''),
                'chunk_text' => (string) $chunk->chunk_text,
                'score' => $scores[(int) $chunk->id] ?? 0.0,
            ];
        }

        return new KnowledgeBase(chunks: $this->rankChunks($out, $query, $topK));
    }

    /**
     * @param  Collection<int,string>  $readyItemIds
     */
    private function retrieveByKeyword(int $tenantId, string $query, int $topK, Collection $readyItemIds): KnowledgeBase
    {
        $tokens = $this->tokenize($query);
        if ($tokens === []) {
            return new KnowledgeBase(chunks: []);
        }

        $chunks = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('visual_item_id', $readyItemIds->keys())
            ->get(['id', 'visual_item_id', 'chunk_text']);

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
                    'document_id' => 0,
                    'title' => (string) ($readyItemIds[$chunk->visual_item_id] ?? ''),
                    'chunk_text' => (string) $chunk->chunk_text,
                    'score' => $score / count($tokens),
                ];
            }
        }

        return new KnowledgeBase(chunks: $this->rankChunks($scored, $query, $topK));
    }

    /**
     * Xếp hạng + lọc chunk cuối cùng (dùng chung cho vector & keyword).
     *
     *  1. Cộng {@see self::TITLE_MATCH_BOOST} cho mỗi token đặc trưng của câu hỏi trùng TÊN chunk
     *     (mã model như "d900"/"ht21") ⇒ đúng sản phẩm khách hỏi trồi lên đầu, không lẫn SP cùng dòng.
     *  2. Loại chunk điểm thấp hơn hẳn chunk tốt nhất (× {@see self::RELATIVE_FLOOR}) ⇒ bỏ nhiễu.
     *  3. Cắt top-K.
     *
     * @param  list<array{document_id:int, title:string, chunk_text:string, score?:float}>  $rows
     * @return list<array{document_id:int, title:string, chunk_text:string, score?:float}>
     */
    private function rankChunks(array $rows, string $query, int $topK): array
    {
        if ($rows === []) {
            return [];
        }

        $queryCodes = $this->codeTokens($query);
        if ($queryCodes !== []) {
            foreach ($rows as &$row) {
                $titleCodes = $this->codeTokens((string) $row['title']);
                $hits = count(array_intersect($queryCodes, $titleCodes));
                if ($hits > 0) {
                    $row['score'] = (float) ($row['score'] ?? 0.0) + ($hits * self::TITLE_MATCH_BOOST);
                }
            }
            unset($row);
        }

        usort($rows, fn ($a, $b) => ($b['score'] ?? 0.0) <=> ($a['score'] ?? 0.0));

        $best = (float) ($rows[0]['score'] ?? 0.0);
        if ($best > 0.0) {
            $floor = $best * self::RELATIVE_FLOOR;
            $rows = array_values(array_filter($rows, fn ($r) => (float) ($r['score'] ?? 0.0) >= $floor));
        }

        return array_slice($rows, 0, $topK);
    }

    /**
     * Token "mã model" — CÓ chữ số (vd "d900", "ht21", "d1600"). Đây là tín hiệu phân biệt sản phẩm
     * chắc chắn nhất giữa các SP cùng dòng: từ chung như "mạch"/"loa"/"karaoke" xuất hiện ở MỌI sản
     * phẩm nên KHÔNG dùng để boost (sẽ làm loãng, kéo cả SP khác lên). Chỉ token chứa số mới discrim.
     *
     * @return list<string>
     */
    private function codeTokens(string $text): array
    {
        $out = [];
        foreach ($this->tokenize($text) as $t) {
            if (preg_match('/\d/u', $t) === 1) {
                $out[] = $t;
            }
        }

        return $out;
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
