<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Extension copy sản phẩm: `POST /products` kèm `variants[]` phải tạo master SKU
 * tương ứng (giá/biến thể) để sản phẩm sao chép dùng được cho luồng đăng sàn.
 */
class CopyProductVariantsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);
    }

    /** @param array<string,mixed> $body */
    private function postProduct(array $body)
    {
        return $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson('/api/v1/products', $body);
    }

    public function test_copy_with_variants_creates_a_sku_per_variant(): void
    {
        $res = $this->postProduct([
            'name' => 'Máy cạo râu mini coclear CS007',
            'image' => 'https://cdn/x.jpg',
            'unit' => 'pc',
            'variants' => [
                ['name' => 'Cam Hermes', 'price' => 249000, 'stock' => 0, 'sku' => '237535767487'],
                ['name' => 'Xám PVD', 'price' => 308000, 'stock' => 0, 'sku' => '237535767488'],
            ],
        ]);

        $res->assertCreated();
        $this->assertSame(2, $res->json('data.skus_count'));

        $productId = (int) $res->json('data.id');
        $this->assertDatabaseHas('skus', [
            'product_id' => $productId,
            'sku_code' => 'CP'.$productId.'-1',
            'ref_sale_price' => 249000,
        ]);
        $this->assertDatabaseHas('skus', [
            'product_id' => $productId,
            'sku_code' => 'CP'.$productId.'-2',
            'ref_sale_price' => 308000,
        ]);

        // Mã SKU gốc của sàn được giữ trong attributes.source_sku.
        $sku = Sku::where('product_id', $productId)->orderBy('id')->first();
        $this->assertSame('237535767487', $sku->attributes['source_sku'] ?? null);
        $this->assertSame('Cam Hermes', $sku->attributes['variant'] ?? null);
    }

    public function test_copy_without_variants_but_unit_price_creates_one_sku(): void
    {
        $res = $this->postProduct(['name' => 'Sản phẩm đơn', 'unit_price' => 150000]);

        $res->assertCreated();
        $this->assertSame(1, $res->json('data.skus_count'));
        $this->assertDatabaseHas('skus', ['product_id' => (int) $res->json('data.id'), 'ref_sale_price' => 150000]);
    }

    public function test_plain_product_without_variants_creates_no_sku(): void
    {
        $res = $this->postProduct(['name' => 'SP thường (SPA)']);

        $res->assertCreated();
        $this->assertSame(0, $res->json('data.skus_count'));
    }
}
