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

            // Dữ liệu BẢNG (Google Sheet / CSV / TSV / XLSX): mỗi HÀNG = 1 chunk (mỗi sản phẩm
            // một bản ghi riêng) ⇒ RAG khớp đúng sản phẩm, không trộn lẫn. Văn bản tự do: cắt theo độ dài.
            $chunks = $this->isTabular($doc) ? $this->chunkByRow($text) : $this->chunk($text);
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
        // Google Sheets công khai: ưu tiên gviz JSON (đầu ra UTF-8 structured — không lo
        // CSV escaping / xuống dòng trong ô), fallback CSV export nếu gviz lỗi.
        if (DocumentTextExtractor::googleSheetId($url) !== null) {
            return $this->fetchGoogleSheet($url, $extractor);
        }

        $res = Http::timeout(20)->get($url);
        if (! $res->successful()) {
            return null;
        }

        // Strip tags thô (HTML → text). Đủ cho keyword retrieval S6.
        return trim(html_entity_decode(strip_tags($res->body())));
    }

    /**
     * Tải Google Sheet công khai: gviz JSON trước (chính), CSV export sau (fallback).
     * Cả hai ép UTF-8. Sheet chưa công khai ⇒ Google trả HTML 200 ⇒ ném lỗi rõ ràng.
     */
    private function fetchGoogleSheet(string $url, DocumentTextExtractor $extractor): ?string
    {
        $gviz = (string) DocumentTextExtractor::googleSheetGvizUrl($url);
        $res = Http::timeout(20)->get($gviz);
        if ($res->successful()) {
            $body = $this->toUtf8($res->body(), (string) $res->header('Content-Type'));
            $this->assertSheetIsPublic($body, (string) $res->header('Content-Type'));
            $text = $extractor->fromGvizJson($body);
            if ($text !== null) {
                return $text;
            }
            // gviz trả 200 nhưng không parse được (format lạ) ⇒ thử CSV fallback bên dưới.
        }

        // Fallback CSV export.
        $csvUrl = (string) DocumentTextExtractor::googleSheetCsvUrl($url);
        $res = Http::timeout(20)->get($csvUrl);
        if (! $res->successful()) {
            throw new \RuntimeException('Không tải được Google Sheet (HTTP '.$res->status().').');
        }
        $body = $this->toUtf8($res->body(), (string) $res->header('Content-Type'));
        $this->assertSheetIsPublic($body, (string) $res->header('Content-Type'));

        return $extractor->extract($body, 'csv');
    }

    /** Sheet chưa chia sẻ công khai ⇒ Google trả TRANG HTML (200) ⇒ chặn để không nuốt HTML rác. */
    private function assertSheetIsPublic(string $body, string $contentType): void
    {
        if (str_contains(strtolower($contentType), 'text/html') || str_starts_with(ltrim($body), '<')) {
            throw new \RuntimeException('Google Sheet chưa chia sẻ công khai. Hãy đặt quyền "Bất kỳ ai có liên kết → Người xem" rồi thử lại.');
        }
    }

    /**
     * Ép body về UTF-8 (tránh lỗi tiếng Việt). Google trả UTF-8 cho gviz/CSV; nhưng nếu
     * charset header khác (vd windows-1258) hoặc body không phải UTF-8 hợp lệ ⇒ convert.
     */
    private function toUtf8(string $body, string $contentType): string
    {
        if (preg_match('/charset=([\w-]+)/i', $contentType, $m) && strtoupper($m[1]) !== 'UTF-8') {
            $converted = @mb_convert_encoding($body, 'UTF-8', $m[1]);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }
        if (! mb_check_encoding($body, 'UTF-8')) {
            $converted = @mb_convert_encoding($body, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return $body;
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

    /**
     * Nguồn có cấu trúc BẢNG không (mỗi hàng là 1 bản ghi)? → Google Sheet URL, hoặc
     * upload csv/tsv/xlsx. `DocumentTextExtractor` đã chuẩn hoá: mỗi hàng = 1 dòng (ô nối ' | ').
     */
    private function isTabular(AiKnowledgeDocument $doc): bool
    {
        if ($doc->source === AiKnowledgeDocument::SOURCE_URL
            && DocumentTextExtractor::googleSheetId((string) $doc->url) !== null) {
            return true;
        }
        if ($doc->source === AiKnowledgeDocument::SOURCE_UPLOAD) {
            $ext = strtolower(pathinfo((string) $doc->storage_path, PATHINFO_EXTENSION));

            return in_array($ext, ['csv', 'tsv', 'xlsx'], true);
        }

        return false;
    }

    /**
     * Mỗi DÒNG (hàng của bảng) = 1 chunk. Giữ nguyên ranh giới hàng, KHÔNG gộp \n thành
     * space (như `chunk()`). Bỏ dòng rỗng. Hàng dài quá `$maxSize` (mô tả cực dài) → cắt
     * mềm để không vượt giới hạn, nhưng vẫn theo từng hàng riêng.
     *
     * @return list<string>
     */
    private function chunkByRow(string $text, int $maxSize = 2000): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $row) {
            $row = trim((string) $row);
            if ($row === '') {
                continue;
            }
            if (mb_strlen($row) <= $maxSize) {
                $out[] = $row;

                continue;
            }
            foreach (mb_str_split($row, $maxSize) as $part) {
                if (trim($part) !== '') {
                    $out[] = $part;
                }
            }
        }

        return $out;
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
