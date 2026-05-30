<?php

namespace CMBcoreSeller\Modules\Support\Services;

use CMBcoreSeller\Modules\Support\Models\HelpChunk;
use Illuminate\Support\Carbon;

/**
 * Index tài liệu trợ giúp (docs_user/rag_chunks.jsonl) vào help_chunks + Qdrant.
 *
 * Mỗi dòng JSONL = {title, module, screen, question, answer, keywords[]}. Idempotent
 * theo ref_key (hash title+question). Có cấu hình embedding (SupportAiClient) ⇒ tạo
 * vector + upsert Qdrant; không có ⇒ vẫn lưu help_chunks (keyword fallback vẫn chạy).
 *
 * TỰ CHỨA: dùng {@see SupportAiClient} — KHÔNG đụng `ai_providers`/registry messaging.
 */
class HelpIndexer
{
    public function __construct(
        private SupportAiClient $ai,
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

        $embConfigured = $this->ai->embeddingConfigured();
        $embModel = $this->ai->embeddingModel();
        $providerCode = $embConfigured ? $embModel : null;

        // Tạo collection nếu có cấu hình embedding + Qdrant bật. `--fresh` ⇒ DROP + tạo lại để
        // không còn point mồ côi (help_chunks đã xoá & cấp id mới).
        $qdrantReady = false;
        if ($embConfigured && $this->qdrant->enabled()) {
            $dim = $this->probeDimension() ?? (int) config('support.assistant.embedding_dim', 1536);
            $qdrantReady = $fresh
                ? $this->qdrant->recreateCollection($dim)
                : $this->qdrant->ensureCollection($dim);
            $log($qdrantReady ? "Qdrant collection sẵn sàng (dim={$dim})" : 'Qdrant không sẵn sàng — chỉ keyword');
        } else {
            $log($embConfigured ? 'Qdrant tắt (QDRANT_URL rỗng) — chỉ keyword' : 'Chưa cấu hình embedding — chỉ keyword');
        }

        $embedded = 0;
        $points = [];
        foreach ($rows as $r) {
            $chunkText = $this->composeText($r);
            $refKey = 'rag_chunks:'.md5(($r['title'] ?? '').'|'.($r['question'] ?? ''));

            $vector = null;
            if ($qdrantReady) {
                $vector = $this->ai->embed($chunkText);
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

    /** Dò số chiều vector thật (embed "ping"). Null nếu chưa cấu hình / lỗi → dùng dim mặc định. */
    private function probeDimension(): ?int
    {
        $vec = $this->ai->embed('ping');

        return $vec !== null && $vec !== [] ? count($vec) : null;
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
