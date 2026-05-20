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
use Tests\TestCase;

class MessagingChannelControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'ChanShop']);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_id' => 'APP123',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
        $this->activatePro();
    }

    private function activatePro(): void
    {
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
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

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return $user;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_owner_can_start_facebook_connect(): void
    {
        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/facebook/connect')
            ->assertOk()
            ->assertJsonPath('data.authorize_url', fn ($url) => str_contains((string) $url, 'facebook.com'));
    }

    public function test_staff_cs_cannot_start_facebook_connect(): void
    {
        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/facebook/connect')
            ->assertStatus(403);
    }

    public function test_index_lists_only_facebook_pages_without_token(): void
    {
        \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_1', 'shop_name' => 'Shop FB', 'status' => 'active',
            'access_token' => 'SECRET_PAGE_TOKEN', 'messaging_enabled' => true,
        ]);
        // 1 gian hàng sàn — KHÔNG được xuất hiện trong list facebook.
        \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ_1', 'shop_name' => 'Shop LZ', 'status' => 'active',
        ]);

        $res = $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/channels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_shop_id', 'PAGE_1')
            ->assertJsonPath('data.0.messaging_enabled', true)
            ->assertJsonPath('data.0.token_expired', false);

        // Không lộ token
        $this->assertStringNotContainsString('SECRET_PAGE_TOKEN', $res->getContent());
    }
}
