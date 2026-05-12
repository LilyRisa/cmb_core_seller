<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

/**
 * Contract test for TikTokConnector: feed it sample Partner API (v202309)
 * responses (Http::fake, no real network) and assert it returns the standard
 * DTOs. See docs/04-channels/README.md §1 rule 5, docs/09-process/testing-strategy.md §2.
 */
class TikTokConnectorContractTest extends TestCase
{
    private function connector()
    {
        F::configure();

        return app(ChannelRegistry::class)->for('tiktok');
    }

    private function auth(): AuthContext
    {
        return new AuthContext(channelAccountId: 1, provider: 'tiktok', externalShopId: F::SHOP_ID, accessToken: 'tk', extra: ['shop_cipher' => F::SHOP_CIPHER]);
    }

    public function test_exchange_code_returns_token_dto(): void
    {
        Http::fake(['*/api/v2/token/get*' => Http::response(F::tokenGet())]);

        $token = $this->connector()->exchangeCodeForToken('auth_code_xyz');

        $this->assertSame('tk_access_123', $token->accessToken);
        $this->assertSame('tk_refresh_123', $token->refreshToken);
        $this->assertNotNull($token->expiresAt);
        $this->assertTrue($token->expiresAt->isFuture());
        $this->assertSame('open_id_abc', $token->raw['open_id']);
        // granted_scopes (a list from TikTok) is joined into the standard DTO's ?string scope
        $this->assertSame('seller.shop,seller.order,seller.product', $token->scope);
    }

    public function test_fetch_shop_info_returns_shop_dto_with_cipher(): void
    {
        Http::fake(['*/authorization/202309/shops*' => Http::response(F::authShops())]);

        $shop = $this->connector()->fetchShopInfo(new AuthContext(channelAccountId: 0, provider: 'tiktok', externalShopId: '', accessToken: 'tk'));

        $this->assertSame(F::SHOP_ID, $shop->externalShopId);
        $this->assertSame('Cửa hàng test', $shop->name);
        $this->assertSame('VN', $shop->region);
        $this->assertSame(F::SHOP_CIPHER, $shop->raw['cipher']);
    }

    public function test_fetch_order_detail_maps_to_order_dto(): void
    {
        Http::fake(['*/order/202309/orders?*' => Http::response(F::orderDetail())]);

        $dto = $this->connector()->fetchOrderDetail($this->auth(), F::ORDER_ID);

        $this->assertSame(F::ORDER_ID, $dto->externalOrderId);
        $this->assertSame('tiktok', $dto->source);
        $this->assertSame('AWAITING_SHIPMENT', $dto->rawStatus);
        $this->assertSame('Nguyễn Văn A', $dto->buyer['name']);
        $this->assertSame('Hồ Chí Minh', $dto->shippingAddress['province']);
        $this->assertSame('Quận 1', $dto->shippingAddress['district']);
        $this->assertSame('Phường Bến Nghé', $dto->shippingAddress['ward']);
        // money parsed to integer VND đồng
        $this->assertSame(200000, $dto->itemTotal);
        $this->assertSame(20000, $dto->shippingFee);
        $this->assertSame(205000, $dto->grandTotal);
        $this->assertSame(10000, $dto->platformDiscount);   // payment.platform_discount + payment.shipping_fee_platform_discount
        $this->assertSame(5000, $dto->sellerDiscount);      // payment.seller_discount + payment.shipping_fee_seller_discount
        $this->assertTrue($dto->isCod);
        $this->assertSame(205000, $dto->codAmount);
        $this->assertSame('FULFILLMENT_BY_SELLER', $dto->fulfillmentType);
        // 2 line_item rows of the same SKU -> one item, quantity 2
        $this->assertCount(1, $dto->items);
        $this->assertSame('AT-RED-M', $dto->items[0]->sellerSku);
        $this->assertSame(2, $dto->items[0]->quantity);
        $this->assertSame(100000, $dto->items[0]->unitPrice);
        // packages carried through
        $this->assertSame('1153000000000000001', $dto->packages[0]['externalPackageId']);
    }

    public function test_fetch_orders_pages(): void
    {
        Http::fake([
            '*/order/202309/orders/search*' => Http::sequence()
                ->push(F::ordersSearch([F::order('A', 'AWAITING_SHIPMENT')], nextToken: 'PAGE2'))
                ->push(F::ordersSearch([F::order('B', 'IN_TRANSIT')], nextToken: null)),
        ]);

        $page1 = $this->connector()->fetchOrders($this->auth(), ['updatedFrom' => now()->subDay()]);
        $this->assertCount(1, $page1->items);
        $this->assertSame('A', $page1->items[0]->externalOrderId);
        $this->assertTrue($page1->hasMore);
        $this->assertSame('PAGE2', $page1->nextCursor);

        $page2 = $this->connector()->fetchOrders($this->auth(), ['cursor' => 'PAGE2']);
        $this->assertSame('B', $page2->items[0]->externalOrderId);
        $this->assertFalse($page2->hasMore);
    }

    public function test_status_mapping(): void
    {
        $c = $this->connector();
        $this->assertSame(StandardOrderStatus::Unpaid, $c->mapStatus('UNPAID'));
        // SPEC 0013: AWAITING_SHIPMENT = chưa in/arrange phiếu ⇒ pending (kể cả khi đã có package).
        // AWAITING_COLLECTION = đã in/arrange phiếu (TikTok "đang chờ lấy hàng") ⇒ processing.
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('AWAITING_SHIPMENT'));
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('AWAITING_SHIPMENT', ['packages' => [['id' => '1']]]));
        $this->assertSame(StandardOrderStatus::Processing, $c->mapStatus('AWAITING_COLLECTION'));
        $this->assertSame(StandardOrderStatus::Shipped, $c->mapStatus('IN_TRANSIT'));
        $this->assertSame(StandardOrderStatus::Delivered, $c->mapStatus('DELIVERED'));
        $this->assertSame(StandardOrderStatus::Completed, $c->mapStatus('COMPLETED'));
        $this->assertSame(StandardOrderStatus::Cancelled, $c->mapStatus('CANCELLED'));
        // unknown raw status -> conservative fallback, never throws
        $this->assertSame(StandardOrderStatus::Cancelled, $c->mapStatus('SOMETHING_CANCELLED_WEIRD'));
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('TOTALLY_NEW_STATUS'));
    }

    public function test_webhook_signature_valid_and_invalid(): void
    {
        $c = $this->connector();
        $wh = F::webhookOrderStatusChange();

        $valid = Request::create('/webhook/tiktok', 'POST', server: ['HTTP_AUTHORIZATION' => $wh['signature']], content: $wh['raw']);
        $this->assertTrue($c->verifyWebhookSignature($valid));

        $tampered = Request::create('/webhook/tiktok', 'POST', server: ['HTTP_AUTHORIZATION' => 'deadbeef'], content: $wh['raw']);
        $this->assertFalse($c->verifyWebhookSignature($tampered));

        $noSig = Request::create('/webhook/tiktok', 'POST', content: $wh['raw']);
        $this->assertFalse($c->verifyWebhookSignature($noSig));
    }

    public function test_parse_webhook_to_event_dto(): void
    {
        $wh = F::webhookOrderStatusChange(F::ORDER_ID, type: 1);
        $request = Request::create('/webhook/tiktok', 'POST', server: ['HTTP_AUTHORIZATION' => $wh['signature']], content: $wh['raw']);

        $event = $this->connector()->parseWebhook($request);

        $this->assertSame('tiktok', $event->provider);
        $this->assertSame(WebhookEventDTO::TYPE_ORDER_STATUS_UPDATE, $event->type);
        $this->assertSame(F::SHOP_ID, $event->externalShopId);
        $this->assertSame(F::ORDER_ID, $event->externalOrderId);
    }

    public function test_unsupported_phase2plus_operations_throw(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->connector()->updateStock($this->auth(), 'sku-1', 5);
    }
}
