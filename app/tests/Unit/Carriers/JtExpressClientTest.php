<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class JtExpressClientTest extends TestCase
{
    private function client(): JtExpressClient
    {
        return new JtExpressClient('669375073659916329', '6e93e0d4344e47f0a4af7e4e75af955e', 'https://demoopenapi.jtexpress.vn/webopenplatformapi');
    }

    public function test_add_order_posts_form_fields_and_returns_data(): void
    {
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => [
                'txlogisticId' => '123456789101', 'billCode' => '802400616352', 'sortLine' => '800-028A04-',
                'inquiryFee' => 15, 'codFee' => 0, 'insuranceFee' => 0,
            ]]);
        });

        $data = $this->client()->addOrder(['customerCode' => '024E000014', 'txlogisticId' => '123456789101']);

        $this->assertSame('802400616352', $data['billCode']);
        $this->assertSame('669375073659916329', $captured['apiAccount']);
        $this->assertArrayHasKey('digest', $captured);
        $this->assertArrayHasKey('timestamp', $captured);
        $biz = json_decode($captured['bizContent'], true);
        $this->assertSame('024E000014', $biz['customerCode']);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/api/order/addOrder'));
    }

    public function test_cancel_order_posts_correct_path(): void
    {
        Http::fake(['*/api/order/cancelOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => ['txlogisticId' => 'X', 'billCode' => 'X']])]);

        $data = $this->client()->cancelOrder(['txlogisticId' => 'X', 'reason' => 'test']);

        $this->assertSame('X', $data['billCode']);
    }

    public function test_get_com_cost_posts_correct_path(): void
    {
        Http::fake(['*/api/spmComCost/getComCost' => Http::response(['code' => '1', 'msg' => 'success', 'data' => ['price' => 100000, 'codFee' => 0, 'insuranceFee' => 5]])]);

        $data = $this->client()->getComCost(['weight' => 10]);

        $this->assertSame(100000, $data['price']);
    }

    public function test_print_order_posts_correct_path(): void
    {
        Http::fake(['*/api/order/printOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => [
            'txlogisticId' => 'X', 'billCode' => 'B1', 'base64EncodeContent' => base64_encode('%PDF-FAKE'),
        ]])]);

        $data = $this->client()->printOrder(['txlogisticId' => 'X']);

        $this->assertSame('B1', $data['billCode']);
    }

    public function test_trace_posts_correct_path_and_returns_list(): void
    {
        Http::fake(['*/api/logistics/trace' => Http::response(['code' => '1', 'msg' => 'success', 'data' => [
            ['billCode' => 'B1', 'details' => [['scanTime' => '2024-06-05 15:57:04', 'scanTypeCode' => 113]]],
        ]])]);

        $data = $this->client()->trace(['billcodes' => 'B1']);

        $this->assertSame('B1', $data[0]['billCode']);
    }

    public function test_throws_runtime_exception_with_jt_message_on_error_code(): void
    {
        Http::fake(['*/api/order/addOrder' => Http::response(['code' => '999001030', 'msg' => 'customerCode or password is wrong'])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('customerCode or password is wrong');

        $this->client()->addOrder(['customerCode' => 'bad']);
    }
}
