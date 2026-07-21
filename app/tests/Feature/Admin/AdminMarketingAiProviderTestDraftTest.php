<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Marketing\Models\MarketingAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminMarketingAiProviderTestDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_exposes_plaintext_api_key_for_super_admin(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        MarketingAiProvider::query()->create([
            'code' => 'forecast-openai',
            'display_name' => 'Forecast',
            'adapter' => 'openai_compatible',
            'api_key' => 'sk-plain-test',
            'base_url' => 'https://api.openai.com',
            'default_model' => 'gpt-4o-mini',
            'is_active' => true,
        ]);

        $resp = $this->getJson('/api/v1/admin/marketing-ai-providers')->assertOk();

        $resp->assertJsonPath('data.0.api_key', 'sk-plain-test');
    }

    public function test_draft_test_reports_ok_on_successful_probe(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(
                ['choices' => [['message' => ['content' => 'pong']]]],
                200,
            ),
        ]);

        $resp = $this->postJson('/api/v1/admin/marketing-ai-providers/test-draft', [
            'adapter' => 'openai_compatible',
            'base_url' => 'https://api.openai.com',
            'api_key' => 'sk-test',
            'default_model' => 'gpt-4o-mini',
        ])->assertOk();

        $resp->assertJsonPath('data.ok', true);
    }

    public function test_store_rejects_unsafe_base_url(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        $this->postJson('/api/v1/admin/marketing-ai-providers', [
            'code' => 'forecast-ssrf',
            'adapter' => 'openai_compatible',
            'api_key' => 'sk-test',
            'base_url' => 'http://169.254.169.254',
            'default_model' => 'gpt-4o-mini',
        ])->assertStatus(422);
    }

    public function test_draft_test_rejects_unsafe_base_url(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        $this->postJson('/api/v1/admin/marketing-ai-providers/test-draft', [
            'adapter' => 'openai_compatible',
            'base_url' => 'http://169.254.169.254',
            'api_key' => 'sk-test',
            'default_model' => 'gpt-4o-mini',
        ])->assertStatus(422);
    }
}
