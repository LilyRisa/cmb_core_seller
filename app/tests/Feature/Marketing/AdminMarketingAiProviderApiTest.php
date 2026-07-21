<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Marketing\Models\MarketingAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminMarketingAiProviderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');
    }

    public function test_crud_and_exclusive_active_and_token_encrypted_at_rest(): void
    {
        // create
        $this->postJson('/api/v1/admin/marketing-ai-providers', [
            'code' => 'forecast-openai', 'display_name' => 'Forecast GPT', 'adapter' => 'openai_compatible',
            'api_key' => 'SECRET', 'base_url' => 'https://api.openai.com/v1', 'default_model' => 'gpt-4o-mini', 'is_active' => true,
        ])->assertOk();

        $raw = DB::table('marketing_ai_providers')->where('code', 'forecast-openai')->value('api_key');
        $this->assertNotSame('SECRET', $raw); // encrypted at rest

        // list — page is super-admin-only (guard admin_web) ⇒ api_key returned plaintext
        // for the SecretInput form (spec 2026-07-21 §5.3), still encrypted at rest above.
        $res = $this->getJson('/api/v1/admin/marketing-ai-providers')->assertOk();
        $res->assertJsonPath('data.0.api_key', 'SECRET');

        // a second active provider deactivates the first (single active)
        $this->postJson('/api/v1/admin/marketing-ai-providers', [
            'code' => 'forecast-claude', 'adapter' => 'anthropic', 'api_key' => 'K2', 'is_active' => true,
        ])->assertOk();
        $this->assertFalse((bool) MarketingAiProvider::find('forecast-openai')->is_active);
        $this->assertTrue((bool) MarketingAiProvider::find('forecast-claude')->is_active);

        // delete
        $this->deleteJson('/api/v1/admin/marketing-ai-providers/forecast-openai')->assertOk();
        $this->assertDatabaseMissing('marketing_ai_providers', ['code' => 'forecast-openai']);
    }
}
