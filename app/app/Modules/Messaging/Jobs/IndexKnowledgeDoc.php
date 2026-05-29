<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Services\DocumentTextExtractor;
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
 * Nguồn text: inline_text | url (fetch HTML, hoặc link Google Sheets công khai →
 * export CSV) | upload (txt/md/csv/tsv/docx/xlsx/pdf qua DocumentTextExtractor;
 * không trích được ⇒ đánh `failed` reason `binary_extraction_not_implemented`).
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

    public function handle(MediaStorage $storage, DocumentTextExtractor $extractor): void
    {
        $doc = AiKnowledgeDocument::withoutGlobalScope(TenantScope::class)->find($this->documentId);
        if (! $doc) {
            return;
        }

        try {
            $text = $this->extractText($doc, $storage, $extractor);
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

    private function extractText(AiKnowledgeDocument $doc, MediaStorage $storage, DocumentTextExtractor $extractor): ?string
    {
        return match ($doc->source) {
            AiKnowledgeDocument::SOURCE_INLINE => (string) $doc->inline_text,
            AiKnowledgeDocument::SOURCE_URL => $this->fetchUrl((string) $doc->url, $extractor),
            AiKnowledgeDocument::SOURCE_UPLOAD => $this->readUpload($doc, $storage, $extractor),
            default => null,
        };
    }

    private function fetchUrl(string $url, DocumentTextExtractor $extractor): ?string
    {
        // Link Google Sheets công khai → export CSV → parse bảng (thay vì nuốt HTML app shell).
        $sheetCsv = DocumentTextExtractor::googleSheetCsvUrl($url);
        if ($sheetCsv !== null) {
            // Theo redirect (Google 307 sang googleusercontent) — Guzzle bật sẵn.
            $res = Http::timeout(20)->get($sheetCsv);
            if (! $res->successful()) {
                throw new \RuntimeException('Không tải được Google Sheet (HTTP '.$res->status().').');
            }

            // Sheet KHÔNG chia sẻ công khai ⇒ Google trả TRANG HTML (đăng nhập/xin quyền)
            // với status 200 ⇒ phải chặn, nếu không sẽ "nuốt" HTML thành CSV rác.
            $contentType = strtolower((string) $res->header('Content-Type'));
            $body = $res->body();
            if (str_contains($contentType, 'text/html') || str_starts_with(ltrim($body), '<')) {
                throw new \RuntimeException('Google Sheet chưa chia sẻ công khai. Hãy đặt quyền "Bất kỳ ai có liên kết → Người xem" rồi thử lại.');
            }

            return $extractor->extract($body, 'csv');
        }

        $res = Http::timeout(20)->get($url);
        if (! $res->successful()) {
            return null;
        }

        // Strip tags thô (HTML → text). Đủ cho keyword retrieval S6.
        return trim(html_entity_decode(strip_tags($res->body())));
    }

    private function readUpload(AiKnowledgeDocument $doc, MediaStorage $storage, DocumentTextExtractor $extractor): ?string
    {
        if (! $doc->storage_path) {
            return null;
        }
        $contents = $storage->disk()->get($doc->storage_path);
        if ($contents === null) {
            return null;
        }

        // Trích theo phần mở rộng (txt/csv/docx/xlsx/pdf…); binary không hỗ trợ ⇒ null.
        return $extractor->extract($contents, pathinfo($doc->storage_path, PATHINFO_EXTENSION));
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
