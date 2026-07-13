<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GhtkConnectorQuoteTest extends TestCase
{
    private function account(): array
    {
        return [
            'id' => 1, 'carrier' => 'ghtk',
            'credentials' => ['token' => 'GHTK-TOKEN', 'client_source' => 'S1'],
            'default_service' => null,
            'meta' => [
                'from_address' => ['province_name' => 'Hà Nội', 'district_name' => 'Quận Hai Bà Trưng'],
                'defaults' => ['package' => ['weight_grams' => 800]],
            ],
        ];
    }

    public function test_quote_uses_account_default_package_weight_not_request(): void
    {
        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            $captured = $request->data();

            return Http::response(['success' => true, 'fee' => ['name' => 'area1', 'fee' => 26400, 'insurance_fee' => 0]]);
        });

        $result = (new GhtkConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy'],
        ]);

        $this->assertSame(800, $captured['weight']);
        $this->assertCount(1, $result);
        $this->assertSame(26400, $result[0]['fee']);
        $this->assertSame('area1', $result[0]['name']);
    }
}
