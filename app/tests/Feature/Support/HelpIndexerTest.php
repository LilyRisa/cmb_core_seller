<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Support\Models\HelpChunk;
use CMBcoreSeller\Modules\Support\Services\HelpIndexer;
use CMBcoreSeller\Modules\Support\Services\QdrantClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Index docs_user/rag_chunks.jsonl → help_chunks + Qdrant. */
class HelpIndexerTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('framework/testing/help-'.uniqid());
        @mkdir($this->dir, 0777, true);
        file_put_contents($this->dir.'/rag_chunks.jsonl', implode("\n", [
            json_encode(['title' => 'Kết nối gian hàng', 'module' => 'Channels', 'screen' => '/channels', 'question' => 'Kết nối sàn?', 'answer' => 'Vào Gian hàng bấm Kết nối.', 'keywords' => ['kết nối']]),
            json_encode(['title' => 'In tem', 'module' => 'Fulfillment', 'screen' => '/orders', 'question' => 'In tem?', 'answer' => 'Bấm In phiếu giao hàng.', 'keywords' => ['in tem']]),
        ]));
        config(['support.docs_path' => $this->dir]);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/rag_chunks.jsonl');
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function test_index_without_provider_stores_chunks_for_keyword_only(): void
    {
        config(['support.assistant.provider_code' => '', 'support.qdrant.url' => '']);

        $stats = app(HelpIndexer::class)->index(false);

        $this->assertSame(2, $stats['total']);
        $this->assertSame(0, $stats['embedded']);
        $this->assertSame(2, HelpChunk::query()->count());
        $this->assertNull(HelpChunk::query()->first()->embedding_model);
    }

    public function test_fresh_index_recreates_collection_and_upserts_vectors(): void
    {
        AiProvider::query()->create([
            'code' => 'help-ai', 'adapter' => 'openai_compatible', 'display_name' => 'Help AI',
            'api_key' => 'sk-test', 'base_url' => 'https://api.test', 'default_model' => 'gpt-test',
            'is_active' => true, 'sort_order' => 0,
        ]);
        config([
            'support.assistant.provider_code' => 'help-ai',
            'support.qdrant.url' => 'http://qdrant:6333',
        ]);
        $this->app->forgetInstance(QdrantClient::class);

        Http::fake([
            '*/v1/embeddings' => Http::response(['data' => [['embedding' => array_fill(0, 8, 0.1)]], 'usage' => ['total_tokens' => 2]]),
            '*/collections/*/points*' => Http::response(['result' => ['status' => 'completed']]),
            '*/collections/*' => Http::response(['result' => true]),
        ]);

        $stats = app(HelpIndexer::class)->index(true);

        $this->assertSame(2, $stats['total']);
        $this->assertSame(2, $stats['embedded']);
        $this->assertTrue($stats['qdrant']);
        $this->assertSame('help-ai', $stats['provider']);

        // --fresh ⇒ DROP rồi tạo lại collection, rồi upsert points.
        Http::assertSent(fn ($req) => $req->method() === 'DELETE' && str_contains($req->url(), '/collections/'));
        Http::assertSent(fn ($req) => $req->method() === 'PUT' && str_contains($req->url(), '/collections/') && ! str_contains($req->url(), '/points'));
        Http::assertSent(fn ($req) => str_contains($req->url(), '/points'));
    }

    /**
     * Provider chat KHÔNG có embedding (vd OpenRouter) nhưng đặt provider embedding RIÊNG
     * ⇒ index vẫn tạo vector qua provider embedding. Đây là fix cho lỗi "0/N chunk có vector".
     */
    public function test_index_uses_separate_embedding_provider_when_chat_provider_has_none(): void
    {
        // Provider chat: openrouter — giả lập KHÔNG có /v1/embeddings (trả 404).
        AiProvider::query()->create([
            'code' => 'openrouter', 'adapter' => 'openai_compatible', 'display_name' => 'OpenRouter',
            'api_key' => 'sk-or', 'base_url' => 'https://openrouter.ai/api', 'default_model' => 'google/gemini-2.0-flash-lite-001',
            'is_active' => true, 'sort_order' => 0,
        ]);
        // Provider embedding RIÊNG: openai — có /v1/embeddings.
        AiProvider::query()->create([
            'code' => 'openai-emb', 'adapter' => 'openai_compatible', 'display_name' => 'OpenAI Embed',
            'api_key' => 'sk-oa', 'base_url' => 'https://api.openai.com', 'default_model' => 'gpt-4o-mini',
            'is_active' => true, 'sort_order' => 1,
        ]);
        config([
            'support.assistant.provider_code' => 'openrouter',          // chat
            'support.assistant.embedding_provider_code' => 'openai-emb', // embedding riêng
            'support.qdrant.url' => 'http://qdrant:6333',
        ]);
        $this->app->forgetInstance(QdrantClient::class);

        Http::fake([
            // Chỉ openai mới trả vector; openrouter embeddings sẽ 404 (nhưng index KHÔNG gọi nó).
            'openai.com/v1/embeddings' => Http::response(['data' => [['embedding' => array_fill(0, 8, 0.2)]], 'usage' => ['total_tokens' => 2]]),
            'openrouter.ai/*' => Http::response(['error' => 'no embeddings'], 404),
            '*/collections/*/points*' => Http::response(['result' => ['status' => 'completed']]),
            '*/collections/*' => Http::response(['result' => true]),
        ]);

        $stats = app(HelpIndexer::class)->index(true);

        $this->assertSame(2, $stats['embedded']);          // tạo được vector
        $this->assertSame('openai-emb', $stats['provider']); // qua provider embedding riêng
        // Embedding gọi đúng endpoint openai, KHÔNG gọi openrouter cho embeddings.
        Http::assertSent(fn ($req) => str_contains($req->url(), 'openai.com/v1/embeddings'));
        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'openrouter.ai') && str_contains($req->url(), '/embeddings'));
    }
}
