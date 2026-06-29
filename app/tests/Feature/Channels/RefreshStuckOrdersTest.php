<?php

namespace Tests\Feature\Channels;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\Page;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Channels\Jobs\RefreshStuckOrders;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\PrepareBlockReason;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

/**
 * TDD: RefreshStuckOrders job — refetch đơn treo từ sàn, cập nhật status + clear has_issue lỗi thời.
 */
class RefreshStuckOrdersTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Refresh Stuck Test Shop']);
        app(CurrentTenant::class)->set($this->tenant);

        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'shopee',
            'external_shop_id' => 'SP-REFRESH-TEST',
            'shop_name' => 'Shopee Refresh Test',
            'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    /**
     * Seed 1 đơn "treo": status=Processing, has_issue=true (phiếu giao hàng), last_synced_at 3h trước.
     */
    private function seedStuckOrder(string $extId = 'SPX1'): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'shopee',
            'channel_account_id' => $this->account->getKey(),
            'external_order_id' => $extId,
            'order_number' => $extId,
            'status' => StandardOrderStatus::Processing,
            'raw_status' => 'PROCESSED',
            'currency' => 'VND',
            'grand_total' => 100000,
            'item_total' => 100000,
            'placed_at' => now()->subDay(),
            'source_updated_at' => now()->subDays(2),
            'last_synced_at' => now()->subHours(3),   // stuck: last sync > 2h ago
            'has_issue' => true,
            'issue_reason' => 'phiếu giao hàng chưa tải về',
            'tags' => [],
            'packages' => [],
        ]);
    }

    /**
     * Đăng ký fake Shopee connector trả OrderDTO đã tiến status cho externalOrderId cho trước.
     */
    private function registerFakeShopeeReturning(string $extId, string $rawStatus, StandardOrderStatus $standard): void
    {
        $dto = new OrderDTO(
            externalOrderId: $extId,
            source: 'shopee',
            rawStatus: $rawStatus,
            sourceUpdatedAt: CarbonImmutable::now()->subMinutes(5),
            orderNumber: $extId,
            paymentStatus: null,
            placedAt: CarbonImmutable::now()->subDay(),
            paidAt: null,
            shippedAt: null,
            deliveredAt: null,
            completedAt: null,
            cancelledAt: null,
            cancelReason: null,
            buyer: ['name' => 'Buyer'],
            shippingAddress: [],
            currency: 'VND',
            itemTotal: 100000,
            shippingFee: 0,
            platformDiscount: 0,
            sellerDiscount: 0,
            tax: 0,
            codAmount: 0,
            grandTotal: 100000,
            isCod: false,
            fulfillmentType: null,
            items: [],
            packages: [],
            raw: [],
        );

        // Bind fake instance vào container; registry resolves by class-string via container->make().
        $this->app->instance(FakeShopeeRefreshConnector::class, new FakeShopeeRefreshConnector($dto, $standard));
        app(ChannelRegistry::class)->register('shopee', FakeShopeeRefreshConnector::class);
    }

    public function test_refreshes_stuck_order_status_and_clears_label_issue(): void
    {
        $order = $this->seedStuckOrder('SPX1');
        $this->registerFakeShopeeReturning('SPX1', rawStatus: 'SHIPPED', standard: StandardOrderStatus::Shipped);

        (new RefreshStuckOrders((int) $this->account->getKey()))->handle(
            app(ChannelRegistry::class),
            app(OrderUpsertService::class),
            app(CurrentTenant::class),
        );

        $order->refresh();
        $this->assertSame(StandardOrderStatus::Shipped, $order->status, 'đơn treo phải được refresh sang trạng thái mới');
        $this->assertFalse((bool) $order->has_issue, 'issue tem/tracking lỗi thời phải được clear khi đã tiến lên');
    }

    public function test_skips_order_that_is_not_stuck(): void
    {
        // Đơn has_issue=false và last_synced_at chỉ 30 phút trước → KHÔNG thuộc tập "treo"
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'shopee',
            'channel_account_id' => $this->account->getKey(),
            'external_order_id' => 'SPX2',
            'order_number' => 'SPX2',
            'status' => StandardOrderStatus::Processing,
            'raw_status' => 'PROCESSED',
            'currency' => 'VND',
            'grand_total' => 100000,
            'item_total' => 100000,
            'placed_at' => now()->subDay(),
            'source_updated_at' => now()->subMinutes(30),
            'last_synced_at' => now()->subMinutes(30),  // not stuck
            'has_issue' => false,
            'issue_reason' => null,
            'tags' => [],
            'packages' => [],
        ]);

        // Fake connector sẽ KHÔNG được gọi — nếu bị gọi nó sẽ ném exception
        $throwingDto = new OrderDTO(
            externalOrderId: 'SPX2', source: 'shopee', rawStatus: 'PROCESSED',
            sourceUpdatedAt: CarbonImmutable::now(), orderNumber: 'SPX2', paymentStatus: null,
            placedAt: CarbonImmutable::now()->subDay(), paidAt: null, shippedAt: null, deliveredAt: null,
            completedAt: null, cancelledAt: null, cancelReason: null, buyer: [], shippingAddress: [],
            currency: 'VND', itemTotal: 100000, shippingFee: 0, platformDiscount: 0, sellerDiscount: 0,
            tax: 0, codAmount: 0, grandTotal: 100000, isCod: false, fulfillmentType: null,
            items: [], packages: [], raw: [],
        );
        $this->app->instance(FakeShopeeRefreshConnector::class, new FakeShopeeRefreshConnector($throwingDto, StandardOrderStatus::Shipped, throwOnFetch: true));
        app(ChannelRegistry::class)->register('shopee', FakeShopeeRefreshConnector::class);

        (new RefreshStuckOrders((int) $this->account->getKey()))->handle(
            app(ChannelRegistry::class),
            app(OrderUpsertService::class),
            app(CurrentTenant::class),
        );

        // Trạng thái KHÔNG đổi → fetchOrderDetail không được gọi
        $order->refresh();
        $this->assertSame(StandardOrderStatus::Processing, $order->status, 'đơn không treo không được thay đổi trạng thái');
    }
}

/**
 * Fake Shopee connector dùng riêng cho RefreshStuckOrders test.
 * Implements toàn bộ interface; chỉ fetchOrderDetail + mapStatus là có logic thật.
 */
final class FakeShopeeRefreshConnector implements ChannelConnector
{
    public function __construct(
        private readonly OrderDTO $returnDto,
        private readonly StandardOrderStatus $returnStatus,
        private readonly bool $throwOnFetch = false,
    ) {}

    public function code(): string
    {
        return 'shopee';
    }

    public function displayName(): string
    {
        return 'Shopee (Fake Refresh)';
    }

    public function capabilities(): array
    {
        return ['orders.fetch' => true];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO
    {
        if ($this->throwOnFetch) {
            throw new \LogicException("fetchOrderDetail should NOT have been called for non-stuck order [{$externalOrderId}].");
        }

        return $this->returnDto;
    }

    public function mapStatus(string $rawStatus, array $rawOrder = []): StandardOrderStatus
    {
        return $this->returnStatus;
    }

    // --- Stubs for unused interface methods ---

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    public function exchangeCodeForToken(string $code, array $context = []): TokenDTO
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    public function refreshToken(string $refreshToken, array $context = []): TokenDTO
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    public function registerWebhooks(AuthContext $auth): void
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    public function revoke(AuthContext $auth): void
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array{updatedFrom?:CarbonImmutable,updatedTo?:CarbonImmutable,statuses?:list<string>,cursor?:string,pageSize?:int} $query */
    public function fetchOrders(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    public function parseWebhook(Request $request): WebhookEventDTO
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    public function prepareBlockReason(string $rawStatus, array $rawOrder = []): ?PrepareBlockReason
    {
        return null;
    }

    public function unprocessedRawStatuses(): array
    {
        return [];
    }

    /** @param array{cursor?:string,pageSize?:int} $query */
    public function fetchListings(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array{external_product_id?:string|null,warehouse_id?:string|int|null} $context */
    public function updateStock(AuthContext $auth, string $externalSkuId, int $available, array $context = []): void
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array<string,mixed> $params */
    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array{tracking_no?:string,shipment_provider?:string,external_item_ids?:list<int>,delivery_type?:string,packageId?:string} $params */
    public function pushReadyToShip(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array{type?:string,format?:string,externalPackageId?:string,tracking_no?:string,order_item_ids?:list<int>} $query */
    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array{from?:CarbonImmutable,to?:CarbonImmutable,cursor?:string,pageSize?:int} $query */
    public function fetchSettlements(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array<string,mixed> $query */
    public function fetchReturns(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array<string,mixed> $query */
    public function fetchCancellations(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array<string,mixed> $params */
    public function decideReturn(AuthContext $auth, string $externalReturnId, string $action, array $params = []): array
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }

    /** @param array<string,mixed> $params */
    public function decideCancellation(AuthContext $auth, string $externalCancelId, string $action, array $params = []): array
    {
        throw UnsupportedOperation::for('shopee_fake', __METHOD__);
    }
}
