<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Asserts that OrderResource exposes `prepare_block_reason` (a VN label or null)
 * derived from the connector's pure status-mapping. No real API calls — pure mapping.
 */
class OrderResourcePrepareBlockReasonTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private ChannelAccount $shopeeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Register shopee connector (env only loads manual,tiktok; shopee is env-gated).
        app(ChannelRegistry::class)->register('shopee', ShopeeConnector::class);

        $this->user = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Test Shop']);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);

        $this->shopeeAccount = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'shopee',
            'external_shop_id' => 'sp-shop-1',
            'shop_name' => 'Shopee Test Shop',
            'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function header(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    /** Helper: seed a channel order with the given raw/standard status and index via API. */
    private function indexOrdersForRawStatus(string $provider, string $rawStatus, string $standard): array
    {
        Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => $provider,
            'channel_account_id' => $this->shopeeAccount->getKey(),
            'external_order_id' => 'EXT-'.uniqid(),
            'order_number' => 'ORD-'.uniqid(),
            'status' => StandardOrderStatus::from($standard),
            'raw_status' => $rawStatus,
            'currency' => 'VND',
            'grand_total' => 100000,
            'item_total' => 100000,
            'placed_at' => now()->subHour(),
            'source_updated_at' => now()->subHour(),
            'tags' => [],
        ]);

        return $this->actingAs($this->user)
            ->withHeaders($this->header())
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->json();
    }

    /** Helper: seed a manual order (no channel_account_id) and index via API. */
    private function indexManualOrder(string $standard): array
    {
        Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual',
            'channel_account_id' => null,
            'external_order_id' => 'MAN-'.uniqid(),
            'order_number' => 'MAN-'.uniqid(),
            'status' => StandardOrderStatus::from($standard),
            'raw_status' => $standard,
            'currency' => 'VND',
            'grand_total' => 50000,
            'item_total' => 50000,
            'placed_at' => now()->subHour(),
            'source_updated_at' => now()->subHour(),
            'tags' => [],
        ]);

        return $this->actingAs($this->user)
            ->withHeaders($this->header())
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->json();
    }

    public function test_unpaid_channel_order_exposes_reason(): void
    {
        $payload = $this->indexOrdersForRawStatus('shopee', 'UNPAID', standard: 'unpaid');
        $row = $payload['data'][0];
        $this->assertSame('Chờ người mua thanh toán', $row['prepare_block_reason']);
    }

    public function test_ready_channel_order_has_null_reason(): void
    {
        $payload = $this->indexOrdersForRawStatus('shopee', 'READY_TO_SHIP', standard: 'ready_to_ship');
        $this->assertNull($payload['data'][0]['prepare_block_reason']);
    }

    public function test_manual_order_has_null_reason(): void
    {
        $payload = $this->indexManualOrder(standard: 'pending');
        $this->assertNull($payload['data'][0]['prepare_block_reason']);
    }
}
