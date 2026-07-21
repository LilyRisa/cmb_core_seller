<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminAiProviderTestDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_test_reports_ok_on_successful_probe(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        Http::fake([
            'https://api.deepseek.com/v1/chat/completions' => Http::response(
                ['choices' => [['message' => ['content' => 'pong']]]],
                200,
            ),
        ]);

        $resp = $this->postJson('/api/v1/admin/ai-providers/test-draft', [
            'adapter' => 'openai_compatible',
            'base_url' => 'https://api.deepseek.com',
            'api_key' => 'sk-test',
            'default_model' => 'deepseek-chat',
        ])->assertOk();

        $resp->assertJsonPath('data.ok', true);
    }

    public function test_draft_test_rejects_unprobeable_adapter(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        $this->postJson('/api/v1/admin/ai-providers/test-draft', [
            'adapter' => 'custom_http',
            'api_key' => 'sk-test',
        ])->assertStatus(422);
    }
}
