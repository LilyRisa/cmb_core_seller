<?php

namespace Tests\Fixtures\Channels\Shopee;

/** Static factories returning Shopee Open Platform v2 response shapes for Http::fake. */
final class ShopeeFixtures
{
    public static function configure(): void
    {
        config([
            'integrations.shopee.partner_id' => 1001,
            'integrations.shopee.partner_key' => 'PARTNER_KEY',
            'integrations.shopee.base_url' => 'https://openplatform.sandbox.test-stable.shopee.sg',
            'integrations.shopee.finance_enabled' => false,
            'integrations.shopee.fulfillment_enabled' => true,
            'integrations.shopee.status_map' => [
                'UNPAID' => 'unpaid', 'READY_TO_SHIP' => 'pending', 'PROCESSED' => 'processing',
                'RETRY_SHIP' => 'processing', 'SHIPPED' => 'shipped', 'TO_CONFIRM_RECEIVE' => 'delivered',
                'COMPLETED' => 'completed', 'IN_CANCEL' => 'processing', 'CANCELLED' => 'cancelled', 'TO_RETURN' => 'returning',
            ],
            'integrations.shopee.webhook_event_types' => [1 => 'unknown', 2 => 'shop_deauthorized', 3 => 'order_status_update', 4 => 'order_status_update', 6 => 'product_update', 12 => 'unknown', 15 => 'unknown'],
            'integrations.shopee.endpoints' => [
                'auth_partner' => '/api/v2/shop/auth_partner', 'token_get' => '/api/v2/auth/token/get',
                'token_refresh' => '/api/v2/auth/access_token/get', 'shop_info' => '/api/v2/shop/get_shop_info',
                'order_list' => '/api/v2/order/get_order_list', 'order_detail' => '/api/v2/order/get_order_detail',
                'shipping_parameter' => '/api/v2/logistics/get_shipping_parameter', 'ship_order' => '/api/v2/logistics/ship_order',
                'tracking_number' => '/api/v2/logistics/get_tracking_number', 'create_document' => '/api/v2/logistics/create_shipping_document',
                'get_document_result' => '/api/v2/logistics/get_shipping_document_result', 'download_document' => '/api/v2/logistics/download_shipping_document',
                'item_list' => '/api/v2/product/get_item_list', 'item_base_info' => '/api/v2/product/get_item_base_info',
                'model_list' => '/api/v2/product/get_model_list', 'update_stock' => '/api/v2/product/update_stock',
                'escrow_detail' => '/api/v2/payment/get_escrow_detail', 'escrow_list' => '/api/v2/payment/get_escrow_list',
                'send_message' => '/api/v2/sellerchat/send_message',
                'return_list' => '/api/v2/returns/get_return_list', 'return_detail' => '/api/v2/returns/get_return_detail',
                'return_confirm' => '/api/v2/returns/confirm', 'return_dispute' => '/api/v2/returns/dispute',
                'handle_cancellation' => '/api/v2/order/handle_buyer_cancellation',
            ],
            'integrations.shopee.return_status_map' => [
                'REQUESTED' => 'requested', 'ACCEPTED' => 'approved', 'PROCESSING' => 'processing', 'JUDGING' => 'processing', 'CLOSED' => 'completed',
            ],
            'integrations.shopee.chat_push_codes' => [10],
        ]);
    }

    /** @return array<string,mixed> */
    public static function tokenGet(): array
    {
        return ['error' => '', 'request_id' => 'r1', 'access_token' => 'ACCESS_1', 'refresh_token' => 'REFRESH_1', 'expire_in' => 14400, 'shop_id' => 55];
    }

    /** @return array<string,mixed> */
    public static function shopInfo(): array
    {
        return ['error' => '', 'request_id' => 'r2', 'response' => ['shop_name' => 'Shop Shopee VN', 'region' => 'VN', 'status' => 'NORMAL']];
    }

    /** @return array<string,mixed> get_order_list page */
    public static function orderList(string $nextCursor = '', bool $more = false): array
    {
        return ['error' => '', 'response' => [
            'order_list' => [['order_sn' => 'SN_1'], ['order_sn' => 'SN_2']],
            'next_cursor' => $nextCursor, 'more' => $more,
        ]];
    }

    /** @return array<string,mixed> get_order_detail */
    public static function orderDetail(): array
    {
        return ['error' => '', 'response' => ['order_list' => [
            self::orderRow('SN_1', 'READY_TO_SHIP'),
            self::orderRow('SN_2', 'PROCESSED'),
        ]]];
    }

    /** @return array<string,mixed> */
    public static function orderRow(string $sn, string $status): array
    {
        return [
            'order_sn' => $sn, 'order_status' => $status, 'update_time' => 1700000000, 'create_time' => 1699990000,
            'currency' => 'VND', 'cod' => true, 'total_amount' => 250000, 'actual_shipping_fee' => 20000,
            'buyer_username' => 'buyer_'.$sn,
            'recipient_address' => ['name' => 'Nguyen Van A', 'phone' => '0900000000', 'full_address' => '12 Le Loi', 'town' => 'P1', 'district' => 'Q1', 'city' => 'HCM', 'state' => 'HCM', 'zipcode' => '700000'],
            'item_list' => [[
                'item_id' => 111, 'model_id' => 222, 'item_sku' => 'SKU-A', 'model_sku' => 'SKU-A-RED',
                'item_name' => 'Áo thun', 'model_name' => 'Đỏ / M', 'model_quantity_purchased' => 2,
                'model_discounted_price' => 115000, 'image_info' => ['image_url' => 'https://img/a.jpg'],
            ]],
            'package_list' => [['package_number' => 'PKG_1', 'shipping_carrier' => 'SPX Express']],
        ];
    }

    public static function shippingParameter(): array
    {
        return ['error' => '', 'response' => ['dropoff' => [], 'pickup' => ['address_list' => [['address_id' => 9, 'time_slot_list' => [['pickup_time_id' => 'T1']]]]]]];
    }

    public static function shipOrder(): array
    {
        return ['error' => '', 'response' => []];
    }

    public static function trackingNumber(): array
    {
        return ['error' => '', 'response' => ['tracking_number' => 'TRK123', 'first_mile_tracking_number' => null]];
    }

    public static function createDocument(): array
    {
        return ['error' => '', 'response' => ['result_list' => [['order_sn' => 'SN_1', 'package_number' => 'PKG_1']]]];
    }

    public static function documentResult(string $status = 'READY'): array
    {
        return ['error' => '', 'response' => ['result_list' => [['order_sn' => 'SN_1', 'package_number' => 'PKG_1', 'status' => $status]]]];
    }

    public static function itemList(): array
    {
        return ['error' => '', 'response' => ['item' => [['item_id' => 111]], 'next_offset' => 0, 'has_next_page' => false]];
    }

    public static function itemBaseInfo(): array
    {
        return ['error' => '', 'response' => ['item_list' => [[
            'item_id' => 111, 'item_name' => 'Áo thun', 'item_sku' => 'SKU-A', 'item_status' => 'NORMAL',
            'image' => ['image_url_list' => ['https://img/a.jpg']], 'price_info' => [['current_price' => 120000]],
        ]]]];
    }

    public static function modelList(): array
    {
        return ['error' => '', 'response' => ['model' => [
            ['model_id' => 222, 'model_sku' => 'SKU-A-RED', 'price_info' => [['current_price' => 115000]], 'stock_info_v2' => ['summary_info' => ['total_available_stock' => 7]]],
        ]]];
    }

    public static function escrowList(): array
    {
        return ['error' => '', 'response' => ['order_sn_list' => ['SN_1'], 'more' => false]];
    }

    /** Page 1 of a multi-page escrow list (more=true). */
    public static function escrowListPage1(): array
    {
        return ['error' => '', 'response' => ['order_sn_list' => ['SN_P1'], 'more' => true]];
    }

    /** Page 2 of a multi-page escrow list (more=false / last page). */
    public static function escrowListPage2(): array
    {
        return ['error' => '', 'response' => ['order_sn_list' => ['SN_P2'], 'more' => false]];
    }

    /** shipping_parameter response where the platform offers a dropoff option. */
    public static function shippingParameterDropoff(): array
    {
        return ['error' => '', 'response' => ['dropoff' => ['branch_list' => [['branch_id' => 7]]], 'pickup' => []]];
    }

    public static function escrowDetail(): array
    {
        return ['error' => '', 'response' => [
            'order_sn' => 'SN_1',
            'order_income' => [
                'escrow_amount' => 210000, 'buyer_total_amount' => 250000,
                'commission_fee' => 15000, 'service_fee' => 5000, 'seller_transaction_fee' => 2000,
                'actual_shipping_fee' => 20000, 'shopee_shipping_rebate' => 18000,
                'voucher_from_seller' => 0, 'voucher_from_shopee' => 0,
            ],
        ]];
    }
}
