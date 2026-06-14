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

    public function test_copy_with_variants_does_not_create_redundant_master_skus(): void
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
        // Phương án A: KHÔNG tự tạo master SKU (tránh SKU dư thừa).
        $this->assertSame(0, $res->json('data.skus_count'));
        $this->assertSame(0, Sku::where('product_id', (int) $res->json('data.id'))->count());

        // Biến thể thô được giữ trong meta để SKU nháp đăng sàn seed từ đó.
        $res->assertJsonPath('data.meta.variants.0.name', 'Cam Hermes');
        $res->assertJsonPath('data.meta.variants.1.price', 308000);
    }

    public function test_plain_product_without_variants_creates_no_sku(): void
    {
        $res = $this->postProduct(['name' => 'SP thường (SPA)']);

        $res->assertCreated();
        $this->assertSame(0, $res->json('data.skus_count'));
    }
}
