<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * TDD — GET /api/v1/messaging/capabilities (Phase A2).
 */
class MessagingCapabilitiesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'CapsShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activateSubscription(Plan::CODE_PRO);

        // Wire tiktok_chat + shopee_chat (plus the always-loaded manual)
        ShopeeFixtures::configure();
        config(['integrations.messaging' => ['tiktok_chat', 'shopee_chat']]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activateSubscription(string $planCode): void
    {
        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        $now = now();

        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    public function test_capabilities_returns_enabled_providers(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/capabilities')
            ->assertOk();

        $data = $response->json('data');

        // manual always loaded; tiktok_chat + shopee_chat via config
        $this->assertArrayHasKey('manual', $data);
        $this->assertArrayHasKey('tiktok_chat', $data);
        $this->assertArrayHasKey('shopee_chat', $data);

        // Each provider exposes outbound.text = true
        $this->assertTrue($data['manual']['outbound.text']);
        $this->assertTrue($data['tiktok_chat']['outbound.text']);
        $this->assertTrue($data['shopee_chat']['outbound.text']);

        // lazada_chat is NOT enabled (not in config) — must be absent
        $this->assertArrayNotHasKey('lazada_chat', $data);

        // TikTok does not support video outbound
        $this->assertFalse($data['tiktok_chat']['outbound.video']);
    }
}
