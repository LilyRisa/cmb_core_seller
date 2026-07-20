<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressConnector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JtExpressConnectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('integrations.jt.api_account', 'TEST-ACC');
        Config::set('integrations.jt.private_key', 'TEST-KEY');
    }

    private function account(array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => 1, 'carrier' => 'jt',
            'credentials' => ['customerCode' => '024E000014', 'password' => 'secret'],
            'default_service' => null,
            'meta' => [
                'pay_type' => 'PP_CASH',
                'from_address' => [
                    'name' => 'CMBcore Shop', 'phone' => '0901234567', 'address' => '7/28 Thành Thái',
                    'province_name' => 'Hồ Chí Minh', 'ward_name' => 'Phường 14',
                ],
            ],
        ], $overrides);
    }

    public function test_quote_returns_fee_from_getcomcost(): void
    {
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => ['price' => 100000, 'codFee' => 0, 'insuranceFee' => 5]]);
        });

        $result = (new JtExpressConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Hà Nội', 'ward' => 'Phường Hàng Trống'],
        ]);

        $this->assertCount(1, $result);
        $this->assertSame(100000, $result[0]['fee']);
        $biz = json_decode($captured['bizContent'], true);
        $this->assertSame('Hồ Chí Minh', $biz['sender']['prov']);
        $this->assertSame('Hà Nội', $biz['receiver']['prov']);
        $this->assertSame(1, $biz['selfAddress']);
        $this->assertArrayNotHasKey('city', $biz['sender']);
    }

    public function test_quote_returns_empty_when_recipient_address_missing(): void
    {
        $this->assertSame([], (new JtExpressConnector)->quote($this->account(), ['recipient' => []]));
    }

    public function test_quote_returns_empty_when_from_address_missing(): void
    {
        $account = $this->account();
        $account['meta']['from_address'] = [];

        $this->assertSame([], (new JtExpressConnector)->quote($account, ['recipient' => ['province' => 'Hà Nội', 'ward' => 'X']]));
    }

    public function test_create_shipment_posts_addorder_and_returns_tracking(): void
    {
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => [
                'txlogisticId' => 'ORD1', 'billCode' => '802400616352', 'sortLine' => '800-028A04-',
                'inquiryFee' => 15, 'codFee' => 0, 'insuranceFee' => 20000,
            ]]);
        });

        $result = (new JtExpressConnector)->createShipment($this->account(), [
            'client_order_code' => 'ORD1',
            'recipient' => ['name' => 'Trần B', 'phone' => '0912345000', 'address' => '475A Điện Biên Phủ', 'province' => 'Hồ Chí Minh', 'ward' => 'Phường 25'],
            'parcel' => ['weight_grams' => 800],
            'cod_amount' => 150000,
            'items' => [['name' => 'Áo M', 'quantity' => 2, 'price' => 150000]],
        ]);

        $this->assertSame('802400616352', $result['tracking_no']);
        $this->assertSame('jt', $result['carrier']);
        $biz = json_decode($captured['bizContent'], true);
        $this->assertSame('ORD1', $biz['txlogisticId']);
        $this->assertSame(1, $biz['selfAddress']);
        $this->assertArrayNotHasKey('city', $biz['receiver']);
        $this->assertSame('Phường 25', $biz['receiver']['area']);
        $this->assertSame('Hồ Chí Minh', $biz['sender']['prov']);
        $this->assertSame(150000, $biz['codMoney']);
        $this->assertSame('PP_CASH', $biz['payType']);
        $this->assertSame('EXPRESS', $biz['productType']);
    }

    public function test_create_shipment_uses_pay_type_from_account_meta(): void
    {
        Http::fake(['*' => Http::response(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'B1']])]);
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'B1']]);
        });

        (new JtExpressConnector)->createShipment($this->account(['meta' => ['pay_type' => 'PP_PM']]), [
            'client_order_code' => 'ORD2',
            'recipient' => ['name' => 'X', 'phone' => '090', 'address' => 'test', 'province' => 'Hồ Chí Minh', 'ward' => 'Phường 1'],
        ]);

        $biz = json_decode($captured['bizContent'], true);
        $this->assertSame('PP_PM', $biz['payType']);
    }

    public function test_create_shipment_hashes_merchant_password_before_sending(): void
    {
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'B1']]);
        });

        // customerCode/password = ví dụ CHÍNH THỨC từ open.jtexpress.vn/helpCenter → Authentication Tools.
        (new JtExpressConnector)->createShipment(
            $this->account(['credentials' => ['customerCode' => '084LC02438', 'password' => 'KGC6jju1']]),
            [
                'client_order_code' => 'ORD9',
                'recipient' => ['name' => 'X', 'phone' => '090', 'address' => 'test', 'province' => 'Hồ Chí Minh', 'ward' => 'Phường 1'],
            ],
        );

        $biz = json_decode($captured['bizContent'], true);
        $this->assertSame('084LC02438', $biz['customerCode']);
        // Không bao giờ gửi password thô (J&T không nhận plaintext) — phải là bản đã hash.
        $this->assertSame('4AE2DBF6527EA7C49C59EFF24F6FEA71', $biz['password']);
        $this->assertNotSame('KGC6jju1', $biz['password']);
    }

    public function test_create_shipment_always_sends_codmoney_and_nonzero_goodsvalue(): void
    {
        // J&T từ chối thật ("codMoney is required" / "min goodsValue is 0.01") khi thiếu codMoney hoặc
        // goodsValue=0 — verify UAT 2026-07-20. Đơn không COD + chưa nhập giá SP vẫn phải gửi đủ 2 field.
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'B1']]);
        });

        (new JtExpressConnector)->createShipment($this->account(), [
            'client_order_code' => 'ORD6',
            'recipient' => ['name' => 'X', 'phone' => '090', 'address' => 'test', 'province' => 'Hồ Chí Minh', 'ward' => 'Phường 1'],
        ]);

        $biz = json_decode($captured['bizContent'], true);
        $this->assertArrayHasKey('codMoney', $biz);
        $this->assertSame(0, $biz['codMoney']);
        $this->assertGreaterThan(0, $biz['goodsValue']);
    }

    public function test_quote_sends_codmoney_and_nonzero_goodsvalue(): void
    {
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => ['price' => 100000]]);
        });

        (new JtExpressConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Hà Nội', 'ward' => 'Phường Hàng Trống'],
        ]);

        $biz = json_decode($captured['bizContent'], true);
        $this->assertArrayHasKey('codMoney', $biz);
        $this->assertGreaterThan(0, $biz['goodsValue']);
    }

    public function test_create_shipment_throws_clear_error_when_recipient_missing_ward(): void
    {
        $this->expectExceptionMessage('Tỉnh/Phường');

        (new JtExpressConnector)->createShipment($this->account(), [
            'client_order_code' => 'ORD3',
            'recipient' => ['name' => 'X', 'phone' => '090', 'address' => 'test', 'province' => 'Hồ Chí Minh'],
        ]);
    }

    public function test_create_shipment_throws_clear_error_when_from_address_missing(): void
    {
        $account = $this->account();
        $account['meta']['from_address'] = [];

        $this->expectExceptionMessage('kho gửi');

        (new JtExpressConnector)->createShipment($account, [
            'client_order_code' => 'ORD4',
            'recipient' => ['name' => 'X', 'phone' => '090', 'address' => 'test', 'province' => 'Hồ Chí Minh', 'ward' => 'Phường 1'],
        ]);
    }

    public function test_create_shipment_throws_clear_error_when_sender_missing_province(): void
    {
        $account = $this->account();
        $account['meta']['from_address']['province_name'] = '';

        $this->expectExceptionMessage('Tỉnh/Phường kho gửi');

        (new JtExpressConnector)->createShipment($account, [
            'client_order_code' => 'ORD5',
            'recipient' => ['name' => 'X', 'phone' => '090', 'address' => 'test', 'province' => 'Hồ Chí Minh', 'ward' => 'Phường 1'],
        ]);
    }
}
