<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * POST /api/v1/fulfillment/quote — gợi ý phí ship carrier-agnostic. GHTK trả phí; carrier
 * không hỗ trợ quote (GHN) ⇒ data null; lỗi/không có account ⇒ data null (không chặn FE).
 */
class ShippingQuoteTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        app(CarrierRegistry::class)->register('ghtk', GhtkConnector::class);
        app(CarrierRegistry::class)->register('ghn', GhnConnector::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'QuoteShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function ghtkAccount(): CarrierAccount
    {
        return CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'carrier' => 'ghtk', 'name' => 'GHTK', 'is_active' => true, 'is_default' => true,
            'credentials' => ['token' => 'T', 'client_source' => 'S1'],
            'meta' => ['from_address' => ['province_name' => 'TP. Hồ Chí Minh', 'district_name' => 'Quận 1', 'address' => 'Số 1']],
        ]);
    }

    private function payload(int $accountId): array
    {
        return [
            'carrier_account_id' => $accountId,
            'weight_grams' => 500,
            'value' => 200000,
            'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy', 'ward' => 'Phường Dịch Vọng'],
        ];
    }

    public function test_quote_returns_fee_for_ghtk(): void
    {
        Http::fake(['*/services/shipment/fee*' => Http::response([
            'success' => true, 'fee' => ['name' => 'area2', 'fee' => 30000, 'insurance_fee' => 1000],
        ])]);
        $acc = $this->ghtkAccount();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote', $this->payload($acc->getKey()))
            ->assertOk()
            ->assertJsonPath('data.carrier', 'ghtk')
            ->assertJsonPath('data.fee', 30000)
            ->assertJsonPath('data.insurance_fee', 1000);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/services/shipment/fee')
            && str_contains($req->url(), 'weight=500'));
    }

    public function test_quote_works_with_two_tier_address_no_district(): void
    {
        Http::fake(['*/services/shipment/fee*' => Http::response([
            'success' => true, 'fee' => ['name' => 'area1', 'fee' => 22000, 'insurance_fee' => 0],
        ])]);
        $acc = $this->ghtkAccount();

        // 2 cấp: chỉ province + ward (không district) — không được trả 422.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote', [
                'carrier_account_id' => $acc->getKey(), 'weight_grams' => 500,
                'recipient' => ['province' => 'Hà Nội', 'ward' => 'Phường Cầu Giấy'],
            ])
            ->assertOk()
            ->assertJsonPath('data.fee', 22000);
    }

    public function test_quote_returns_null_for_carrier_without_quote_capability(): void
    {
        $ghn = CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'carrier' => 'ghn', 'name' => 'GHN', 'is_active' => true,
            'credentials' => ['token' => 'X', 'shop_id' => 1],
        ]);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote', $this->payload($ghn->getKey()))
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_quote_returns_null_on_api_error(): void
    {
        Http::fake(['*/services/shipment/fee*' => Http::response(['success' => false, 'message' => 'lỗi'], 200)]);
        $acc = $this->ghtkAccount();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote', $this->payload($acc->getKey()))
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_quote_requires_weight(): void
    {
        $acc = $this->ghtkAccount();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote', [
                'carrier_account_id' => $acc->getKey(),
                'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy'],
            ])
            ->assertStatus(422);
    }
}
