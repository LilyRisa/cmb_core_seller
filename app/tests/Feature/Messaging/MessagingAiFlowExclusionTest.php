<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Messaging\Services\AiFlowExclusionService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Loại trừ lẫn nhau Tầng 2 (ADR-0022 §4): AI auto-reply FB XOR flow `inbox_any` FB.
 */
class MessagingAiFlowExclusionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'ExclShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);
    }

    private function catchAllFlow(string $status = AutomationFlow::STATUS_ACTIVE): AutomationFlow
    {
        return AutomationFlow::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Mọi tin', 'provider' => 'facebook_page',
            'status' => $status, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'trigger_config' => [], 'enabled' => true, 'version' => 1,
            'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []],
        ]);
    }

    public function test_enabling_facebook_ai_pauses_catch_all_flow(): void
    {
        $flow = $this->catchAllFlow();

        $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->patchJson('/api/v1/messaging/settings', [
                'ai_provider_code' => 'manual', 'ai_enabled' => true,
                'auto_mode_marketplace' => false, 'auto_mode_facebook' => true,
            ])
            ->assertOk()
            ->assertJsonPath('meta.paused_catch_all_flows', 1)
            ->assertJsonPath('data.auto_mode_facebook', true);

        $this->assertSame(AutomationFlow::STATUS_PAUSED, $flow->fresh()->status);
    }

    public function test_enabling_facebook_ai_does_not_touch_first_message_flow(): void
    {
        $fm = AutomationFlow::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Chào', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            'trigger_config' => [], 'enabled' => true, 'version' => 1,
            'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []],
        ]);

        $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->patchJson('/api/v1/messaging/settings', [
                'ai_provider_code' => 'manual', 'ai_enabled' => true,
                'auto_mode_marketplace' => false, 'auto_mode_facebook' => true,
            ])
            ->assertOk()
            ->assertJsonPath('meta.paused_catch_all_flows', 0);

        // first_message là Tầng 1 — KHÔNG bị tạm dừng.
        $this->assertSame(AutomationFlow::STATUS_ACTIVE, $fm->fresh()->status);
    }

    public function test_activating_catch_all_flow_disables_facebook_ai(): void
    {
        MessagingSetting::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'ai_provider_code' => 'manual', 'ai_enabled' => true,
            'auto_mode_marketplace' => true, 'auto_mode_facebook' => true,
        ]);

        $disabled = app(AiFlowExclusionService::class)->disableFacebookAiAuto((int) $this->tenant->getKey());

        $this->assertTrue($disabled);
        $setting = MessagingSetting::withoutGlobalScopes()->find($this->tenant->getKey());
        $this->assertFalse((bool) $setting->auto_mode_facebook);
        // Marketplace KHÔNG bị ảnh hưởng (chỉ tách nhóm Facebook).
        $this->assertTrue((bool) $setting->auto_mode_marketplace);
    }

    public function test_catch_all_flow_disables_ai_only_for_its_pages(): void
    {
        $pageA = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'pa', 'shop_name' => 'A', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $pageB = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'pb', 'shop_name' => 'B', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        foreach ([$pageA, $pageB] as $p) {
            MessagingAccountMeta::query()->create([
                'channel_account_id' => $p->id, 'tenant_id' => $this->tenant->getKey(),
                'messaging_enabled' => true, 'ai_auto_mode' => true,
            ]);
        }

        $flow = AutomationFlow::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Mọi tin A', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'trigger_config' => [], 'enabled' => true, 'applies_all_pages' => false, 'version' => 1,
            'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []],
        ]);
        $flow->pages()->attach($pageA->getKey(), ['tenant_id' => $this->tenant->getKey()]);

        $changed = app(AiFlowExclusionService::class)->disableFacebookAiAutoForFlow($flow);

        $this->assertTrue($changed);
        $this->assertFalse((bool) MessagingAccountMeta::withoutGlobalScopes()->find($pageA->id)->ai_auto_mode, 'page A tắt AI');
        $this->assertTrue((bool) MessagingAccountMeta::withoutGlobalScopes()->find($pageB->id)->ai_auto_mode, 'page B không đổi');
    }

    public function test_disable_is_one_directional_no_auto_restore(): void
    {
        // Tắt AI FB không tự bật lại flow đã pause.
        $flow = $this->catchAllFlow(AutomationFlow::STATUS_PAUSED);
        MessagingSetting::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'ai_provider_code' => 'manual', 'ai_enabled' => true,
            'auto_mode_marketplace' => false, 'auto_mode_facebook' => true,
        ]);

        $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->patchJson('/api/v1/messaging/settings', [
                'ai_provider_code' => 'manual', 'ai_enabled' => true,
                'auto_mode_marketplace' => false, 'auto_mode_facebook' => false,
            ])
            ->assertOk();

        // Flow vẫn paused — không auto-restore.
        $this->assertSame(AutomationFlow::STATUS_PAUSED, $flow->fresh()->status);
    }
}
