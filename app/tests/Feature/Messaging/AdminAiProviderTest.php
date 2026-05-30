<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test super-admin CRUD AI provider (SPEC-0024 S6, ADR-0018 revised → bảng riêng).
 *
 * Quan trọng: api_key KHÔNG lộ trong response; capabilities đọc từ connector
 * class; test-connection của connector chưa wire (Claude stub) trả ok:false
 * thay vì 500.
 */
class AdminAiProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAdmin(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');
    }

    public function test_admin_creates_provider_without_leaking_key(): void
    {
        $this->actingAdmin();

        $this->postJson('/api/v1/admin/ai-providers', [
            'code' => 'claude',
            'adapter' => 'anthropic',
            'display_name' => 'Claude Prod',
            'api_key' => 'sk-ant-secret-xxx',
            'default_model' => 'claude-opus-4-7',
            'is_active' => true,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.code', 'claude')
            ->assertJsonPath('data.has_api_key', true)
            ->assertJsonMissingPath('data.api_key');

        // Key encrypted at rest (raw column khác plaintext).
        $row = AiProvider::query()->find('claude');
        $this->assertNotSame('sk-ant-secret-xxx', $row->getRawOriginal('api_key'));
        $this->assertSame('sk-ant-secret-xxx', $row->api_key); // decrypt qua cast

        // capabilities từ connector class (Claude: reply.suggest true).
        $caps = $this->getJson('/api/v1/admin/ai-providers')->assertOk()->json('data.0.capabilities');
        $this->assertTrue($caps['reply.suggest'] ?? false);
    }

    public function test_store_rejects_unregistered_adapter(): void
    {
        $this->actingAdmin();
        $this->postJson('/api/v1/admin/ai-providers', ['code' => 'bogus', 'adapter' => 'bogus_adapter'])
            ->assertStatus(422);
    }

    public function test_store_allows_free_form_code_with_known_adapter(): void
    {
        $this->actingAdmin();
        $this->postJson('/api/v1/admin/ai-providers', [
            'code' => 'deepseek-prod', 'adapter' => 'openai_compatible',
            'base_url' => 'https://api.deepseek.com', 'default_model' => 'deepseek-chat',
            'api_key' => 'sk-ds-xxx', 'is_active' => true,
        ])->assertStatus(201)->assertJsonPath('data.adapter', 'openai_compatible');
    }

    public function test_test_endpoint_graceful_when_not_configured(): void
    {
        $this->actingAdmin();
        // Claude active nhưng CHƯA nhập api_key ⇒ ProviderNotConfigured → ok:false.
        AiProvider::query()->create(['code' => 'claude', 'adapter' => 'anthropic', 'is_active' => true]);

        $this->postJson('/api/v1/admin/ai-providers/claude/test')
            ->assertOk()
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.reason', 'not_configured');
    }

    public function test_test_endpoint_ok_for_claude_with_key(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'model' => 'claude-opus-4-7',
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'Em chào anh/chị ạ!']],
                'usage' => ['input_tokens' => 12, 'output_tokens' => 8],
            ], 200),
        ]);

        $this->actingAdmin();
        AiProvider::query()->create([
            'code' => 'claude', 'adapter' => 'anthropic', 'is_active' => true,
            'api_key' => 'sk-ant-test', 'default_model' => 'claude-opus-4-7',
        ]);

        $this->postJson('/api/v1/admin/ai-providers/claude/test')
            ->assertOk()
            ->assertJsonPath('data.ok', true);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/messages')
            && $req->hasHeader('x-api-key', 'sk-ant-test')
            && $req->hasHeader('anthropic-version', '2023-06-01'));
    }

    public function test_test_endpoint_ok_for_manual(): void
    {
        $this->actingAdmin();
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);

        $this->postJson('/api/v1/admin/ai-providers/manual/test')
            ->assertOk()
            ->assertJsonPath('data.ok', true);
    }

    /** Test phải kiểm CẢ embedding (provider có năng lực embedding) — không chỉ chat. */
    public function test_test_endpoint_checks_embedding_capability(): void
    {
        Http::fake([
            '*/v1/embeddings' => Http::response(['data' => [['embedding' => [0.1, 0.2, 0.3]]], 'usage' => ['total_tokens' => 3]], 200),
            '*/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'hi'], 'finish_reason' => 'stop']], 'usage' => []], 200),
        ]);
        $this->actingAdmin();
        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
            'base_url' => 'https://api.openai.com', 'default_model' => 'gpt-4o-mini', 'api_key' => 'sk-x',
        ]);

        $this->postJson('/api/v1/admin/ai-providers/openai/test')
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.results.chat.ok', true)
            ->assertJsonPath('data.results.embedding.ok', true)
            ->assertJsonPath('data.results.embedding.dimension', 3);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/embeddings'));
    }

    /** Provider chat OK nhưng embedding LỖI ⇒ ok=false + chỉ rõ embedding hỏng (để cấu hình Support). */
    public function test_test_endpoint_flags_broken_embedding(): void
    {
        Http::fake([
            '*/v1/embeddings' => Http::response(['error' => ['message' => 'model not found']], 404),
            '*/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'hi'], 'finish_reason' => 'stop']], 'usage' => []], 200),
        ]);
        $this->actingAdmin();
        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
            'base_url' => 'https://api.openai.com', 'default_model' => 'gpt-4o-mini', 'api_key' => 'sk-x',
        ]);

        $this->postJson('/api/v1/admin/ai-providers/openai/test')
            ->assertOk()
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.results.chat.ok', true)
            ->assertJsonPath('data.results.embedding.ok', false);
    }

    public function test_destroy_disables_provider(): void
    {
        $this->actingAdmin();
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);

        $this->deleteJson('/api/v1/admin/ai-providers/manual')->assertOk();

        $this->assertDatabaseHas('ai_providers', ['code' => 'manual', 'is_active' => false]);
    }

    public function test_requires_admin_guard(): void
    {
        // Không login admin ⇒ 401/403.
        $this->getJson('/api/v1/admin/ai-providers')->assertStatus(401);
    }

    public function test_multiple_openai_compatible_instances_coexist(): void
    {
        $this->actingAdmin();
        foreach ([
            ['deepseek-prod', 'https://api.deepseek.com', 'deepseek-chat'],
            ['qwen-cheap', 'https://dashscope-intl.aliyuncs.com/compatible-mode', 'qwen-plus'],
            ['openrouter-fb', 'https://openrouter.ai/api', 'openai/gpt-4o-mini'],
        ] as [$code, $url, $model]) {
            $this->postJson('/api/v1/admin/ai-providers', [
                'code' => $code, 'adapter' => 'openai_compatible',
                'base_url' => $url, 'default_model' => $model, 'api_key' => 'k', 'is_active' => true,
            ])->assertStatus(201);
        }

        $codes = collect($this->getJson('/api/v1/admin/ai-providers')->json('data'))->pluck('code');
        $this->assertContains('deepseek-prod', $codes);
        $this->assertContains('qwen-cheap', $codes);
        $this->assertContains('openrouter-fb', $codes);
    }

    public function test_store_rejects_non_https_base_url(): void
    {
        $this->actingAdmin();
        $this->postJson('/api/v1/admin/ai-providers', [
            'code' => 'evil', 'adapter' => 'openai_compatible',
            'base_url' => 'http://169.254.169.254', 'default_model' => 'x',
        ])->assertStatus(422);
    }

    public function test_hyphenated_code_is_editable_testable_deletable(): void
    {
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => 'hi'], 'finish_reason' => 'stop']],
            'data' => [['embedding' => [0.1, 0.2]]], // openai_compatible test cả embedding
            'usage' => [],
        ], 200)]);
        $this->actingAdmin();

        $this->postJson('/api/v1/admin/ai-providers', [
            'code' => 'deepseek-prod', 'adapter' => 'openai_compatible',
            'base_url' => 'https://api.deepseek.com', 'default_model' => 'deepseek-chat',
            'api_key' => 'k', 'is_active' => true,
        ])->assertStatus(201);

        // Route constraint phải cho phép dấu '-' (trước đây [a-z0-9_]+ ⇒ 404 mọi thao tác).
        $this->patchJson('/api/v1/admin/ai-providers/deepseek-prod', ['display_name' => 'DeepSeek Prod'])
            ->assertOk()->assertJsonPath('data.display_name', 'DeepSeek Prod');
        $this->postJson('/api/v1/admin/ai-providers/deepseek-prod/test')
            ->assertOk()->assertJsonPath('data.ok', true);
        $this->deleteJson('/api/v1/admin/ai-providers/deepseek-prod')->assertOk();
        $this->assertDatabaseHas('ai_providers', ['code' => 'deepseek-prod', 'is_active' => false]);
    }

    public function test_store_custom_http_requires_template_and_path(): void
    {
        $this->actingAdmin();

        // custom_http thiếu request_template + response_path ⇒ 422 (envelope chuẩn error.details).
        $res = $this->postJson('/api/v1/admin/ai-providers', [
            'code' => 'my-llm', 'adapter' => 'custom_http',
            'base_url' => 'https://llm.example.com/chat',
        ])->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $details = $res->json('error.details');
        $this->assertArrayHasKey('adapter_config.request_template', $details);
        $this->assertArrayHasKey('adapter_config.response_path', $details);
    }

    public function test_store_custom_http_creates_and_returns_config(): void
    {
        $this->actingAdmin();

        $this->postJson('/api/v1/admin/ai-providers', [
            'code' => 'my-llm', 'adapter' => 'custom_http',
            'display_name' => 'LLM nội địa', 'api_key' => 'secret-key',
            'base_url' => 'https://llm.example.com/chat',
            'default_model' => 'my-model', 'is_active' => true,
            'adapter_config' => [
                'method' => 'POST',
                'headers' => ['Authorization' => 'Bearer {{api_key}}'],
                'request_template' => '{"model":"{{model}}","messages":{{messages_json}}}',
                'response_path' => 'data.reply.text',
                'usage' => ['prompt_path' => 'usage.in', 'completion_path' => 'usage.out'],
            ],
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.adapter', 'custom_http')
            ->assertJsonPath('data.has_api_key', true)
            ->assertJsonMissingPath('data.api_key')
            ->assertJsonPath('data.adapter_config.response_path', 'data.reply.text');

        // capabilities đọc từ connector class: custom_http hỗ trợ intent.classify (cho auto-mode).
        $row = collect($this->getJson('/api/v1/admin/ai-providers')->json('data'))->firstWhere('code', 'my-llm');
        $this->assertTrue($row['capabilities']['intent.classify'] ?? false);
        $this->assertFalse($row['capabilities']['embedding'] ?? true);
    }

    public function test_test_endpoint_graceful_when_adapter_unregistered(): void
    {
        $this->actingAdmin();
        AiProvider::query()->create(['code' => 'weird', 'adapter' => 'bogus_adapter', 'is_active' => true]);

        // make() ném ProviderNotConfigured (adapter chưa register) — phải bắt trong try ⇒ 200 ok:false (KHÔNG 500).
        $this->postJson('/api/v1/admin/ai-providers/weird/test')
            ->assertOk()->assertJsonPath('data.ok', false);
    }
}
