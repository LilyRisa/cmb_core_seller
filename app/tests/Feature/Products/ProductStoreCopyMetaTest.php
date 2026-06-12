<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/v1/products nhận thêm các field "giàu" từ extension copy sản phẩm
 * (giá, mô tả, ảnh gallery, video, biến thể) và gộp vào `meta` để không mất dữ
 * liệu — KHÔNG ảnh hưởng luồng tạo product của SPA.
 */
class ProductStoreCopyMetaTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_extension_rich_payload_is_folded_into_meta(): void
    {
        $payload = [
            'name' => 'Áo thun basic',
            'description' => '<p>Cotton 100%</p>',
            'unit_price' => 199000,
            'unit' => 'pc',
            'thumbnail_img' => 'https://cf.shopee.vn/file/abc',
            'image_links' => ['https://cf.shopee.vn/file/abc', 'https://cf.shopee.vn/file/def'],
            'video_url' => 'https://cvf.shopee.vn/v.mp4',
            'source' => 'shopee',
            'source_url' => 'https://shopee.vn/product/1/2',
            'variants' => [
                ['name' => 'Đỏ / M', 'price' => 189000, 'stock' => 12, 'sku' => '1001', 'image' => 'https://cf.shopee.vn/file/v'],
            ],
        ];

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/products', $payload)
            ->assertCreated();

        // Thiếu `image` ⇒ dùng thumbnail_img làm ảnh đại diện.
        $res->assertJsonPath('data.image', 'https://cf.shopee.vn/file/abc');
        // Dữ liệu giàu được giữ trong meta.
        $res->assertJsonPath('data.meta.unit_price', 199000);
        $res->assertJsonPath('data.meta.description', '<p>Cotton 100%</p>');
        $res->assertJsonPath('data.meta.source', 'shopee');
        $res->assertJsonPath('data.meta.image_links.1', 'https://cf.shopee.vn/file/def');
        $res->assertJsonPath('data.meta.variants.0.name', 'Đỏ / M');
        $res->assertJsonPath('data.meta.variants.0.price', 189000);
    }

    public function test_spa_payload_is_unchanged(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/products', [
                'name' => 'SP thường',
                'image' => 'https://x/y.jpg',
                'brand' => 'Acme',
                'category' => 'Áo',
                'meta' => ['note' => 'abc'],
            ])
            ->assertCreated();

        $res->assertJsonPath('data.image', 'https://x/y.jpg');
        $res->assertJsonPath('data.brand', 'Acme');
        $res->assertJsonPath('data.meta', ['note' => 'abc']);
    }
}
