<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeRetriever;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeVectorIndexer;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC-0024 — RAG "Kiến thức" (visual item): ưu tiên vector (Qdrant), fallback keyword.
 * Hệ tài liệu text thuần cũ (AiKnowledgeDocument) đã gỡ — nguồn tri thức duy nhất là visual item.
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
            'messaging.ai.embedding.base_url' => '',
            'messaging.ai.embedding.api_key' => '',
            'messaging.ai.embedding.model' => 'text-embedding-3-small',
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

    private function item(string $name = 'SP', string $provider = 'facebook_page'): VisualTrainingItem
    {
        return VisualTrainingItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => $name, 'kb_status' => 'ready',
            'provider' => $provider, 'applies_all_pages' => true, 'status' => 'active',
        ]);
    }

    private function chunk(VisualTrainingItem $item, int $i, string $text): AiKnowledgeChunk
    {
        return AiKnowledgeChunk::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->getKey(), 'visual_item_id' => $item->id,
            'chunk_index' => $i, 'chunk_text' => $text, 'embedding' => null, 'token_count' => 5,
        ]);
    }

    public function test_index_item_chunks_embeds_and_upserts_to_qdrant(): void
    {
        $this->useOpenAiProvider();
        $item = $this->item();
        $c1 = $this->chunk($item, 0, 'Sản phẩm A giá 100k');

        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['data' => [['embedding' => [0.1, 0.2, 0.3]]], 'usage' => ['total_tokens' => 3]], 200),
            'qdrant.test:6333/collections/*' => Http::response(['result' => true], 200),
        ]);

        $embedded = app(KnowledgeVectorIndexer::class)->indexItemChunks($item->id, $this->tenant->getKey(), [$c1]);

        $this->assertSame(1, $embedded);
        $this->assertSame([0.1, 0.2, 0.3], $c1->fresh()->embedding);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/points') && $req->method() === 'PUT');
        // Point id PHẢI là integer — Qdrant từ chối id chuỗi (400) ⇒ upsert fail-soft ⇒ collection rỗng.
        Http::assertSent(function ($req) use ($c1) {
            if (! str_contains($req->url(), '/points')) {
                return false;
            }
            $id = $req->data()['points'][0]['id'] ?? null;

            return $id === $c1->id && is_int($id);
        });
    }

    public function test_retrieve_uses_vector_when_available(): void
    {
        $this->useOpenAiProvider();
        $item = $this->item();
        $hit = $this->chunk($item, 0, 'Chính sách bảo hành 12 tháng');
        $this->chunk($item, 1, 'Phí ship nội thành 20k');

        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['data' => [['embedding' => [0.1, 0.2, 0.3]]]], 200),
            'qdrant.test:6333/collections/*/points/search' => Http::response(['result' => [['id' => $hit->id, 'score' => 0.93, 'payload' => []]]], 200),
            'qdrant.test:6333/collections/*' => Http::response(['result' => true], 200),
        ]);

        $kb = app(KnowledgeRetriever::class)->retrieve($this->tenant->getKey(), 'điện thoại có bảo hành không', 4, null, 'facebook_page');

        $this->assertCount(1, $kb->chunks);
        $this->assertSame('Chính sách bảo hành 12 tháng', $kb->chunks[0]['chunk_text']);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/points/search'));
    }

    public function test_embeds_via_dedicated_endpoint_without_chat_provider(): void
    {
        // Endpoint embedding chuyên dụng (tái dùng Hỏi AI) ⇒ KHÔNG cần provider chat, không đụng 403.
        config([
            'messaging.ai.embedding.base_url' => 'http://emb.test',
            'messaging.ai.embedding.api_key' => 'ek',
            'messaging.ai.embedding.model' => 'text-embedding-3-small',
        ]);
        $item = $this->item();
        $c1 = $this->chunk($item, 0, 'Sản phẩm A giá 100k');

        Http::fake([
            'emb.test/v1/embeddings' => Http::response(['data' => [['embedding' => [0.5, 0.6]]]], 200),
            'qdrant.test:6333/collections/*' => Http::response(['result' => true], 200),
        ]);

        $embedded = app(KnowledgeVectorIndexer::class)->indexItemChunks($item->id, $this->tenant->getKey(), [$c1]);

        $this->assertSame(1, $embedded);
        $this->assertSame([0.5, 0.6], $c1->fresh()->embedding);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'emb.test/v1/embeddings'));
    }

    public function test_falls_back_to_keyword_without_embedding_provider(): void
    {
        // Provider manual KHÔNG hỗ trợ embed ⇒ vector null ⇒ keyword.
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);
        MessagingSetting::withoutGlobalScopes()->updateOrCreate(['tenant_id' => $this->tenant->getKey()], [
            'ai_provider_code' => 'manual', 'ai_enabled' => true,
        ]);
        $item = $this->item();
        $this->chunk($item, 0, 'Chính sách bảo hành 12 tháng');
        $this->chunk($item, 1, 'Phí ship nội thành 20k');

        Http::fake();   // không endpoint nào được gọi (embed null trước khi tới Qdrant)

        $kb = app(KnowledgeRetriever::class)->retrieve($this->tenant->getKey(), 'cho hỏi chính sách bảo hành', 4, null, 'facebook_page');

        $this->assertNotEmpty($kb->chunks);
        $this->assertSame('Chính sách bảo hành 12 tháng', $kb->chunks[0]['chunk_text']);
        Http::assertNothingSent();
    }

    public function test_code_boost_disambiguates_similar_products(): void
    {
        // Kịch bản prod: nhiều SP cùng dòng mô tả ~giống nhau, chỉ khác mã model + giá.
        // Khách hỏi đúng "D900" ⇒ chỉ trả D900, KHÔNG lẫn D800/D100 (boost mã model + relative floor).
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);
        MessagingSetting::withoutGlobalScopes()->updateOrCreate(['tenant_id' => $this->tenant->getKey()], [
            'ai_provider_code' => 'manual', 'ai_enabled' => true,
        ]);

        $mk = function (string $name, string $text): void {
            $item = $this->item($name);
            $this->chunk($item, 0, $text);
        };
        $mk('Mạch loa kéo karaoke D800', 'Mạch loa kéo karaoke D800 công suất 200W kéo căng bass giá 800k');
        $mk('Mạch loa kéo karaoke D900', 'Mạch loa kéo karaoke D900 công suất 200W kéo căng bass giá 900k');
        $mk('Mạch loa kéo karaoke D100', 'Mạch loa kéo karaoke D100 công suất 150W kéo căng bass giá 500k');

        Http::fake();   // manual provider ⇒ vector null ⇒ đi keyword path

        $kb = app(KnowledgeRetriever::class)->retrieve($this->tenant->getKey(), 'mạch loa kéo D900 giá bao nhiêu', 4, null, 'facebook_page');

        $this->assertCount(1, $kb->chunks);
        $this->assertSame('Mạch loa kéo karaoke D900', $kb->chunks[0]['title']);
        Http::assertNothingSent();
    }
}
