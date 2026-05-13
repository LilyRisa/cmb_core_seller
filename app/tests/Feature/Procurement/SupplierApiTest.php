<?php

namespace Tests\Feature\Procurement;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Procurement\Models\Supplier;
use CMBcoreSeller\Modules\Procurement\Models\SupplierPrice;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_create_list_update_delete_supplier(): void
    {
        // Tạo
        $r = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/suppliers', [
            'name' => 'Công ty ABC', 'phone' => '0901234567', 'tax_code' => '0123456789',
            'address' => 'Hà Nội', 'payment_terms_days' => 30, 'note' => 'NCC chính',
        ])->assertCreated();
        $id = (int) $r->json('data.id');
        $r->assertJsonPath('data.code', 'NCC-0001')->assertJsonPath('data.name', 'Công ty ABC')->assertJsonPath('data.payment_terms_days', 30);

        // List
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/suppliers')
            ->assertOk()->assertJsonPath('meta.pagination.total', 1)->assertJsonPath('data.0.code', 'NCC-0001');

        // Tìm theo q
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/suppliers?q=ABC')
            ->assertOk()->assertJsonPath('meta.pagination.total', 1);

        // Sửa
        $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/suppliers/{$id}", ['phone' => '0987654321', 'is_active' => false])
            ->assertOk()->assertJsonPath('data.phone', '0987654321')->assertJsonPath('data.is_active', false);

        // Xoá (soft)
        $this->actingAs($this->owner)->withHeaders($this->h())->deleteJson("/api/v1/suppliers/{$id}")->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertSame(0, $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/suppliers')->json('meta.pagination.total'));
        $this->assertNotNull(Supplier::withoutGlobalScope(TenantScope::class)->withTrashed()->find($id)?->deleted_at);
    }

    public function test_rbac_and_tenant_isolation(): void
    {
        $accountant = User::factory()->create();
        $this->tenant->users()->attach($accountant->getKey(), ['role' => Role::Accountant->value]);
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);

        // Viewer 403 (không có procurement.view)
        $this->actingAs($viewer)->withHeaders($this->h())->getJson('/api/v1/suppliers')->assertForbidden();
        // Accountant đọc được, không tạo được
        $this->actingAs($accountant)->withHeaders($this->h())->getJson('/api/v1/suppliers')->assertOk();
        $this->actingAs($accountant)->withHeaders($this->h())->postJson('/api/v1/suppliers', ['name' => 'X'])->assertForbidden();

        // Tenant 2 không thấy được NCC của tenant 1
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/suppliers', ['name' => 'NCC T1'])->assertCreated();
        $other = Tenant::create(['name' => 'Other']);
        $u2 = User::factory()->create();
        $other->users()->attach($u2->getKey(), ['role' => Role::Owner->value]);
        $this->actingAs($u2)->withHeaders(['X-Tenant-Id' => (string) $other->getKey()])->getJson('/api/v1/suppliers')
            ->assertOk()->assertJsonPath('meta.pagination.total', 0);
    }

    public function test_supplier_price_set_and_default_swap(): void
    {
        $supplier = Supplier::query()->create(['tenant_id' => $this->tenant->getKey(), 'code' => 'NCC-X', 'name' => 'X']);
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A', 'name' => 'A']);

        // Set giá đầu — is_default
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/suppliers/{$supplier->getKey()}/prices", [
            'sku_id' => $sku->getKey(), 'unit_cost' => 12000, 'is_default' => true,
        ])->assertCreated()->assertJsonPath('data.is_default', true);
        // Set thêm giá thứ 2 cũng is_default → giá cũ bị unset
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/suppliers/{$supplier->getKey()}/prices", [
            'sku_id' => $sku->getKey(), 'unit_cost' => 13000, 'is_default' => true, 'valid_from' => '2026-05-01',
        ])->assertCreated()->assertJsonPath('data.is_default', true);
        $defaults = SupplierPrice::query()->where('supplier_id', $supplier->getKey())->where('is_default', true)->get();
        $this->assertCount(1, $defaults);
        $this->assertSame(13000, (int) $defaults->first()->unit_cost);
    }
}
