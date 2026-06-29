<?php

namespace Tests\Feature\Integrations\Shopee;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\Exceptions\ShippingDocumentUnavailable;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures as ShopeeFx;
use Tests\TestCase;

/**
 * Xác minh ShopeeConnector phân biệt đúng 2 trạng thái đơn kẹt tem:
 *  - Advance Fulfilment (error_booking_order)  → ShippingDocumentUnavailable terminal=true
 *  - COD chờ duyệt (package_can_not_print / get_shipping_parameter "ready to be shipped") → transient=false
 *
 * HTTP-faked, không DB.
 */
class ShopeeDocumentStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ShopeeFx::configure();
    }

    private function auth(): AuthContext
    {
        return new AuthContext(
            channelAccountId: 1,
            provider: 'shopee',
            externalShopId: '123',
            accessToken: 'tok',
        );
    }

    /**
     * Giả response `common.batch_api_all_failed` với fail_error cụ thể
     * (shape thật của Shopee create_shipping_document khi fail).
     *
     * @return array<string,mixed>
     */
    private function batchFail(string $failError, string $failMessage = ''): array
    {
        return [
            'error' => 'common.batch_api_all_failed',
            'message' => 'Batch operation failed',
            'response' => [
                'result_list' => [[
                    'order_sn' => 'SN_1',
                    'fail_error' => $failError,
                    'fail_message' => $failMessage,
                ]],
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // getShippingDocument
    // -----------------------------------------------------------------------

    public function test_get_shipping_document_advance_fulfilment_throws_terminal(): void
    {
        Http::fake([
            '*get_shipping_document_parameter*' => Http::response(ShopeeFx::documentParameter()),
            '*create_shipping_document*' => Http::response(
                $this->batchFail('logistics.error_booking_order', 'advance fulfilment order cannot print')
            ),
        ]);

        $caught = null;
        try {
            app(ShopeeConnector::class)->getShippingDocument(
                $this->auth(),
                'SN_1',
                ['tracking_no' => 'TRK123'],
            );
        } catch (ShippingDocumentUnavailable $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'ShippingDocumentUnavailable phải được ném cho đơn Advance Fulfilment');
        $this->assertTrue($caught->terminal, 'Advance Fulfilment phải là terminal=true (dừng retry)');
        $this->assertSame('shopee_advance_fulfilment', $caught->reasonCode);
    }

    public function test_get_shipping_document_cod_not_ready_throws_transient(): void
    {
        Http::fake([
            '*get_shipping_document_parameter*' => Http::response(ShopeeFx::documentParameter()),
            '*create_shipping_document*' => Http::response(
                $this->batchFail('logistics.package_can_not_print', 'COD order screening in progress')
            ),
        ]);

        $caught = null;
        try {
            app(ShopeeConnector::class)->getShippingDocument(
                $this->auth(),
                'SN_1',
                ['tracking_no' => 'TRK123'],
            );
        } catch (ShippingDocumentUnavailable $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'ShippingDocumentUnavailable phải được ném cho đơn COD chờ duyệt');
        $this->assertFalse($caught->terminal, 'COD screening phải là terminal=false (cho phép retry)');
        $this->assertSame('shopee_cod_screening', $caught->reasonCode);
    }

    // -----------------------------------------------------------------------
    // arrangeShipment — get_shipping_parameter "ready to be shipped"
    // -----------------------------------------------------------------------

    public function test_arrange_shipment_cod_not_ready_shipping_parameter_throws_transient(): void
    {
        Http::fake([
            '*get_order_detail*' => Http::response([
                'error' => '',
                'response' => ['order_list' => [ShopeeFx::orderRow('SN_1', 'READY_TO_SHIP')]],
            ]),
            '*get_shipping_parameter*' => Http::response([
                'error' => 'error_param',
                'message' => 'This order can only be shipped when the package is ready to be shipped.',
                'response' => [],
            ]),
        ]);

        $caught = null;
        try {
            app(ShopeeConnector::class)->arrangeShipment($this->auth(), 'SN_1');
        } catch (ShippingDocumentUnavailable $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'ShippingDocumentUnavailable phải được ném khi Shopee báo COD chưa sẵn sàng ở bước ship');
        $this->assertFalse($caught->terminal, 'COD screening phải là terminal=false');
        $this->assertSame('shopee_cod_screening', $caught->reasonCode);
    }
}
