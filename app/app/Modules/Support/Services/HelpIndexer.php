<?php

namespace CMBcoreSeller\Modules\Support\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Modules\Support\Models\HelpChunk;
use Illuminate\Support\Carbon;

/**
 * Index tài liệu trợ giúp (docs_user/rag_chunks.jsonl) vào help_chunks + Qdrant.
 *
 * Mỗi dòng JSONL = {title, module, screen, question, answer, keywords[]}. Idempotent
 * theo ref_key (hash title+question). Có provider embedding ⇒ tạo vector + upsert Qdrant;
 * không có ⇒ vẫn lưu help_chunks (keyword fallback vẫn chạy được).
 */
class HelpIndexer
{
    public function __construct(
        private AiAssistantRegistry $registry,
        private QdrantClient $qdrant,
    ) {}

    /**
     * @param  callable(string):void|null  $log
     * @return array{total:int, embedded:int, qdrant:bool, provider:?string}
     */
    public function index(bool $fresh = false, ?callable $log = null): array
    {
        $log ??= fn (string $m) => null;

        $path = (string) config('support.docs_path').'/rag_chunks.jsonl';
        if (! is_file($path)) {
            throw new \RuntimeException("Không tìm thấy file RAG: {$path}");
        }

        $rows = $this->readJsonl($path);
        $log(count($rows).' chunk đọc từ rag_chunks.jsonl');

        if ($fresh) {
            HelpChunk::query()->delete();
            $log('Đã xoá help_chunks cũ (--fresh)');
        }

        $connector = $this->embeddingConnector();
        $embModel = (string) system_setting('help_assistant.embedding_model', config('support.assistant.embedding_model', 'text-embedding-3-small'));
        $providerCode = $connector ? $connector->code() : null;

        // Tạo collection nếu có provider + Qdrant bật. `--fresh` ⇒ DROP + tạo lại để
        // không còn point mồ côi (help_chunks đã xoá & cấp id mới).
        $qdrantReady = false;
        if ($connector && $this->qdrant->enabled()) {
            $dim = $this->probeDimension($connector, $embModel) ?? (int) config('support.assistant.embedding_dim', 1536);
            $qdrantReady = $fresh
                ? $this->qdrant->recreateCollection($dim)
                : $this->qdrant->ensureCollection($dim);
            $log($qdrantReady ? "Qdrant collection sẵn sàng (dim={$dim})" : 'Qdrant không sẵn sàng — chỉ keyword');
        } else {
            $log($connector ? 'Qdrant tắt (QDRANT_URL rỗng) — chỉ keyword' : 'Chưa cấu hình provider embedding — chỉ keyword');
        }

        $embedded = 0;
        $points = [];
        foreach ($rows as $r) {
            $chunkText = $this->composeText($r);
            $refKey = 'rag_chunks:'.md5(($r['title'] ?? '').'|'.($r['question'] ?? ''));

            $vector = null;
            if ($connector && $qdrantReady) {
                $vector = $this->embed($connector, $embModel, $chunkText);
            }

            $chunk = HelpChunk::query()->updateOrCreate(['ref_key' => $refKey], [
                'source' => 'rag_chunks',
                'title' => (string) ($r['title'] ?? 'Tài liệu'),
                'module' => $r['module'] ?? null,
                'screen' => $r['screen'] ?? null,
                'question' => $r['question'] ?? null,
                'answer' => (string) ($r['answer'] ?? ''),
                'keywords' => array_values((array) ($r['keywords'] ?? [])),
                'chunk_text' => $chunkText,
                'embedding_model' => $vector !== null ? $embModel : null,
                'indexed_at' => Carbon::now(),
            ]);

            if ($vector !== null) {
                $embedded++;
                $points[] = [
                    'id' => (int) $chunk->id,
                    'vector' => $vector,
                    'payload' => ['title' => $chunk->title, 'module' => $chunk->module, 'screen' => $chunk->screen],
                ];
                if (count($points) >= 64) {
                    $this->qdrant->upsert($points);
                    $points = [];
                }
            }
        }
        if ($points !== []) {
            $this->qdrant->upsert($points);
        }

        $log("Hoàn tất: {$embedded}/".count($rows).' chunk có vector');

        return [
            'total' => count($rows),
            'embedded' => $embedded,
            'qdrant' => $qdrantReady,
            'provider' => $providerCode,
        ];
    }

    private function embeddingConnector(): ?AiAssistantConnector
    {
        // Provider embedding RIÊNG nếu được đặt (vd chat=OpenRouter, embedding=OpenAI);
        // rỗng ⇒ dùng chung provider chat.
        $code = (string) system_setting('help_assistant.embedding_provider_code', config('support.assistant.embedding_provider_code', ''));
        if ($code === '') {
            $code = (string) system_setting('help_assistant.provider_code', config('support.assistant.provider_code', ''));
        }
        if ($code === '') {
            return null;
        }
        try {
            $c = $this->registry->for($code);

            return $c->supports('embedding') ? $c : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function probeDimension(AiAssistantConnector $connector, string $model): ?int
    {
        try {
            $dto = $connector->embed(new AiContext(tenantId: 0, providerCode: $connector->code(), meta: ['embedding_model' => $model]), 'ping');

            return $dto->dimension > 0 ? $dto->dimension : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<float>|null */
    private function embed(AiAssistantConnector $connector, string $model, string $text): ?array
    {
        try {
            $vec = $connector->embed(new AiContext(tenantId: 0, providerCode: $connector->code(), meta: ['embedding_model' => $model]), $text)->vector;

            return $vec !== [] ? $vec : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function composeText(array $r): string
    {
        $parts = array_filter([
            (string) ($r['title'] ?? ''),
            (string) ($r['question'] ?? ''),
            (string) ($r['answer'] ?? ''),
            implode(', ', (array) ($r['keywords'] ?? [])),
        ], fn ($s) => trim((string) $s) !== '');

        return implode("\n", $parts);
    }

    /** @return list<array<string,mixed>> */
    private function readJsonl(string $path): array
    {
        $out = [];
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return $out;
        }
        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded) && isset($decoded['title'], $decoded['answer'])) {
                $out[] = $decoded;
            }
        }
        fclose($fh);

        return $out;
    }
}
