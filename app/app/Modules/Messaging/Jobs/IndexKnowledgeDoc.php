<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * Index 1 knowledge document cho RAG: lấy text → chunk → ghi `ai_knowledge_chunks`.
 *
 * S6 = "shape, not substance": chunk + lưu text (embedding NULL), retrieval dùng
 * keyword-overlap (xem `KnowledgeRetriever`). Khi 1 connector hỗ trợ `embedding`
 * được cấu hình + Postgres pgvector bật, thêm bước embed mỗi chunk ở đây (đánh
 * dấu TODO) → cosine retrieval. Framework không đổi.
 *
 * Nguồn text: inline_text | url (fetch) | upload (chỉ text/* trong S6; PDF/DOCX
 * cần parser ⇒ đánh `failed` reason `binary_extraction_not_implemented`).
 *
 * Queue: `messaging-ai`. tries 2.
 */
class IndexKnowledgeDoc implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $documentId)
    {
        $this->onQueue('messaging-ai');
    }

    public function handle(MediaStorage $storage): void
    {
        $doc = AiKnowledgeDocument::withoutGlobalScope(TenantScope::class)->find($this->documentId);
        if (! $doc) {
            return;
        }

        try {
            $text = $this->extractText($doc, $storage);
            if ($text === null) {
                $doc->update(['status' => AiKnowledgeDocument::STATUS_FAILED, 'error' => 'binary_extraction_not_implemented']);

                return;
            }

            // Xoá chunk cũ (re-index idempotent).
            AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
                ->where('document_id', $doc->id)->delete();

            $chunks = $this->chunk($text);
            foreach ($chunks as $i => $chunkText) {
                AiKnowledgeChunk::create([
                    'tenant_id' => $doc->tenant_id,
                    'document_id' => $doc->id,
                    'chunk_index' => $i,
                    'chunk_text' => $chunkText,
                    'embedding' => null, // TODO(S6.1): embed nếu provider hỗ trợ `embedding`
                    'token_count' => (int) ceil(mb_strlen($chunkText) / 4),
                ]);
            }

            $doc->update([
                'chunk_count' => count($chunks),
                'status' => AiKnowledgeDocument::STATUS_READY,
                'indexed_at' => now(),
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            $doc->update(['status' => AiKnowledgeDocument::STATUS_FAILED, 'error' => substr($e->getMessage(), 0, 250)]);
        }
    }

    private function extractText(AiKnowledgeDocument $doc, MediaStorage $storage): ?string
    {
        return match ($doc->source) {
            AiKnowledgeDocument::SOURCE_INLINE => (string) $doc->inline_text,
            AiKnowledgeDocument::SOURCE_URL => $this->fetchUrl((string) $doc->url),
            AiKnowledgeDocument::SOURCE_UPLOAD => $this->readUpload($doc, $storage),
            default => null,
        };
    }

    private function fetchUrl(string $url): ?string
    {
        $res = Http::timeout(20)->get($url);
        if (! $res->successful()) {
            return null;
        }
        // Strip tags thô (HTML → text). Đủ cho keyword retrieval S6.
        return trim(html_entity_decode(strip_tags($res->body())));
    }

    private function readUpload(AiKnowledgeDocument $doc, MediaStorage $storage): ?string
    {
        if (! $doc->storage_path) {
            return null;
        }
        // Chỉ text/* trong S6. Binary (pdf/docx) ⇒ null ⇒ caller mark failed.
        $contents = $storage->disk()->get($doc->storage_path);
        if ($contents === null) {
            return null;
        }
        // Heuristic: nội dung không phải UTF-8 text ⇒ coi như binary.
        if (! mb_check_encoding($contents, 'UTF-8')) {
            return null;
        }

        return $contents;
    }

    /** @return list<string> */
    private function chunk(string $text, int $size = 800): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return [];
        }

        return array_values(array_filter(
            mb_str_split($text, $size),
            fn ($c) => trim($c) !== '',
        ));
    }
}
