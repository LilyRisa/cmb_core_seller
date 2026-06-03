<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
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
 * Sửa tài khoản ĐVVC: PATCH phải MERGE credentials/meta (không ghi đè) — để user bổ sung địa chỉ kho
 * cho tài khoản GHTK đã tạo mà không phải xoá đi tạo lại, và không mất token / trạng thái verify cũ.
 */
class CarrierAccountUpdateTest extends TestCase
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

    private function account(): CarrierAccount
    {
        return CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'carrier' => 'ghtk', 'name' => 'GHTK cũ',
            'is_active' => true, 'is_default' => false,
            'credentials' => ['token' => 'TOK-OLD', 'client_source' => 'S-OLD'],
            'meta' => ['from_address' => ['name' => 'Kho cũ', 'province_name' => 'A'], 'last_verify_ok' => true],
        ]);
    }

    public function test_update_meta_merges_and_keeps_credentials_and_verify_state(): void
    {
        $acc = $this->account();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/carrier-accounts/{$acc->getKey()}", [
                'meta' => ['from_address' => ['name' => 'Kho Hà Nội', 'phone' => '0901', 'address' => 'Số 1', 'province_name' => 'Hà Nội', 'ward_name' => 'P. Cầu Giấy']],
            ])->assertOk();

        $fresh = CarrierAccount::withoutGlobalScope(TenantScope::class)->find($acc->getKey());
        // Credentials giữ nguyên (không gửi lại).
        $this->assertSame('TOK-OLD', $fresh->credentials['token']);
        $this->assertSame('S-OLD', $fresh->credentials['client_source']);
        // from_address cập nhật mới.
        $this->assertSame('Kho Hà Nội', $fresh->meta['from_address']['name']);
        $this->assertSame('Hà Nội', $fresh->meta['from_address']['province_name']);
        // Các key meta khác (trạng thái verify) được giữ.
        $this->assertTrue($fresh->meta['last_verify_ok']);
    }

    public function test_update_credentials_merges_keeping_other_keys(): void
    {
        Http::fake(['*/services/shipment/list_pick_add*' => Http::response(['success' => true, 'data' => []])]);
        $acc = $this->account();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/carrier-accounts/{$acc->getKey()}", [
                'credentials' => ['token' => 'TOK-NEW'],
            ])->assertOk();

        $fresh = CarrierAccount::withoutGlobalScope(TenantScope::class)->find($acc->getKey());
        $this->assertSame('TOK-NEW', $fresh->credentials['token']);   // đổi
        $this->assertSame('S-OLD', $fresh->credentials['client_source']); // giữ
    }
}
