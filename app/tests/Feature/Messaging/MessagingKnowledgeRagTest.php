<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeRetriever;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC-0024 — RAG knowledge tin nhắn: ưu tiên vector (Qdrant), fallback keyword.
 */
class MessagingKnowledgeRagTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'RagShop']);
        config([
            'integrations.vector.qdrant.url' => 'http://qdrant.test:6333',
            'integrations.vector.qdrant.api_key' => '',
            'integrations.vector.qdrant.timeout' => 5,
            'messaging.ai.embedding_model' => 'text-embedding-3-small',
        ]);
    }

    private function useOpenAiProvider(): void
    {
        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'sk-oai', 'default_model' => 'gpt-4o-mini',
        ]);
        MessagingSetting::withoutGlobalScopes()->updateOrCreate(['tenant_id' => $this->tenant->getKey()], [
            'ai_provider_code' => 'openai', 'ai_enabled' => true,
        ]);
    }

    private function document(): AiKnowledgeDocument
    {
        return AiKnowledgeDocument::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->getKey(), 'title' => 'Bảng giá', 'source' => AiKnowledgeDocument::SOURCE_INLINE,
            'inline_text' => 'x', 'status' => AiKnowledgeDocument::STATUS_READY, 'applies_all_pages' => true,
            'chunk_count' => 0, 'embedding_version' => 0,
        ]);
    }

    private function chunk(AiKnowledgeDocument $doc, int $i, string $text): AiKnowledgeChunk
    {
        return AiKnowledgeChunk::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->getKey(), 'document_id' => $doc->id,
            'chunk_index' => $i, 'chunk_text' => $text, 'embedding' => null, 'token_count' => 5,
        ]);
    }

    public function test_index_chunks_embeds_and_upserts_to_qdrant(): void
    {
        $this->useOpenAiProvider();
        $doc = $this->document();
        $c1 = $this->chunk($doc, 0, 'Sản phẩm A giá 100k');

        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['data' => [['embedding' => [0.1, 0.2, 0.3]]], 'usage' => ['total_tokens' => 3]], 200),
            'qdrant.test:6333/collections/*' => Http::response(['result' => true], 200),
        ]);

        $embedded = app(KnowledgeVectorIndexer::class)->indexChunks($doc, [$c1]);

        $this->assertSame(1, $embedded);
        $this->assertSame([0.1, 0.2, 0.3], $c1->fresh()->embedding);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/points') && $req->method() === 'PUT');
    }

    public function test_retrieve_uses_vector_when_available(): void
    {
        $this->useOpenAiProvider();
        $doc = $this->document();
        $hit = $this->chunk($doc, 0, 'Chính sách bảo hành 12 tháng');
        $this->chunk($doc, 1, 'Phí ship nội thành 20k');

        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['data' => [['embedding' => [0.1, 0.2, 0.3]]]], 200),
            'qdrant.test:6333/collections/*/points/search' => Http::response(['result' => [['id' => (string) $hit->id, 'score' => 0.93, 'payload' => []]]], 200),
            'qdrant.test:6333/collections/*' => Http::response(['result' => true], 200),
        ]);

        $kb = app(KnowledgeRetriever::class)->retrieve($this->tenant->getKey(), 'điện thoại có bảo hành không', 4);

        $this->assertCount(1, $kb->chunks);
        $this->assertSame($hit->id, AiKnowledgeChunk::withoutGlobalScopes()->where('chunk_text', $kb->chunks[0]['chunk_text'])->value('id'));
        $this->assertSame('Chính sách bảo hành 12 tháng', $kb->chunks[0]['chunk_text']);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/points/search'));
    }

    public function test_falls_back_to_keyword_without_embedding_provider(): void
    {
        // Provider manual KHÔNG hỗ trợ embed ⇒ vector null ⇒ keyword.
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);
        MessagingSetting::withoutGlobalScopes()->updateOrCreate(['tenant_id' => $this->tenant->getKey()], [
            'ai_provider_code' => 'manual', 'ai_enabled' => true,
        ]);
        $doc = $this->document();
        $this->chunk($doc, 0, 'Chính sách bảo hành 12 tháng');
        $this->chunk($doc, 1, 'Phí ship nội thành 20k');

        Http::fake();   // không endpoint nào được gọi (embed null trước khi tới Qdrant)

        $kb = app(KnowledgeRetriever::class)->retrieve($this->tenant->getKey(), 'cho hỏi chính sách bảo hành', 4);

        $this->assertNotEmpty($kb->chunks);
        $this->assertSame('Chính sách bảo hành 12 tháng', $kb->chunks[0]['chunk_text']);
        Http::assertNothingSent();
    }
}
