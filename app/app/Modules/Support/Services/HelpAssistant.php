<?php

namespace CMBcoreSeller\Modules\Support\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Modules\Support\Models\HelpChunk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Trợ lý trả lời câu hỏi về CÁCH DÙNG hệ thống (RAG trên docs_user).
 *
 * Suy biến mượt — KHÔNG bao giờ ném:
 *   1. Vector: embed câu hỏi (provider help) → Qdrant top-K.
 *   2. Không có provider embedding / Qdrant tắt-lỗi ⇒ fallback keyword trên help_chunks.
 *   3. Có provider chat ⇒ generateReply (RAG); không có ⇒ trả thẳng câu trả lời chunk khớp nhất.
 */
class HelpAssistant
{
    private const SYSTEM = <<<'TXT'
Bạn là trợ lý hướng dẫn sử dụng phần mềm OmniSell / CMBcoreSeller (quản lý bán hàng đa sàn).
Người hỏi là NGƯỜI BÁN / nhân viên đang dùng phần mềm, KHÔNG phải khách mua hàng.
CHỈ trả lời dựa trên "Tài liệu tham khảo" bên dưới. Nếu tài liệu không đủ thông tin, hãy nói rõ là
chưa có hướng dẫn và gợi ý dùng tab "Hỏi CSKH". Trả lời tiếng Việt, ngắn gọn, theo từng bước khi cần,
nêu tên màn hình/nút (vd "Chuẩn bị hàng", trang /orders) khi phù hợp. KHÔNG bịa thông tin.
TXT;

    public function __construct(
        private AiAssistantRegistry $registry,
        private QdrantClient $qdrant,
    ) {}

    /**
     * @param  list<array{role:string, content:string}>  $history
     * @return array{answer:string, sources:list<array{title:string, module:?string, screen:?string, score:float}>, mode:string}
     */
    public function ask(string $question, array $history = [], ?int $tenantId = null): array
    {
        $question = trim($question);
        if ($question === '') {
            return ['answer' => 'Bạn vui lòng nhập câu hỏi.', 'sources' => [], 'mode' => 'empty'];
        }

        // 1) Lấy chunk liên quan (vector nếu được, không thì keyword).
        [$chunks, $retrievalMode] = $this->retrieve($question, (int) config('support.assistant.top_k', 5));

        if ($chunks === []) {
            return [
                'answer' => 'Hiện chưa có tài liệu hướng dẫn cho câu hỏi này. Bạn vui lòng dùng tab "Hỏi CSKH" để được hỗ trợ trực tiếp.',
                'sources' => [],
                'mode' => 'no_docs',
            ];
        }

        $sources = array_map(fn ($c) => [
            'title' => (string) $c['title'],
            'module' => $c['module'] ?? null,
            'screen' => $c['screen'] ?? null,
            'score' => round((float) $c['score'], 4),
        ], array_slice($chunks, 0, 3));

        // 2) Sinh câu trả lời bằng LLM (nếu có provider chat); không thì trả chunk khớp nhất.
        $answer = $this->generate($question, $history, $chunks, $tenantId);
        if ($answer !== null) {
            return ['answer' => $answer, 'sources' => $sources, 'mode' => $retrievalMode];
        }

        $best = $chunks[0];
        $fallback = (string) $best['answer'];
        $fallback .= "\n\n(Trợ lý AI chưa được cấu hình đầy đủ — đây là nội dung tài liệu liên quan nhất.)";

        return ['answer' => $fallback, 'sources' => $sources, 'mode' => $retrievalMode.'_no_llm'];
    }

    /**
     * @return array{0: list<array<string,mixed>>, 1: string}
     */
    private function retrieve(string $question, int $topK): array
    {
        // Thử vector search.
        $vector = $this->embed($question);
        if ($vector !== null && $this->qdrant->enabled()) {
            $hits = $this->qdrant->search($vector, $topK);
            if ($hits !== []) {
                $ids = array_map(fn ($h) => $h['id'], $hits);
                $scoreById = [];
                foreach ($hits as $h) {
                    $scoreById[$h['id']] = $h['score'];
                }
                $rows = HelpChunk::query()->whereIn('id', $ids)->get()
                    ->sortByDesc(fn ($r) => $scoreById[$r->id] ?? 0)
                    ->map(fn ($r) => $this->rowToArray($r, (float) ($scoreById[$r->id] ?? 0)))
                    ->values()->all();
                if ($rows !== []) {
                    return [$rows, 'rag'];
                }
            }
        }

        // Fallback keyword.
        return [$this->keywordSearch($question, $topK), 'keyword'];
    }

    /** Embed câu hỏi qua provider help. Null nếu không cấu hình / không hỗ trợ. */
    private function embed(string $text): ?array
    {
        $code = (string) config('support.assistant.provider_code', '');
        if ($code === '') {
            return null;
        }

        try {
            $connector = $this->registry->for($code);
            if (! $connector->supports('embedding')) {
                return null;
            }
            $ctx = new AiContext(
                tenantId: 0,
                providerCode: $code,
                meta: ['embedding_model' => (string) config('support.assistant.embedding_model', 'text-embedding-3-small')],
            );
            $vec = $connector->embed($ctx, $text)->vector;

            return $vec !== [] ? $vec : null;
        } catch (\Throwable $e) {
            Log::info('support.help.embed_unavailable', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Keyword-overlap trên help_chunks (giống KnowledgeRetriever) — luôn chạy được.
     *
     * @return list<array<string,mixed>>
     */
    private function keywordSearch(string $question, int $topK): array
    {
        $tokens = $this->tokenize($question);
        if ($tokens === []) {
            return [];
        }

        // Lọc thô bằng LIKE để không quét toàn bảng, rồi chấm điểm trong PHP.
        $query = HelpChunk::query();
        $query->where(function ($q) use ($tokens) {
            foreach (array_slice($tokens, 0, 6) as $t) {
                $q->orWhere('chunk_text', 'like', '%'.$t.'%')
                    ->orWhere('title', 'like', '%'.$t.'%');
            }
        });
        $candidates = $query->limit(80)->get();
        if ($candidates->isEmpty()) {
            $candidates = HelpChunk::query()->limit(200)->get();
        }

        $scored = [];
        foreach ($candidates as $row) {
            $hay = Str::lower(($row->title.' '.$row->question.' '.$row->chunk_text.' '.implode(' ', (array) $row->keywords)));
            $matches = 0;
            foreach ($tokens as $t) {
                $matches += substr_count($hay, $t);
            }
            if ($matches > 0) {
                $scored[] = $this->rowToArray($row, $matches / max(1, count($tokens)));
            }
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $topK);
    }

    /** Gọi LLM sinh câu trả lời RAG. Null nếu không có provider chat / lỗi. */
    private function generate(string $question, array $history, array $chunks, ?int $tenantId): ?string
    {
        $code = (string) config('support.assistant.provider_code', '');
        if ($code === '') {
            return null;
        }

        try {
            $connector = $this->registry->for($code);
            if (! $connector->supports('reply.suggest')) {
                return null;
            }

            $recent = [];
            foreach ($history as $h) {
                $role = (string) ($h['role'] ?? 'user');
                $recent[] = [
                    'direction' => $role === 'assistant' ? 'outbound' : 'inbound',
                    'kind' => 'text',
                    'body' => (string) ($h['content'] ?? ''),
                    'sent_at' => null,
                ];
            }
            $recent[] = ['direction' => 'inbound', 'kind' => 'text', 'body' => $question, 'sent_at' => null];

            $kb = new KnowledgeBase(array_map(fn ($c) => [
                'document_id' => (int) $c['id'],
                'title' => (string) $c['title'],
                'chunk_text' => (string) $c['chunk_text'],
                'score' => (float) $c['score'],
            ], $chunks));

            $ctx = new AiContext(
                tenantId: $tenantId ?? 0,
                providerCode: $code,
                maxTokens: (int) config('support.assistant.max_tokens', 700),
                systemPromptExtra: self::SYSTEM,
            );

            $reply = $connector->generateReply(
                $ctx,
                new ConversationSnapshot(conversationId: 0, provider: 'help', recentMessages: $recent),
                $kb,
            );

            $body = trim($reply->body);

            return $body !== '' ? $body : null;
        } catch (\Throwable $e) {
            Log::info('support.help.generate_unavailable', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function rowToArray(HelpChunk $row, float $score): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'module' => $row->module,
            'screen' => $row->screen,
            'question' => $row->question,
            'answer' => $row->answer,
            'chunk_text' => $row->chunk_text,
            'score' => $score,
        ];
    }

    /** @return list<string> token >=3 ký tự, lowercase, distinct. */
    private function tokenize(string $text): array
    {
        $text = Str::lower($text);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_values(array_unique(array_filter($parts, fn ($t) => mb_strlen($t) >= 3)));

        return array_slice($tokens, 0, 24);
    }
}
