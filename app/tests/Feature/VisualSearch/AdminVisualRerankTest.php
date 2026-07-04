<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVisualRerankTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai.vision.enabled', true);
        config()->set('ai.vision.models', ['gpt-5', 'gemini']);
    }

    private function seedProviders(): void
    {
        AiProvider::query()->create(['code' => 'chat_min', 'adapter' => 'openai_compatible', 'is_active' => true, 'base_url' => 'https://a.example.com', 'default_model' => 'mn/Minimax-M3']);
        AiProvider::query()->create(['code' => 'rr_vis', 'adapter' => 'openai_compatible', 'is_active' => true, 'base_url' => 'https://b.example.com', 'default_model' => 'ts/gemini-3.5-flash']);
    }

    public function test_index_lists_providers_with_vision_flag(): void
    {
        $this->seedProviders();
        $admin = AdminUser::factory()->create();

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/ai-visual-rerank')
            ->assertOk()->json('data');

        $this->assertNull($res['selected_provider_code']);
        $byCode = collect($res['providers'])->keyBy('code');
        $this->assertFalse($byCode['chat_min']['vision']);
        $this->assertTrue($byCode['rr_vis']['vision']);
    }

    public function test_put_saves_active_provider_and_rejects_unknown(): void
    {
        $this->seedProviders();
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-visual-rerank', ['provider_code' => 'rr_vis'])
            ->assertOk();
        $this->assertSame('rr_vis', system_setting('visual_search.rerank.provider_code'));

        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-visual-rerank', ['provider_code' => 'nope'])
            ->assertStatus(422);
    }

    public function test_put_empty_clears_setting(): void
    {
        $this->seedProviders();
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-visual-rerank', ['provider_code' => 'rr_vis'])->assertOk();
        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-visual-rerank', ['provider_code' => ''])->assertOk();

        $this->assertSame('', (string) system_setting('visual_search.rerank.provider_code', ''));
    }

    public function test_requires_admin_guard(): void
    {
        $this->getJson('/api/v1/admin/ai-visual-rerank')->assertStatus(401);
    }
}
