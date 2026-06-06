<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Khi tạo/sửa master SKU ghép với listing sàn (Lazada/Shopee/TikTok), ảnh sản phẩm đã kéo về
 * `channel_listings.image` phải được tải về kho media (R2) và gắn vào SKU. Best-effort.
 */
class SkuImageFromListingTest extends TestCase
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

    /** Bytes có magic PNG để MediaUploader nhận diện là ảnh. */
    private function pngBytes(): string
    {
        return "\x89PNG\x0D\x0A\x1A\x0A".str_repeat("\x00", 64);
    }

    private function shop(string $ext): ChannelAccount
    {
        return ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => $ext, 'shop_name' => 'S', 'status' => 'active',
        ]);
    }

    public function test_creating_sku_from_listing_pulls_product_image(): void
    {
        Storage::fake('public');
        $shop = $this->shop('s1');
        ChannelListing::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $shop->getKey(),
            'external_sku_id' => 'EXT-1', 'seller_sku' => 'SKU1', 'title' => 'Áo',
            'currency' => 'VND', 'is_active' => true, 'image' => 'https://cdn.lazada.test/p/img.jpg',
        ]);
        Http::fake(['cdn.lazada.test/*' => Http::response($this->pngBytes(), 200, ['Content-Type' => 'image/png'])]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/skus', [
            'sku_code' => 'SKU1', 'name' => 'Áo thun',
            'mappings' => [['channel_account_id' => $shop->getKey(), 'external_sku_id' => 'EXT-1', 'seller_sku' => 'SKU1']],
        ])->assertCreated();

        $sku = Sku::withoutGlobalScope(TenantScope::class)->findOrFail($res->json('data.id'));
        $this->assertNotEmpty($sku->image_url, 'SKU phải có ảnh lấy từ listing sàn.');
        $this->assertNotEmpty($sku->image_path);
        Storage::disk('public')->assertExists((string) $sku->image_path);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'cdn.lazada.test'));
    }

    public function test_no_image_call_when_listing_has_no_image(): void
    {
        Storage::fake('public');
        $shop = $this->shop('s2');
        ChannelListing::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $shop->getKey(),
            'external_sku_id' => 'EXT-2', 'seller_sku' => 'SKU2', 'currency' => 'VND', 'is_active' => true,
        ]);
        Http::fake();

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/skus', [
            'sku_code' => 'SKU2', 'name' => 'Quần',
            'mappings' => [['channel_account_id' => $shop->getKey(), 'external_sku_id' => 'EXT-2', 'seller_sku' => 'SKU2']],
        ])->assertCreated();

        $sku = Sku::withoutGlobalScope(TenantScope::class)->findOrFail($res->json('data.id'));
        $this->assertNull($sku->image_url);
        Http::assertNothingSent();
    }

    public function test_non_image_response_is_ignored(): void
    {
        Storage::fake('public');
        $shop = $this->shop('s3');
        ChannelListing::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $shop->getKey(),
            'external_sku_id' => 'EXT-3', 'seller_sku' => 'SKU3', 'currency' => 'VND', 'is_active' => true,
            'image' => 'https://cdn.lazada.test/p/notfound.html',
        ]);
        // Sàn trả trang HTML (không phải ảnh) → magic bytes không khớp → bỏ qua, không lưu rác.
        Http::fake(['cdn.lazada.test/*' => Http::response('<html>error</html>', 200, ['Content-Type' => 'text/html'])]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/skus', [
            'sku_code' => 'SKU3', 'name' => 'Nón',
            'mappings' => [['channel_account_id' => $shop->getKey(), 'external_sku_id' => 'EXT-3', 'seller_sku' => 'SKU3']],
        ])->assertCreated();

        $sku = Sku::withoutGlobalScope(TenantScope::class)->findOrFail($res->json('data.id'));
        $this->assertNull($sku->image_url);
    }
}
