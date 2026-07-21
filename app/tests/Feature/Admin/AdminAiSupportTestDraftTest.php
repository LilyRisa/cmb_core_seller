<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminAiSupportTestDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_probe_surfaces_provider_error_message(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response(
                ['error' => ['message' => 'invalid api key']],
                401,
            ),
        ]);

        $resp = $this->postJson('/api/v1/admin/ai-support/test-draft', [
            'kind' => 'chat',
            'base_url' => 'https://openrouter.ai/api',
            'api_key' => 'sk-bad',
            'model' => 'openai/gpt-4o-mini',
        ])->assertOk();

        $resp->assertJsonPath('data.ok', false);
        $this->assertStringContainsString('invalid api key', (string) $resp->json('data.message'));
    }

    public function test_embedding_probe_reports_ok_with_dimension(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response(
                ['data' => [['embedding' => [0.1, 0.2]]]],
                200,
            ),
        ]);

        $resp = $this->postJson('/api/v1/admin/ai-support/test-draft', [
            'kind' => 'embedding',
            'base_url' => 'https://api.openai.com',
            'api_key' => 'sk-test',
            'model' => 'text-embedding-3-small',
        ])->assertOk();

        $resp->assertJsonPath('data.ok', true);
    }

    /**
     * SafeProviderUrl chống SSRF — mirror của AdminAiProviderTestDraftTest (Messaging):
     * endpoint này gửi request thật ra base_url do admin nhập, nên phải chặn
     * 169.254.169.254 (cloud metadata endpoint, mục tiêu SSRF kinh điển) trước khi
     * chạm HTTP client.
     */
    public function test_draft_test_rejects_unsafe_base_url(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        Http::fake();

        $this->postJson('/api/v1/admin/ai-support/test-draft', [
            'kind' => 'chat',
            'base_url' => 'http://169.254.169.254',
            'api_key' => 'sk-test',
            'model' => 'x',
        ])->assertStatus(422);

        Http::assertNothingSent();
    }
}
