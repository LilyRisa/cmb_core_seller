<?php

namespace Tests\Fixtures\Channels\tiktok;

/**
 * Sample TikTok Shop Partner API (v202309) responses + webhook bodies for
 * contract/feature tests — modelled after the SDK schemas in sdk_tiktok_seller/,
 * with PII stubbed. No real network calls; used with Http::fake().
 * See docs/09-process/testing-strategy.md §2.
 */
final class TikTokFixtures
{
    public const APP_KEY = 'test_app_key';

    public const APP_SECRET = 'test_app_secret';

    public const SHOP_ID = '7000000000000000001';

    public const SHOP_CIPHER = 'TTP_TestShopCipher==';

    public const ORDER_ID = '576123456789012345';

    /** Envelope: { code, message, data, request_id } */
    public static function envelope(array $data): array
    {
        return ['code' => 0, 'message' => 'Success', 'data' => $data, 'request_id' => 'req-'.uniqid()];
    }

    public static function tokenGet(): array
    {
        return self::envelope([
            'access_token' => 'tk_access_123',
            'access_token_expire_in' => now()->addDays(7)->timestamp,
            'refresh_token' => 'tk_refresh_123',
            'refresh_token_expire_in' => now()->addDays(90)->timestamp,
            'open_id' => 'open_id_abc',
            'seller_name' => 'Cửa hàng test',
            'seller_base_region' => 'VN',
            'user_type' => 0,
            'granted_scopes' => 'order.info,product.info',
        ]);
    }

    public static function tokenRefresh(): array
    {
        return self::envelope([
            'access_token' => 'tk_access_NEW',
            'access_token_expire_in' => now()->addDays(7)->timestamp,
            'refresh_token' => 'tk_refresh_NEW',
            'refresh_token_expire_in' => now()->addDays(90)->timestamp,
            'open_id' => 'open_id_abc',
        ]);
    }

    public static function authShops(): array
    {
        return self::envelope([
            'shops' => [[
                'id' => self::SHOP_ID,
                'name' => 'Cửa hàng test',
                'region' => 'VN',
                'seller_type' => 'CROSS_BORDER',
                'cipher' => self::SHOP_CIPHER,
                'code' => 'VNTEST01',
            ]],
        ]);
    }

    /** One order in the v202309 shape (snake_case). $updateTime = Unix ts of last update (default: 10 min ago). */
    public static function order(string $id = self::ORDER_ID, string $status = 'AWAITING_SHIPMENT', ?int $updateTime = null): array
    {
        return [
            'id' => $id,
            'status' => $status,
            'create_time' => now()->subHours(3)->timestamp,
            'update_time' => $updateTime ?? now()->subMinutes(10)->timestamp,
            'paid_time' => now()->subHours(2)->timestamp,
            'payment_method_name' => 'COD',
            'fulfillment_type' => 'FULFILLMENT_BY_SELLER',
            'buyer_email' => 'b***@example.com',
            'buyer_message' => 'Giao giờ hành chính',
            'cancel_reason' => null,
            'recipient_address' => [
                'name' => 'Nguyễn Văn A',
                'phone_number' => '(+84)987654321',
                'address_detail' => 'Số 1, Đường ABC',
                'full_address' => 'Số 1, Đường ABC, Phường Bến Nghé, Quận 1, Hồ Chí Minh',
                'postal_code' => '700000',
                'region_code' => 'VN',
                'district_info' => [
                    ['address_level' => 'L0', 'address_level_name' => 'Country', 'address_name' => 'Vietnam'],
                    ['address_level' => 'L1', 'address_level_name' => 'Province', 'address_name' => 'Hồ Chí Minh'],
                    ['address_level' => 'L2', 'address_level_name' => 'District', 'address_name' => 'Quận 1'],
                    ['address_level' => 'L3', 'address_level_name' => 'Ward', 'address_name' => 'Phường Bến Nghé'],
                ],
            ],
            'payment' => [
                'currency' => 'VND',
                'sub_total' => '200000',
                'shipping_fee' => '20000',
                'seller_discount' => '5000',
                'platform_discount' => '10000',
                'shipping_fee_seller_discount' => '0',
                'shipping_fee_platform_discount' => '0',
                'tax' => '0',
                'total_amount' => '205000',
            ],
            'packages' => [['id' => '1153000000000000001']],
            'shipping_provider' => 'GHN',
            'tracking_number' => 'TN123456',
            // line_items: one row per unit (TikTok); two units of the same SKU here.
            'line_items' => [
                [
                    'id' => 'li-1', 'product_id' => 'p-1', 'product_name' => 'Áo thun cotton',
                    'sku_id' => 'sku-1', 'sku_name' => 'Đỏ, M', 'seller_sku' => 'AT-RED-M',
                    'sku_image' => 'https://img.example/at-red-m.jpg',
                    'sale_price' => '100000', 'original_price' => '120000', 'seller_discount' => '2500', 'platform_discount' => '5000',
                    'currency' => 'VND', 'package_id' => '1153000000000000001',
                ],
                [
                    'id' => 'li-2', 'product_id' => 'p-1', 'product_name' => 'Áo thun cotton',
                    'sku_id' => 'sku-1', 'sku_name' => 'Đỏ, M', 'seller_sku' => 'AT-RED-M',
                    'sku_image' => 'https://img.example/at-red-m.jpg',
                    'sale_price' => '100000', 'original_price' => '120000', 'seller_discount' => '2500', 'platform_discount' => '5000',
                    'currency' => 'VND', 'package_id' => '1153000000000000001',
                ],
            ],
        ];
    }

    /** GET /order/202309/orders?ids=... -> { data: { orders: [...] } } */
    public static function orderDetail(string $id = self::ORDER_ID, string $status = 'AWAITING_SHIPMENT', ?int $updateTime = null): array
    {
        return self::envelope(['orders' => [self::order($id, $status, $updateTime)]]);
    }

    /** POST /order/202309/orders/search -> { data: { orders, next_page_token, total_count } } */
    public static function ordersSearch(?array $orders = null, ?string $nextToken = null): array
    {
        $orders ??= [self::order(self::ORDER_ID, 'AWAITING_SHIPMENT')];

        return self::envelope([
            'orders' => $orders,
            'next_page_token' => $nextToken,
            'total_count' => count($orders),
        ]);
    }

    /** A webhook body + the matching Authorization signature (= HMAC-SHA256(secret, app_key + rawBody)). */
    public static function webhookOrderStatusChange(string $orderId = self::ORDER_ID, int $type = 1): array
    {
        $body = [
            'type' => $type,
            'tts_notification_id' => 'ntf-'.uniqid(),
            'shop_id' => self::SHOP_ID,
            'timestamp' => now()->timestamp,
            'data' => ['order_id' => $orderId, 'order_status' => 'AWAITING_COLLECTION', 'update_time' => now()->timestamp],
        ];
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $sig = hash_hmac('sha256', self::APP_KEY.$raw, self::APP_SECRET);

        return ['body' => $body, 'raw' => $raw, 'signature' => $sig];
    }

    /** Apply the test app_key/app_secret to config so the connector/verifier use these fixtures. */
    public static function configure(): void
    {
        config([
            'integrations.tiktok.app_key' => self::APP_KEY,
            'integrations.tiktok.app_secret' => self::APP_SECRET,
            'integrations.tiktok.subscribe_events' => [], // skip webhook subscription calls in tests
        ]);
    }
}
