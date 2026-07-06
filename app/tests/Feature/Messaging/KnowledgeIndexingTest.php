<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Jobs\IndexKnowledgeDoc;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Services\DocumentTextExtractor;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer;
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
        (new IndexKnowledgeDoc($doc->id))->handle(app(MediaStorage::class), app(DocumentTextExtractor::class), app(KnowledgeVectorIndexer::class));
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
        // Mỗi HÀNG = 1 chunk, BỎ dòng tiêu đề khỏi RAG ⇒ chỉ còn 1 dòng dữ liệu.
        $this->assertSame(1, $doc->chunk_count);
        $chunks = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->where('document_id', $doc->id)->orderBy('chunk_index')->pluck('chunk_text')->all();
        $this->assertStringNotContainsString('Câu hỏi | Trả lời', implode("\n", $chunks)); // không còn tiêu đề
        // Hàng sản phẩm là MỘT chunk riêng, gồm cả mô tả nhiều dòng (newline-in-cell → space).
        $this->assertStringContainsString('Giá ship?', $chunks[0]);
        $this->assertStringContainsString('30k toàn quốc', $chunks[0]);
        $this->assertStringContainsString('miễn phí đơn >500k', $chunks[0]);
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
        // Mỗi hàng 1 chunk, BỎ tiêu đề ⇒ chỉ 1 dòng dữ liệu (chunk_index 0).
        $this->assertSame(1, $doc->chunk_count);
        $second = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->where('document_id', $doc->id)->where('chunk_index', 0)->value('chunk_text');
        $this->assertStringContainsString('30k toàn quốc', (string) $second);
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

    public function test_each_sheet_row_is_its_own_chunk(): void
    {
        // 3 sản phẩm + header = mỗi hàng 1 chunk RIÊNG (không trộn sản phẩm vào chung 1 chunk).
        $gviz = "/*O_o*/\n".'google.visualization.Query.setResponse({"status":"ok","table":{"cols":[],"rows":['
            .'{"c":[{"v":"Sản phẩm"},{"v":"Giá"},{"v":"Mô tả"}]},'
            .'{"c":[{"v":"Bộ chia AV"},{"v":"220k"},{"v":"Chia tín hiệu\nKhông cần điện"}]},'
            .'{"c":[{"v":"ZK-MT21"},{"v":"360k"},{"v":"Chip TPA3116D2\nCông suất 200W"}]},'
            .'{"c":[{"v":"Mạch karaoke D800"},{"v":"800k"},{"v":"2 cổng míc\nCông suất 200W"}]}'
            .']}});';
        Http::fake(['docs.google.com/*/gviz/*' => Http::response($gviz, 200, ['Content-Type' => 'application/javascript; charset=UTF-8'])]);

        $doc = $this->urlDoc('https://docs.google.com/spreadsheets/d/MULTI/edit');
        $this->runJob($doc);

        $doc->refresh();
        $this->assertSame(3, $doc->chunk_count); // 3 sản phẩm (BỎ dòng tiêu đề)
        $chunks = AiKnowledgeChunk::withoutGlobalScope(TenantScope::class)
            ->where('document_id', $doc->id)->orderBy('chunk_index')->pluck('chunk_text')->all();

        // Mỗi sản phẩm gọn trong 1 chunk; KHÔNG dính sản phẩm khác.
        $this->assertStringContainsString('Bộ chia AV', $chunks[0]);
        $this->assertStringNotContainsString('ZK-MT21', $chunks[0]);
        $this->assertStringContainsString('ZK-MT21', $chunks[1]);
        $this->assertStringNotContainsString('Mạch karaoke', $chunks[1]);
        $this->assertStringContainsString('Mạch karaoke D800', $chunks[2]);
    }

    public function test_index_item_chunks_upserts_with_item_payload(): void
    {
        $captured = [];
        $store = \Mockery::mock(\CMBcoreSeller\Integrations\Vector\Contracts\VectorStore::class);
        $store->shouldReceive('enabled')->andReturn(true);
        $store->shouldReceive('ensureCollection')->andReturn(true);
        $store->shouldReceive('upsert')->andReturnUsing(function ($col, $points) use (&$captured) { $captured = $points; return true; });
        $this->app->instance(\CMBcoreSeller\Integrations\Vector\Contracts\VectorStore::class, $store);

        $indexer = \Mockery::mock(\CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer::class.'[embed]', [
            app(\CMBcoreSeller\Integrations\Ai\AiAssistantRegistry::class), $store,
        ]);
        $indexer->shouldAllowMockingProtectedMethods();
        $indexer->shouldReceive('embed')->andReturn([0.1, 0.2, 0.3]);

        $chunk = \CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk::create([
            'tenant_id' => 1, 'visual_item_id' => 55, 'chunk_index' => 0,
            'chunk_text' => 'Áo thun cotton', 'embedding' => null, 'token_count' => 3,
        ]);

        $n = $indexer->indexItemChunks(55, 1, [$chunk]);

        $this->assertSame(1, $n);
        $this->assertSame(['tenant_id' => 1, 'item_id' => 55], $captured[0]['payload']);
        $this->assertNotNull($chunk->fresh()->embedding);
    }
}
