<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use CMBcoreSeller\Integrations\Carriers\Manual\ManualCarrierConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShipmentQuoteAllTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        app(CarrierRegistry::class)->register('ghtk', GhtkConnector::class);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeGhtkAccount(string $name, bool $active = true): CarrierAccount
    {
        return CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'carrier' => 'ghtk', 'name' => $name,
            'credentials' => ['token' => 'T', 'client_source' => 'S1'], 'is_active' => $active,
            'meta' => ['from_address' => ['province_name' => 'Hà Nội', 'district_name' => 'Q1'], 'defaults' => ['package' => ['weight_grams' => 500]]],
        ]);
    }

    public function test_aggregates_quotes_from_all_active_accounts(): void
    {
        $this->makeGhtkAccount('GHTK — Kho A');
        $this->makeGhtkAccount('GHTK — Kho B (inactive)', active: false);
        $this->makeGhtkAccount('GHTK — Kho lỗi');

        Http::fake(function ($request) {
            $body = $request->data();
            // Phân biệt account theo pick_district (mỗi account cùng 1 pick_district trong test này nên
            // dùng thứ tự request thay thế: account đầu OK, account sau lỗi — giả lập qua đếm lần gọi.
            static $calls = 0;
            $calls++;

            return $calls === 1
                ? Http::response(['success' => true, 'fee' => ['name' => 'area1', 'fee' => 26400, 'insurance_fee' => 0]])
                : Http::response(['success' => false, 'message' => 'Địa chỉ không hỗ trợ'], 400);
        });

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote-all', ['recipient' => ['province' => 'Hà Nội', 'district' => 'Cầu Giấy']])
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(2, $rows, 'Chỉ 2 tài khoản active (inactive bị loại hoàn toàn).');
        $names = collect($rows)->pluck('account_name')->all();
        $this->assertContains('GHTK — Kho A', $names);
        $this->assertContains('GHTK — Kho lỗi', $names);
        $this->assertNotContains('GHTK — Kho B (inactive)', $names);

        $errorRow = collect($rows)->firstWhere('account_name', 'GHTK — Kho lỗi');
        $this->assertArrayHasKey('error', $errorRow);
    }

    public function test_does_not_leak_other_tenant_accounts(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other']);
        CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $otherTenant->getKey(), 'carrier' => 'ghtk', 'name' => 'Other shop GHTK', 'is_active' => true,
            'credentials' => ['token' => 'T'], 'meta' => ['from_address' => ['province_name' => 'HCM'], 'defaults' => ['package' => ['weight_grams' => 500]]],
        ]);

        Http::fake(fn () => Http::response(['success' => true, 'fee' => ['name' => 'area1', 'fee' => 1000, 'insurance_fee' => 0]]));

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote-all', ['recipient' => ['province' => 'Hà Nội', 'district' => 'Cầu Giấy']])
            ->assertOk();

        $this->assertCount(0, $res->json('data'));
    }

    public function test_carrier_without_quote_capability_is_silently_skipped(): void
    {
        app(CarrierRegistry::class)->register('manual', ManualCarrierConnector::class);

        CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'carrier' => 'manual', 'name' => 'Tự vận chuyển',
            'credentials' => [], 'is_active' => true, 'meta' => [],
        ]);
        $this->makeGhtkAccount('GHTK — Kho A');

        Http::fake(fn () => Http::response(['success' => true, 'fee' => ['name' => 'area1', 'fee' => 26400, 'insurance_fee' => 0]]));

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote-all', ['recipient' => ['province' => 'Hà Nội', 'district' => 'Cầu Giấy']])
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(1, $rows, 'Tài khoản manual (không hỗ trợ quote) bị bỏ qua hoàn toàn, không có dòng lỗi.');
        $names = collect($rows)->pluck('account_name')->all();
        $this->assertContains('GHTK — Kho A', $names);
        $this->assertNotContains('Tự vận chuyển', $names);
    }
}
