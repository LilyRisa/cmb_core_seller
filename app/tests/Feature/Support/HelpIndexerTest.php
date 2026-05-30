<?php

namespace Tests\Feature\Support;

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

    public function test_index_without_embedding_config_stores_chunks_for_keyword_only(): void
    {
        config(['support.assistant.embedding.base_url' => '', 'support.qdrant.url' => '']);

        $stats = app(HelpIndexer::class)->index(false);

        $this->assertSame(2, $stats['total']);
        $this->assertSame(0, $stats['embedded']);
        $this->assertSame(2, HelpChunk::query()->count());
        $this->assertNull(HelpChunk::query()->first()->embedding_model);
    }

    public function test_fresh_index_recreates_collection_and_upserts_vectors(): void
    {
        // Credentials embedding RIÊNG của Support (tự chứa).
        config([
            'support.assistant.embedding.base_url' => 'https://emb.test',
            'support.assistant.embedding.api_key' => 'sk-emb',
            'support.assistant.embedding.model' => 'text-embedding-3-small',
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

        // --fresh ⇒ DROP rồi tạo lại collection, rồi upsert points.
        Http::assertSent(fn ($req) => $req->method() === 'DELETE' && str_contains($req->url(), '/collections/'));
        Http::assertSent(fn ($req) => $req->method() === 'PUT' && str_contains($req->url(), '/collections/') && ! str_contains($req->url(), '/points'));
        Http::assertSent(fn ($req) => str_contains($req->url(), '/points'));
    }

    /**
     * Chat (OpenRouter) cấu hình nhưng EMBEDDING để trống ⇒ 0 vector (chạy keyword).
     * Đây đúng case OpenRouter không có /v1/embeddings — chứng minh tách chat/embedding.
     */
    public function test_chat_configured_but_no_embedding_yields_zero_vectors(): void
    {
        config([
            'support.assistant.chat.base_url' => 'https://openrouter.ai/api',
            'support.assistant.chat.api_key' => 'sk-or',
            'support.assistant.chat.model' => 'google/gemini-2.0-flash-lite-001',
            'support.assistant.embedding.base_url' => '',  // chưa cấu hình embedding
            'support.qdrant.url' => 'http://qdrant:6333',
        ]);
        $this->app->forgetInstance(QdrantClient::class);

        $stats = app(HelpIndexer::class)->index(true);

        $this->assertSame(2, $stats['total']);
        $this->assertSame(0, $stats['embedded']);   // không có embedding ⇒ 0 vector
        $this->assertFalse($stats['qdrant']);
        $this->assertSame(2, HelpChunk::query()->count()); // vẫn lưu chunk cho keyword
    }

    /** Chat = OpenRouter, embedding = nguồn riêng ⇒ tạo được vector (case mong muốn). */
    public function test_separate_chat_and_embedding_endpoints_create_vectors(): void
    {
        config([
            'support.assistant.chat.base_url' => 'https://openrouter.ai/api',
            'support.assistant.chat.api_key' => 'sk-or',
            'support.assistant.chat.model' => 'google/gemini-2.0-flash-lite-001',
            'support.assistant.embedding.base_url' => 'https://api.openai.com',
            'support.assistant.embedding.api_key' => 'sk-oa',
            'support.assistant.embedding.model' => 'text-embedding-3-small',
            'support.qdrant.url' => 'http://qdrant:6333',
        ]);
        $this->app->forgetInstance(QdrantClient::class);

        Http::fake([
            'openai.com/v1/embeddings' => Http::response(['data' => [['embedding' => array_fill(0, 8, 0.2)]], 'usage' => ['total_tokens' => 2]]),
            'openrouter.ai/*' => Http::response(['error' => 'no embeddings'], 404),
            '*/collections/*/points*' => Http::response(['result' => ['status' => 'completed']]),
            '*/collections/*' => Http::response(['result' => true]),
        ]);

        $stats = app(HelpIndexer::class)->index(true);

        $this->assertSame(2, $stats['embedded']);
        // Embedding gọi đúng endpoint openai; KHÔNG gọi openrouter cho embeddings.
        Http::assertSent(fn ($req) => str_contains($req->url(), 'openai.com/v1/embeddings'));
        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'openrouter.ai') && str_contains($req->url(), '/embeddings'));
    }
}
