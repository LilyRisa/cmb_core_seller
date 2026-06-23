<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations\TikTok;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokProductPayload;
use PHPUnit\Framework\TestCase;

class TikTokPayloadTest extends TestCase
{
    public function test_builds_create_payload(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton nam form rộng',
            description: 'd',
            categoryId: '600001',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('uri-1', 'uri')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 199000,
                'stock' => 5,
                'sale_props' => ['size' => 'M'],
                'warehouse_id' => 'WH1',
            ]],
            logistics: ['package_weight' => 0.5],
        );

        $body = TikTokProductPayload::toBody($draft, 'LISTING');

        $this->assertSame('600001', $body['category_id']);
        $this->assertSame('v2', $body['category_version']);
        $this->assertSame('LISTING', $body['save_mode']);
        $this->assertSame('uri-1', $body['main_images'][0]['uri']);
        $this->assertTrue(is_string($body['skus'][0]['price']['amount']));
        $this->assertSame('199000', $body['skus'][0]['price']['amount']);
        $this->assertSame('WH1', $body['skus'][0]['inventory'][0]['warehouse_id']);
        // Khóa biến thể CHỮ ("size") là thuộc tính tùy biến ⇒ gửi `name` (KHÔNG `id` —
        // id phải Int64) + `value_name`.
        $this->assertSame('size', $body['skus'][0]['sales_attributes'][0]['name']);
        $this->assertSame('M', $body['skus'][0]['sales_attributes'][0]['value_name']);
        $this->assertArrayNotHasKey('id', $body['skus'][0]['sales_attributes'][0]);
        $this->assertArrayNotHasKey('brand_id', $body);
        // Không có GTIN / idempotencyKey ⇒ KHÔNG gửi (giữ payload gọn).
        $this->assertArrayNotHasKey('identifier_code', $body['skus'][0]);
        $this->assertArrayNotHasKey('idempotency_key', $body);
    }

    public function test_numeric_sale_prop_key_is_sent_as_builtin_id(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton nam form rộng',
            description: 'd',
            categoryId: '600001',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('uri-1', 'uri')],
            skus: [[
                'seller_sku' => 'S1', 'price' => 199000, 'stock' => 5, 'warehouse_id' => 'WH1',
                'sale_props' => ['100089' => 'Red'], // khóa SỐ = thuộc tính dựng sẵn
            ]],
            logistics: ['package_weight' => 0.5],
        );

        $sa = TikTokProductPayload::toBody($draft)['skus'][0]['sales_attributes'][0];

        $this->assertSame('100089', $sa['id']);
        $this->assertSame('Red', $sa['value_name']);
        $this->assertArrayNotHasKey('name', $sa);
    }

    public function test_product_attributes_are_passed_through_to_body(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton nam form rộng',
            description: 'd',
            categoryId: '600001',
            brandId: null,
            // Khóa "product_attributes" lồng KHÔNG còn được dùng — payload nhận mảng đã dựng sẵn.
            attributes: ['100107' => '1000055'],
            media: [new MediaRefDTO('uri-1', 'uri')],
            skus: [[
                'seller_sku' => 'S1', 'price' => 199000, 'stock' => 5, 'warehouse_id' => 'WH1', 'sale_props' => [],
            ]],
            logistics: ['package_weight' => 0.5],
        );

        $pa = [['id' => '100107', 'values' => [['id' => '1000055']]]];
        $body = TikTokProductPayload::toBody($draft, 'LISTING', null, $pa);

        $this->assertSame($pa, $body['product_attributes']);
    }

    public function test_sends_identifier_code_and_idempotency_key_when_present(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton nam form rộng',
            description: 'd',
            categoryId: '600001',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('uri-1', 'uri')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 199000,
                'stock' => 5,
                'sale_props' => ['size' => 'M'],
                'warehouse_id' => 'WH1',
                'gtin' => '8938505970121',
                'gtin_type' => 'EAN',
            ]],
            logistics: ['package_weight' => 0.5],
            idempotencyKey: 'listing-draft-42',
        );

        $body = TikTokProductPayload::toBody($draft, 'LISTING');

        $this->assertSame('8938505970121', $body['skus'][0]['identifier_code']['code']);
        $this->assertSame('EAN', $body['skus'][0]['identifier_code']['type']);
        $this->assertSame('listing-draft-42', $body['idempotency_key']);
    }
}
