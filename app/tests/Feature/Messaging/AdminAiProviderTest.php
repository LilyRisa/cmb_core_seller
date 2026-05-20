<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_store_rejects_unregistered_code(): void
    {
        $this->actingAdmin();

        $this->postJson('/api/v1/admin/ai-providers', ['code' => 'bogus_llm'])
            ->assertStatus(422);
    }

    public function test_test_endpoint_graceful_for_unimplemented_connector(): void
    {
        $this->actingAdmin();
        AiProvider::query()->create(['code' => 'claude', 'is_active' => true]);

        $this->postJson('/api/v1/admin/ai-providers/claude/test')
            ->assertOk()
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.reason', 'connector_not_implemented');
    }

    public function test_test_endpoint_ok_for_manual(): void
    {
        $this->actingAdmin();
        AiProvider::query()->create(['code' => 'manual', 'is_active' => true]);

        $this->postJson('/api/v1/admin/ai-providers/manual/test')
            ->assertOk()
            ->assertJsonPath('data.ok', true);
    }

    public function test_destroy_disables_provider(): void
    {
        $this->actingAdmin();
        AiProvider::query()->create(['code' => 'manual', 'is_active' => true]);

        $this->deleteJson('/api/v1/admin/ai-providers/manual')->assertOk();

        $this->assertDatabaseHas('ai_providers', ['code' => 'manual', 'is_active' => false]);
    }

    public function test_requires_admin_guard(): void
    {
        // Không login admin ⇒ 401/403.
        $this->getJson('/api/v1/admin/ai-providers')->assertStatus(401);
    }
}
