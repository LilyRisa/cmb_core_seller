<?php

declare(strict_types=1);

namespace Tests\Feature\Integrations;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ListingAttributeDTO;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaPublisher;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeePublisher;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokPublisher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * getCategoryAttributes của 3 sàn PHẢI chuẩn hoá về tập input_type chuẩn
 * (text/number/select/multi_select) + values [{id,name}] + cờ sale_prop/required đúng,
 * để FE AttributeForm render được. Trước đây trả kiểu thô của sàn ⇒ field rỗng.
 */
class AttributeNormalizationTest extends TestCase
{
    private function auth(string $provider): AuthContext
    {
        return new AuthContext(channelAccountId: 1, provider: $provider, externalShopId: '123', accessToken: 'tok');
    }

    /** @param ListingAttributeDTO[] $list */
    private function byId(array $list, string $id): ListingAttributeDTO
    {
        foreach ($list as $a) {
            if ($a->id === $id) {
                return $a;
            }
        }
        $this->fail("attribute $id not found");
    }

    public function test_tiktok_normalizes_attributes(): void
    {
        config(['integrations.tiktok.app_key' => 'k', 'integrations.tiktok.app_secret' => 's', 'integrations.tiktok.base_url' => 'https://open-api.tiktokglobalshop.com', 'integrations.throttle.tiktok' => 0]);
        Http::fake(['*categories*attributes*' => Http::response(['code' => 0, 'data' => ['attributes' => [
            ['id' => '100', 'name' => 'Chất liệu', 'is_requried' => true, 'type' => 'PRODUCT_PROPERTY', 'is_multiple_selection' => false, 'values' => [['id' => '1', 'name' => 'Cotton']]],
            ['id' => '200', 'name' => 'Màu', 'type' => 'SALES_PROPERTY', 'is_multiple_selection' => true, 'values' => [['id' => '9', 'name' => 'Đỏ']]],
            ['id' => '300', 'name' => 'Trọng lượng', 'type' => 'PRODUCT_PROPERTY', 'value_data_format' => 'POSITIVE_INT_OR_DECIMAL', 'values' => []],
            ['id' => '400', 'name' => 'Ghi chú', 'type' => 'PRODUCT_PROPERTY', 'values' => []],
        ]]])]);

        $list = app(TikTokPublisher::class)->getCategoryAttributes($this->auth('tiktok'), '999');

        $mat = $this->byId($list, '100');
        $this->assertSame(ListingAttributeDTO::INPUT_SELECT, $mat->inputType);
        $this->assertFalse($mat->isSaleProp);
        $this->assertTrue($mat->required);
        $this->assertSame(['id' => '1', 'name' => 'Cotton'], $mat->values[0]);

        $this->assertSame(ListingAttributeDTO::INPUT_MULTI_SELECT, $this->byId($list, '200')->inputType);
        $this->assertTrue($this->byId($list, '200')->isSaleProp);
        $this->assertSame(ListingAttributeDTO::INPUT_NUMBER, $this->byId($list, '300')->inputType);
        $this->assertSame(ListingAttributeDTO::INPUT_TEXT, $this->byId($list, '400')->inputType);
    }

    public function test_lazada_normalizes_attributes_and_fixes_string_flags(): void
    {
        config(['integrations.lazada.app_key' => 'k', 'integrations.lazada.app_secret' => 's', 'integrations.lazada.api_base_url' => 'https://api.lazada.vn/rest']);
        Http::fake(['*category/attributes/get*' => Http::response(['code' => '0', 'data' => [
            ['name' => 'size', 'label' => 'Kích cỡ', 'is_mandatory' => '1', 'is_sale_prop' => '1', 'input_type' => 'singleSelect', 'options' => [['id' => 10, 'name' => 'M'], ['id' => 11, 'name' => 'L']]],
            ['name' => 'desc', 'label' => 'Mô tả', 'is_mandatory' => '0', 'is_sale_prop' => '0', 'input_type' => 'text', 'options' => []],
            ['name' => 'weight', 'is_mandatory' => '0', 'input_type' => 'numeric'],
            ['name' => 'tags', 'input_type' => 'multiSelect', 'options' => [['id' => 1, 'name' => 'A']]],
        ]])]);

        $list = app(LazadaPublisher::class)->getCategoryAttributes($this->auth('lazada'), '999');

        $size = $this->byId($list, 'size');
        $this->assertSame('Kích cỡ', $size->name);
        $this->assertSame(ListingAttributeDTO::INPUT_SELECT, $size->inputType);
        $this->assertTrue($size->required);
        $this->assertTrue($size->isSaleProp);
        $this->assertSame(['id' => '10', 'name' => 'M'], $size->values[0]);

        // BUG cũ: (bool)"0" === true ⇒ desc bị coi là bắt buộc. Phải FALSE.
        $desc = $this->byId($list, 'desc');
        $this->assertFalse($desc->required);
        $this->assertFalse($desc->isSaleProp);
        $this->assertSame(ListingAttributeDTO::INPUT_TEXT, $desc->inputType);

        $this->assertSame(ListingAttributeDTO::INPUT_NUMBER, $this->byId($list, 'weight')->inputType);
        $this->assertSame(ListingAttributeDTO::INPUT_MULTI_SELECT, $this->byId($list, 'tags')->inputType);
    }

    public function test_shopee_normalizes_numeric_input_type_and_values(): void
    {
        config(['integrations.shopee.partner_id' => 1, 'integrations.shopee.partner_key' => 'k', 'integrations.shopee.base_url' => 'https://partner.shopeemobile.com']);
        Http::fake(['*get_attribute_tree*' => Http::response(['response' => ['list' => [['attribute_tree' => [
            ['attribute_id' => 100643, 'original_attribute_name' => 'Material', 'display_attribute_name' => 'Chất liệu', 'mandatory' => true, 'input_type' => 1, 'input_validation_type' => 2, 'attribute_value_list' => [['value_id' => 3300, 'original_value_name' => 'Cotton']]],
            ['attribute_id' => 200, 'mandatory' => false, 'input_type' => 3, 'input_validation_type' => 1],
            ['attribute_id' => 201, 'mandatory' => false, 'input_type' => 3, 'input_validation_type' => 2],
            ['attribute_id' => 202, 'mandatory' => false, 'input_type' => 4, 'attribute_value_list' => [['value_id' => 1, 'original_value_name' => 'X']]],
        ]]]]])]);

        $list = app(ShopeePublisher::class)->getCategoryAttributes($this->auth('shopee'), '999');

        $mat = $this->byId($list, '100643');
        $this->assertSame('Chất liệu', $mat->name);
        $this->assertSame(ListingAttributeDTO::INPUT_SELECT, $mat->inputType);
        $this->assertTrue($mat->required);
        $this->assertSame(['id' => '3300', 'name' => 'Cotton'], $mat->values[0]);

        $this->assertSame(ListingAttributeDTO::INPUT_NUMBER, $this->byId($list, '200')->inputType);
        $this->assertSame(ListingAttributeDTO::INPUT_TEXT, $this->byId($list, '201')->inputType);
        $this->assertSame(ListingAttributeDTO::INPUT_MULTI_SELECT, $this->byId($list, '202')->inputType);
    }
}
