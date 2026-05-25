<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\OrderReturn;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

class ReturnsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        F::configure();
        config(['integrations.tiktok.returns_enabled' => true]);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop returns api']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => F::SHOP_ID,
            'shop_name' => 'X', 'status' => ChannelAccount::STATUS_ACTIVE, 'access_token' => 'tk',
            'meta' => ['shop_cipher' => F::SHOP_CIPHER],
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeReturn(string $kind = 'return', AfterSalesStatus $status = AfterSalesStatus::Requested): OrderReturn
    {
        return OrderReturn::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->account->getKey(),
            'source' => 'tiktok', 'external_return_id' => $kind === 'cancel' ? 'CXL-9' : 'RET-9',
            'external_order_id' => F::ORDER_ID, 'kind' => $kind, 'status' => $status,
            'raw_status' => 'RETURN_OR_REFUND_REQUEST_PENDING', 'refund_amount' => 50000, 'currency' => 'VND',
            'source_updated_at' => now()->subHour(),
        ]);
    }

    public function test_index_and_stats_list_returns(): void
    {
        $this->makeReturn();
        $this->makeReturn('cancel');

        $list = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/returns')->assertOk();
        $this->assertSame(2, $list->json('meta.pagination.total'));

        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/returns?open_only=1')->assertOk()->assertJsonPath('meta.pagination.total', 2);
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/returns/stats')->assertOk()->assertJsonPath('data.requested', 2);
    }

    public function test_approve_calls_channel_and_updates_status(): void
    {
        Queue::fake();
        Http::fake(['*/return_refund/202309/returns/RET-9/approve*' => Http::response(F::envelope([]))]);
        $ret = $this->makeReturn();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/returns/{$ret->getKey()}/approve")
            ->assertOk()->assertJsonPath('data.status', 'approved');

        Http::assertSent(fn ($r) => str_contains((string) $r->url(), '/return_refund/202309/returns/RET-9/approve'));
        $this->assertSame(AfterSalesStatus::Approved, $ret->fresh()->status);
    }

    public function test_reject_cancellation_calls_cancellation_endpoint(): void
    {
        Queue::fake();
        Http::fake(['*/return_refund/202309/cancellations/CXL-9/reject*' => Http::response(F::envelope([]))]);
        $ret = $this->makeReturn('cancel');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/returns/{$ret->getKey()}/reject")
            ->assertOk()->assertJsonPath('data.status', 'rejected');

        Http::assertSent(fn ($r) => str_contains((string) $r->url(), '/return_refund/202309/cancellations/CXL-9/reject'));
    }

    public function test_returns_are_tenant_isolated(): void
    {
        $ret = $this->makeReturn();
        $other = User::factory()->create();
        $otherTenant = Tenant::create(['name' => 'B']);
        $otherTenant->users()->attach($other->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($other)->withHeaders(['X-Tenant-Id' => (string) $otherTenant->getKey()])
            ->getJson("/api/v1/returns/{$ret->getKey()}")->assertNotFound();
    }
}
