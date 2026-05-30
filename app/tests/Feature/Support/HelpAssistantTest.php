<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Support\Models\HelpChunk;
use CMBcoreSeller\Modules\Support\Services\QdrantClient;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Tab "Hỏi AI" — RAG (vector) + fallback keyword, không bao giờ 500. */
class HelpAssistantTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'HelpShop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);

        HelpChunk::query()->create([
            'source' => 'rag_chunks', 'ref_key' => 'rag_chunks:test1',
            'title' => 'Kết nối gian hàng sàn', 'module' => 'Channels', 'screen' => '/channels',
            'question' => 'Kết nối gian hàng TikTok như thế nào?',
            'answer' => 'Vào Gian hàng, bấm Kết nối TikTok, đăng nhập shop và cấp quyền.',
            'keywords' => ['kết nối', 'gian hàng', 'tiktok'],
            'chunk_text' => 'Kết nối gian hàng sàn. Vào Gian hàng bấm Kết nối TikTok đăng nhập shop cấp quyền.',
        ]);
    }

    private function actor(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => 'owner']);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_keyword_fallback_when_no_ai_provider_configured(): void
    {
        config(['support.assistant.provider_code' => '', 'support.qdrant.url' => '']);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/support/assistant/ask', ['question' => 'Kết nối gian hàng TikTok ở đâu?'])
            ->assertOk()
            ->assertJsonPath('data.mode', 'keyword_no_llm')
            ->assertJsonPath('data.sources.0.title', 'Kết nối gian hàng sàn')
            ->assertJsonPath('data.answer', fn ($a) => is_string($a) && str_contains($a, 'Kết nối TikTok'));
    }

    public function test_rag_happy_path_with_vector_and_llm(): void
    {
        AiProvider::query()->create([
            'code' => 'help-ai', 'adapter' => 'openai_compatible', 'display_name' => 'Help AI',
            'api_key' => 'sk-test', 'base_url' => 'https://api.test', 'default_model' => 'gpt-test',
            'is_active' => true, 'sort_order' => 0,
        ]);
        config([
            'support.assistant.provider_code' => 'help-ai',
            'support.assistant.embedding_model' => 'text-embedding-3-small',
            'support.qdrant.url' => 'http://qdrant:6333',
        ]);
        $this->app->forgetInstance(QdrantClient::class); // re-đọc config qdrant.url

        Http::fake([
            '*/v1/embeddings' => Http::response(['data' => [['embedding' => array_fill(0, 8, 0.12)]], 'usage' => ['total_tokens' => 4]]),
            '*/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Bạn vào trang Gian hàng và bấm Kết nối TikTok.'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 12], 'model' => 'gpt-test',
            ]),
            '*qdrant*' => Http::response(['result' => [['id' => HelpChunk::query()->first()->id, 'score' => 0.92, 'payload' => []]]]),
        ]);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/support/assistant/ask', ['question' => 'Kết nối TikTok thế nào?'])
            ->assertOk()
            ->assertJsonPath('data.mode', 'rag')
            ->assertJsonPath('data.answer', 'Bạn vào trang Gian hàng và bấm Kết nối TikTok.')
            ->assertJsonPath('data.sources.0.module', 'Channels');

        Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/embeddings'));
        Http::assertSent(fn ($req) => str_contains($req->url(), 'qdrant'));
        Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/chat/completions'));
    }

    public function test_no_docs_indexed_returns_friendly_message_not_500(): void
    {
        HelpChunk::query()->delete();
        config(['support.assistant.provider_code' => '', 'support.qdrant.url' => '']);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/support/assistant/ask', ['question' => 'câu hỏi không có tài liệu'])
            ->assertOk()
            ->assertJsonPath('data.mode', 'no_docs')
            ->assertJsonPath('data.answer', fn ($a) => is_string($a) && str_contains($a, 'CSKH'));
    }

    public function test_empty_question_returns_422(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/support/assistant/ask', ['question' => ''])
            ->assertStatus(422);
    }
}
