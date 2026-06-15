<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AI gợi ý mô tả cho sản phẩm ĐÃ có trên sàn (channel-listings/{id}/ai-description).
 * Provider `manual` (deterministic, free) ⇒ không tốn LLM credit.
 */
class MarketplaceListingAiDescriptionTest extends TestCase
{
    use RefreshDatabase;

    private function ownerAndTenant(string $name): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::create(['name' => $name]);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        return [$owner, $tenant];
    }

    private function subscribe(Tenant $tenant, string $code): void
    {
        $plan = Plan::query()->where('code', $code)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->copy()->addMonth(),
        ]);
    }

    private function makeListing(Tenant $tenant): ChannelListing
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 's1', 'shop_name' => 'S1', 'status' => 'active', 'access_token' => 'at',
        ]);

        return ChannelListing::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->getKey(),
            'external_product_id' => 'p1', 'external_sku_id' => 'sku1', 'seller_sku' => 'SS1',
            'title' => 'Áo thun nam', 'variation' => 'Đỏ / L', 'price' => 100000, 'currency' => 'VND',
        ]);
    }

    public function test_ai_description_returns_suggestion(): void
    {
        $this->seed(BillingPlanSeeder::class);
        [$owner, $tenant] = $this->ownerAndTenant('Shop A');
        $this->subscribe($tenant, Plan::CODE_PRO);
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true, 'default_model' => 'manual-v1']);

        $listing = $this->makeListing($tenant);

        $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson("/api/v1/channel-listings/{$listing->getKey()}/ai-description", ['description' => 'mô tả cũ'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['description', 'provider']]);
    }

    public function test_ai_description_blocked_without_ai_plan(): void
    {
        $this->seed(BillingPlanSeeder::class);
        [$owner, $tenant] = $this->ownerAndTenant('Shop B');
        $this->subscribe($tenant, Plan::CODE_STARTER); // không có feature 'ai'

        $listing = $this->makeListing($tenant);

        $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson("/api/v1/channel-listings/{$listing->getKey()}/ai-description", ['description' => ''])
            ->assertStatus(402);
    }
}
