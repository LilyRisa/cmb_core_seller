<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelListingGroupedTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        $this->accountId = (int) ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'shop1', 'shop_name' => 'Shop 1', 'shop_region' => 'VN',
            'status' => 'active', 'access_token' => 'tok',
        ])->getKey();
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeListing(string $extSku, ?string $extProductId, string $title, ?int $tenantId = null, ?int $accountId = null): ChannelListing
    {
        return ChannelListing::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId ?? $this->tenant->getKey(), 'channel_account_id' => $accountId ?? $this->accountId,
            'external_product_id' => $extProductId, 'external_sku_id' => $extSku, 'title' => $title,
            'currency' => 'VND', 'sync_status' => ChannelListing::SYNC_OK,
        ]);
    }

    public function test_groups_variants_by_external_product_id(): void
    {
        $this->makeListing('S1', 'P1', 'Máy cạo râu');
        $this->makeListing('S2', 'P1', 'Máy cạo râu');
        $this->makeListing('S3', 'P1', 'Máy cạo râu');
        $this->makeListing('S4', 'P2', 'Tượng chú tiểu');
        $this->makeListing('S5', null, 'Sản phẩm đơn lẻ');

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/channel-listings/grouped')
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(3, $rows, 'P1 (3 biến thể) + P2 (1) + null (1) = 3 nhóm.');

        $p1 = collect($rows)->firstWhere('external_product_id', 'P1');
        $this->assertSame(3, $p1['variant_count']);
        $this->assertCount(3, $p1['variants']);
        $this->assertSame('Máy cạo râu', $p1['title']);

        $p2 = collect($rows)->firstWhere('external_product_id', 'P2');
        $this->assertSame(1, $p2['variant_count']);

        $this->assertSame(3, $res->json('meta.pagination.total'), 'Tổng SỐ SẢN PHẨM (nhóm), không phải số dòng biến thể (5).');
    }

    public function test_pagination_counts_products_not_rows(): void
    {
        // 3 sản phẩm, mỗi sản phẩm 2 biến thể = 6 dòng; per_page=2 SẢN PHẨM/trang.
        foreach (['P1', 'P2', 'P3'] as $i => $pid) {
            $this->makeListing("S{$i}a", $pid, "SP {$pid}");
            $this->makeListing("S{$i}b", $pid, "SP {$pid}");
        }

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/channel-listings/grouped?per_page=2')
            ->assertOk();

        $this->assertCount(2, $res->json('data'));
        $this->assertSame(3, $res->json('meta.pagination.total'));
        $this->assertSame(2, $res->json('meta.pagination.total_pages'));
    }

    /**
     * Spec §5 — filter row-level (`mapped`) phải áp ĐÚNG TRƯỚC khi gộp nhóm: nhóm chỉ chứa các biến thể
     * khớp filter, `variant_count` phải khớp đúng `count(variants)` sau lọc (không phải tổng biến thể
     * thật của sản phẩm). Đây là thuộc tính tinh vi nhất của endpoint — 2 lần gọi applyFilters() (đếm
     * nhóm + lấy dòng) phải cùng áp 1 bộ filter, nếu lệch nhau sẽ ra variant_count sai.
     */
    public function test_mapped_filter_applies_within_group_before_counting(): void
    {
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'SKU-1', 'name' => 'Sản phẩm gốc',
        ]);
        $mapped = $this->makeListing('S1', 'P1', 'Máy cạo râu');
        $this->makeListing('S2', 'P1', 'Máy cạo râu');
        $this->makeListing('S3', 'P1', 'Máy cạo râu');
        SkuMapping::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_listing_id' => $mapped->getKey(),
            'sku_id' => $sku->getKey(), 'quantity' => 1, 'type' => 'single',
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/channel-listings/grouped?mapped=0')
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(1, $rows, 'Vẫn 1 nhóm (P1) dù 1/3 biến thể đã map.');
        $p1 = $rows[0];
        $this->assertSame(2, $p1['variant_count'], 'variant_count phải đếm SAU khi lọc mapped=0 (2/3), không phải tổng thật (3).');
        $this->assertCount(2, $p1['variants']);
        $skuIds = collect($p1['variants'])->pluck('external_sku_id')->all();
        $this->assertContains('S2', $skuIds);
        $this->assertContains('S3', $skuIds);
        $this->assertNotContains('S1', $skuIds, 'Biến thể đã map (S1) phải bị loại khỏi nhóm khi lọc mapped=0.');
    }

    public function test_does_not_leak_other_tenant_listings(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other']);
        $otherAccount = (int) ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $otherTenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'shop2', 'shop_name' => 'Shop 2', 'shop_region' => 'VN',
            'status' => 'active', 'access_token' => 'tok',
        ])->getKey();
        $this->makeListing('X1S1', 'X1', 'Khác tenant', tenantId: $otherTenant->getKey(), accountId: $otherAccount);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/channel-listings/grouped')
            ->assertOk();

        $this->assertCount(0, $res->json('data'));
    }
}
