<?php

declare(strict_types=1);

namespace Tests\Feature\Products;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\Contracts\PromotionConnector;
use CMBcoreSeller\Integrations\Channels\DTO\PromotionResultDTO;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Jobs\PushPromotionJob;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Products\Models\ChannelPromotion;
use CMBcoreSeller\Modules\Products\Services\PromotionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Chiến dịch giảm giá nhiều SKU: tạo nháp → đặt SKU (tính sale_price) → SKU bận → chặn
 * chồng lấn khi đẩy. CRUD chạy trên connector stub có ĐỐI TƯỢNG chương trình
 * (has_program_object=true, không gọi HTTP); push dùng Queue::fake để không chạy job thật.
 * Lazada (has_program_object=false) bị chặn tạo chiến dịch — chỉ test phát hiện SKU bận
 * qua special_price (giảm giá trực tiếp trên SKU).
 */
class PromotionCampaignTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private int $accountId;

    private $conn;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.lazada.app_key' => 'k', 'integrations.lazada.app_secret' => 's', 'integrations.lazada.api_base_url' => 'https://api.lazada.vn/rest']);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

        // Connector stub: có đối tượng chương trình + KHÔNG gọi HTTP (capabilities/listPromotions thuần).
        $conn = Mockery::mock(ProductPublishingConnector::class, PromotionConnector::class);
        $conn->shouldReceive('promotionCapabilities')->andReturn([
            'max_items_per_call' => 50, 'supports_percent' => true, 'has_program_object' => true, 'supports_time_of_day' => true,
        ]);
        $conn->shouldReceive('listPromotions')->andReturn([]);
        $cls = $conn::class;
        $this->app->instance($cls, $conn);
        app(PublisherRegistry::class)->register('stub', $cls);
        $this->conn = $conn;

        $account = ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'stub',
            'external_shop_id' => 'shop', 'shop_name' => 'Shop', 'status' => 'active', 'access_token' => 'tok',
        ]);
        $this->accountId = (int) $account->getKey();
    }

    /** @param  array<string,mixed>  $body */
    private function postAs(string $url, array $body = [])
    {
        return $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson($url, $body);
    }

    private function makeDraft(string $type = 'percent'): int
    {
        $res = $this->postAs('/api/v1/channel-promotions', [
            'channel_account_id' => $this->accountId,
            'title' => 'Sale 6.6',
            'discount_type' => $type,
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDays(5)->toIso8601String(),
        ]);
        $res->assertCreated();

        return (int) $res->json('data.id');
    }

    public function test_set_skus_computes_sale_price(): void
    {
        $id = $this->makeDraft('percent');

        $this->postAs("/api/v1/channel-promotions/{$id}/skus", [
            'skus' => [
                ['external_product_id' => 'p1', 'external_sku_id' => 's1', 'base_price' => 100000, 'discount_value' => 20],
                ['external_product_id' => 'p1', 'external_sku_id' => 's2', 'base_price' => 50000, 'discount_value' => 10],
            ],
        ])->assertOk()
            ->assertJsonPath('data.skus.0.sale_price', 80000)
            ->assertJsonPath('data.skus.1.sale_price', 45000);
    }

    public function test_busy_skus_and_overlap_blocks_push(): void
    {
        Queue::fake();
        $a = $this->makeDraft('fixed');
        $this->postAs("/api/v1/channel-promotions/{$a}/skus", [
            'skus' => [['external_product_id' => 'p1', 'external_sku_id' => 's1', 'base_price' => 100000, 'discount_value' => 70000]],
        ])->assertOk();

        // s1 giờ "bận" (thuộc nháp đang chiếm).
        $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channel-promotions/busy-skus?channel_account_id={$this->accountId}")
            ->assertOk()
            ->assertJsonPath('data.external_sku_ids', ['s1']);

        // Chiến dịch khác chứa s1 ⇒ đẩy bị chặn 422.
        $b = $this->makeDraft('fixed');
        $this->postAs("/api/v1/channel-promotions/{$b}/skus", [
            'skus' => [['external_product_id' => 'p1', 'external_sku_id' => 's1', 'base_price' => 100000, 'discount_value' => 60000]],
        ])->assertOk();
        $this->postAs("/api/v1/channel-promotions/{$b}/push")->assertStatus(422);
        Queue::assertNotPushed(PushPromotionJob::class);
    }

    public function test_push_dispatches_job_when_no_conflict(): void
    {
        Queue::fake();
        $id = $this->makeDraft('fixed');
        $this->postAs("/api/v1/channel-promotions/{$id}/skus", [
            'skus' => [['external_product_id' => 'p9', 'external_sku_id' => 's9', 'base_price' => 100000, 'discount_value' => 80000]],
        ])->assertOk();

        $this->postAs("/api/v1/channel-promotions/{$id}/push")->assertOk()->assertJsonPath('data.queued', true);
        Queue::assertPushed(PushPromotionJob::class);
    }

    public function test_busy_skus_returns_numeric_sku_ids_as_strings(): void
    {
        // SKU sàn có id TOÀN SỐ (TikTok/Shopee). PHP ép key mảng chuỗi-số → int ⇒ json ra số; phải strval khi
        // trả, nếu không FE Set<string>.has(external_sku_id) không khớp ⇒ SKU đang giảm KHÔNG bị tô xám.
        $sku = '1730781971519932870';
        ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->accountId,
            'external_product_id' => $sku, 'external_sku_id' => $sku, 'title' => 'Mạch loa phân tần',
            'price' => 99000, 'original_price' => 99000, 'special_price' => 85000, 'currency' => 'VND',
        ]);

        $ids = $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channel-promotions/busy-skus?channel_account_id={$this->accountId}")
            ->assertOk()->json('data.external_sku_ids');

        $this->assertSame([$sku], $ids, 'external_sku_ids phải là CHUỖI (không phải số) để FE khớp Set.');
    }

    public function test_lazada_cannot_create_campaign(): void
    {
        // Lazada giảm giá trực tiếp trên SKU (has_program_object=false) ⇒ không tạo chiến dịch riêng được.
        $lazada = ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'lzd', 'shop_name' => 'Lazada Shop', 'status' => 'active', 'access_token' => 'tok',
        ]);

        $this->postAs('/api/v1/channel-promotions', [
            'channel_account_id' => (int) $lazada->getKey(),
            'title' => 'Sale 6.6', 'discount_type' => 'fixed',
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDays(5)->toIso8601String(),
        ])->assertStatus(422);
    }

    public function test_capabilities_endpoint_returns_lazada_shape(): void
    {
        $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson('/api/v1/channel-promotions/capabilities?provider=lazada')
            ->assertOk()
            ->assertJsonPath('data.has_program_object', false)
            ->assertJsonPath('data.max_items_per_call', 20);
    }

    public function test_two_tabs_filter_by_status(): void
    {
        $draftId = $this->makeDraft('fixed');
        // Giả lập 1 chiến dịch đã live.
        ChannelPromotion::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->accountId, 'provider' => 'lazada',
            'title' => 'Đã chạy', 'discount_type' => 'fixed', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(3),
            'status' => ChannelPromotion::STATUS_LIVE, 'source' => 'app',
        ]);

        $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channel-promotions?channel_account_id={$this->accountId}&tab=draft")
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channel-promotions?channel_account_id={$this->accountId}&tab=pushed")
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_push_job_reaches_live_when_worker_has_no_request_bound_tenant(): void
    {
        // Reproduces prod bug: campaign id=21 (2026-07-23) stuck in draft forever, no error.
        // Queue worker runs with no request-bound CurrentTenant — same as real Horizon workers.
        $id = $this->makeDraft('fixed');
        $this->postAs("/api/v1/channel-promotions/{$id}/skus", [
            'skus' => [['external_product_id' => 'p1', 'external_sku_id' => 's1', 'base_price' => 100000, 'discount_value' => 70000]],
        ])->assertOk();

        $this->conn->shouldReceive('createPromotion')
            ->andReturn(new PromotionResultDTO('ext-promo-1'));
        $this->conn->shouldReceive('putPromotionItems')->andReturnNull();

        app(CurrentTenant::class)->clear();

        (new PushPromotionJob($id))->handle(app(PromotionService::class), app(CurrentTenant::class));

        $promo = ChannelPromotion::withoutGlobalScope(TenantScope::class)->findOrFail($id);
        $this->assertSame(ChannelPromotion::STATUS_LIVE, $promo->status, 'Job phải hoàn tất push, không được kẹt ở draft.');
        $this->assertSame('ext-promo-1', $promo->external_promotion_id);
    }

    public function test_lazada_listing_special_price_marks_sku_busy_with_discount(): void
    {
        // Lazada không có API liệt kê chương trình ⇒ phát hiện qua channel_listings.special_price (đồng bộ từ sàn).
        $lazada = ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'lzd', 'shop_name' => 'Lazada Shop', 'status' => 'active', 'access_token' => 'tok',
        ]);
        $lid = (int) $lazada->getKey();

        ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $lid,
            'external_product_id' => 'P1', 'external_sku_id' => 'SKU-DISC', 'title' => 'Có giảm',
            'price' => 60000, 'original_price' => 119000, 'special_price' => 60000, 'currency' => 'VND',
        ]);
        ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $lid,
            'external_product_id' => 'P2', 'external_sku_id' => 'SKU-FULL', 'title' => 'Giá thường',
            'price' => 100000, 'original_price' => 100000, 'special_price' => null, 'currency' => 'VND',
        ]);

        $svc = app(PromotionService::class);
        $prices = $svc->busyPromoPrices($lid);

        $this->assertSame(60000, $prices['SKU-DISC'] ?? null, 'SKU có special_price ⇒ bận + giá giảm.');
        $this->assertArrayNotHasKey('SKU-FULL', $prices, 'SKU giá thường KHÔNG bận.');
        $this->assertContains('SKU-DISC', $svc->busySkuIds($lid));

        // Endpoint trả prices cho FE tô xám + hiện giá.
        $res = $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channel-promotions/busy-skus?channel_account_id={$lid}")->assertOk();
        $this->assertSame(60000, $res->json('data.prices.SKU-DISC'));
    }
}
