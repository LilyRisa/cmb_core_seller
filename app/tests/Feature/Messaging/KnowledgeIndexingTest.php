<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeDoc;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Services\DocumentTextExtractor;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * AI training indexing — Google Sheet fetch phải nhận ĐÚNG CSV; sheet không công
 * khai (Google trả HTML) ⇒ fail rõ ràng thay vì index rác.
 */
class KnowledgeIndexingTest extends TestCase
{
    use RefreshDatabase;

    private function urlDoc(string $url): AiKnowledgeDocument
    {
        return AiKnowledgeDocument::create([
            'tenant_id' => 1, 'title' => 'Sheet', 'source' => AiKnowledgeDocument::SOURCE_URL,
            'url' => $url, 'status' => AiKnowledgeDocument::STATUS_PENDING,
        ]);
    }

    private function runJob(AiKnowledgeDocument $doc): void
    {
        (new IndexKnowledgeDoc($doc->id))->handle(app(MediaStorage::class), app(DocumentTextExtractor::class));
    }

    public function test_public_google_sheet_indexes_via_gviz_json(): void
    {
        // Ưu tiên gviz JSON (UTF-8 structured). Tiếng Việt + xuống dòng trong ô.
        $gviz = "/*O_o*/\n".'google.visualization.Query.setResponse({"status":"ok","table":{"cols":[],"rows":['
            .'{"c":[{"v":"Câu hỏi"},{"v":"Trả lời"}]},'
            .'{"c":[{"v":"Giá ship?"},{"v":"30k toàn quốc\nmiễn phí đơn >500k"}]}'
            .']}});';
        Http::fake(['docs.google.com/*/gviz/*' => Http::response($gviz, 200, ['Content-Type' => 'application/javascript; charset=UTF-8'])]);

        $doc = $this->urlDoc('https://docs.google.com/spreadsheets/d/ABC123/edit#gid=0');
        $this->runJob($doc);

        $doc->refresh();
        $this->assertSame(AiKnowledgeDocument::STATUS_READY, $doc->status);
        $this->assertGreaterThan(0, $doc->chunk_count);
        $text = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)->where('document_id', $doc->id)->value('chunk_text');
        $this->assertStringContainsString('30k toàn quốc', (string) $text);
        $this->assertStringContainsString('miễn phí đơn >500k', (string) $text); // newline-in-cell giữ nguyên 1 dòng
        // Gọi gviz, KHÔNG cần CSV export.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/gviz/tq?tqx=out:json'));
    }

    public function test_google_sheet_falls_back_to_csv_when_gviz_unparseable(): void
    {
        // gviz trả body lạ (không parse được) → fallback CSV export.
        Http::fake([
            'docs.google.com/*/gviz/*' => Http::response('garbage not gviz', 200, ['Content-Type' => 'text/plain']),
            'docs.google.com/*/export*' => Http::response("Câu hỏi,Trả lời\nGiá ship?,30k toàn quốc", 200, ['Content-Type' => 'text/csv; charset=UTF-8']),
        ]);

        $doc = $this->urlDoc('https://docs.google.com/spreadsheets/d/ABC123/edit#gid=0');
        $this->runJob($doc);

        $doc->refresh();
        $this->assertSame(AiKnowledgeDocument::STATUS_READY, $doc->status);
        $text = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)->where('document_id', $doc->id)->value('chunk_text');
        $this->assertStringContainsString('30k toàn quốc', (string) $text);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/export?format=csv'));
    }

    public function test_non_public_google_sheet_fails_with_clear_error(): void
    {
        // Sheet không công khai ⇒ Google trả TRANG HTML (200) — không phải CSV.
        Http::fake(['docs.google.com/*' => Http::response('<!DOCTYPE html><html><head><title>Sign in</title></head></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8'])]);

        $doc = $this->urlDoc('https://docs.google.com/spreadsheets/d/PRIV/edit');
        $this->runJob($doc);

        $doc->refresh();
        $this->assertSame(AiKnowledgeDocument::STATUS_FAILED, $doc->status);
        $this->assertStringContainsString('công khai', (string) $doc->error);
        $this->assertSame(0, AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)->where('document_id', $doc->id)->count());
    }
}
