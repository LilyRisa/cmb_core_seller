<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelBusinessInfoApiTest extends TestCase
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

    /** Tạo nhanh 1 Facebook Page cho tenant hiện tại. */
    private function fbPage(string $externalId): ChannelAccount
    {
        return ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => $externalId, 'shop_name' => $externalId, 'status' => 'active',
            'access_token' => 'TOKEN_'.$externalId, 'messaging_enabled' => true,
        ]);
    }

    public function test_patch_business_info_persists_and_lists(): void
    {
        $page = $this->fbPage('PAGE_1');

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/channels/{$page->id}/business-info", [
                'business_info' => ['shop_name' => 'Shop A', 'phone' => '0909', 'address' => 'HN'],
            ])
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.business_info.shop_name', 'Shop A');

        $this->assertSame('0909', MessagingAccountMeta::query()->find($page->id)->business_info['phone']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'messaging.facebook_page.business_info']);

        $row = collect($this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/channels?provider=facebook_page')->json('data'))
            ->firstWhere('id', $page->id);
        $this->assertSame('Shop A', $row['business_info']['shop_name']);
    }

    public function test_bulk_business_info_applies_to_selected_pages_only(): void
    {
        $a = $this->fbPage('PAGE_A');
        $b = $this->fbPage('PAGE_B');
        $c = $this->fbPage('PAGE_C'); // không chọn ⇒ không bị đụng tới.

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->patchJson('/api/v1/messaging/channels/business-info', [
                'ids' => [$a->id, $b->id],
                'business_info' => ['shop_name' => 'Shop Chung', 'phone' => '0912'],
            ])
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.processed', 2);

        foreach ([$a->id, $b->id] as $id) {
            $this->assertSame('Shop Chung', MessagingAccountMeta::query()->find($id)?->business_info['shop_name']);
        }
        $this->assertNull(MessagingAccountMeta::query()->find($c->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'messaging.bulk_business_info']);
    }

    public function test_staff_cs_cannot_save_business_info(): void
    {
        $page = $this->fbPage('PAGE_D');

        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/channels/{$page->id}/business-info", [
                'business_info' => ['shop_name' => 'Shop X'],
            ])
            ->assertStatus(403);
    }
}
