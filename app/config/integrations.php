<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled marketplace channels
    |--------------------------------------------------------------------------
    |
    | Provider codes whose connectors should be loaded into ChannelRegistry.
    | Add 'tiktok', 'shopee', 'lazada' here once their connectors exist and
    | are approved on the respective open platforms. 'manual' is always on.
    |
    */
    'channels' => array_filter(explode(',', (string) env('INTEGRATIONS_CHANNELS', 'manual'))),

    /*
    |--------------------------------------------------------------------------
    | Enabled shipping carriers
    |--------------------------------------------------------------------------
    |
    | Carrier codes whose connectors should be loaded into CarrierRegistry,
    | e.g. ghn,ghtk,jt,viettelpost,ninjavan,spx,vnpost,best,ahamove.
    |
    */
    'carriers' => array_filter(explode(',', (string) env('INTEGRATIONS_CARRIERS', ''))),

    /*
    |--------------------------------------------------------------------------
    | Default carrier for self-fulfilled orders
    |--------------------------------------------------------------------------
    */
    'default_carrier' => env('INTEGRATIONS_DEFAULT_CARRIER'),

    /*
    |--------------------------------------------------------------------------
    | Per-provider request throttling (calls per minute, per shop)
    |--------------------------------------------------------------------------
    |
    | Used by the Redis rate limiter when calling marketplace APIs so one
    | shop/tenant cannot starve the shared queue. Tune per real limits.
    |
    */
    'throttle' => [
        'tiktok' => (int) env('THROTTLE_TIKTOK_PER_MIN', 600),
        'shopee' => (int) env('THROTTLE_SHOPEE_PER_MIN', 600),
        'lazada' => (int) env('THROTTLE_LAZADA_PER_MIN', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Order sync
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'poll_interval_minutes' => (int) env('SYNC_POLL_INTERVAL_MINUTES', 10),
        'poll_overlap_minutes' => (int) env('SYNC_POLL_OVERLAP_MINUTES', 5),
        'backfill_days' => (int) env('SYNC_BACKFILL_DAYS', 90),
        'reverse_stock_check' => (bool) env('SYNC_REVERSE_STOCK_CHECK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock push
    |--------------------------------------------------------------------------
    */
    'stock' => [
        'push_debounce_seconds' => (int) env('STOCK_PUSH_DEBOUNCE_SECONDS', 10),
        'default_safety_stock' => (int) env('STOCK_DEFAULT_SAFETY', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | TikTok Shop Partner API (see docs/04-channels/tiktok-shop.md, SPEC 0001)
    |--------------------------------------------------------------------------
    |
    | Sandbox vs production is just configuration: point base_url / auth_base_url
    | at the sandbox endpoints (or create a test app/seller in Partner Center)
    | and set TIKTOK_APP_KEY / TIKTOK_APP_SECRET to the sandbox credentials.
    | API version is pinned ("202309" generation); bump deliberately with tests.
    | This is the ONLY place TikTok-specific config / status & event maps live.
    |
    */
    'tiktok' => [
        'app_key' => env('TIKTOK_APP_KEY'),
        'app_secret' => env('TIKTOK_APP_SECRET'),
        // Partner "service" id used to build the seller authorization URL.
        'service_id' => env('TIKTOK_SERVICE_ID'),
        'sandbox' => (bool) env('TIKTOK_SANDBOX', env('TIKTOK_APP_SANDBOX', true)),

        // Hosts. Open API (signed, shop-scoped) vs auth host (token get/refresh)
        // vs the seller authorization page.
        'base_url' => env('TIKTOK_API_BASE_URL', 'https://open-api.tiktokglobalshop.com'),
        'auth_base_url' => env('TIKTOK_AUTH_BASE_URL', 'https://auth.tiktok-shops.com'),
        'authorize_url' => env('TIKTOK_AUTHORIZE_URL', 'https://services.tiktokshop.com/open/authorize'),

        // API version paths.
        'version' => [
            'order' => '202309',
            'authorization' => '202309',
            'event' => '202309',
            'product' => '202309',
            'fulfillment' => '202309',
        ],

        // "Luồng A" — khi "Chuẩn bị hàng": gọi API sàn arrange shipment (đẩy trạng thái "đã in đơn" lên sàn)
        // + lấy tem/AWB thật của sàn. Đường dẫn đã đối chiếu SDK chính thức (sdk_tiktok_seller, fulfillment
        // 202309: PackagesPackageIdShipPost / PackagesPackageIdGet / PackagesPackageIdShippingDocumentsGet).
        // Lỗi gọi sàn (vd shop yêu cầu handover_method) ⇒ được bắt & gắn cờ has_issue trên đơn, không chặn.
        'fulfillment_enabled' => env('INTEGRATIONS_TIKTOK_FULFILLMENT', true),
        'endpoints' => [
            'ship_package' => env('TIKTOK_SHIP_PACKAGE_PATH', '/fulfillment/{version}/packages/{package_id}/ship'),
            'package_detail' => env('TIKTOK_PACKAGE_DETAIL_PATH', '/fulfillment/{version}/packages/{package_id}'),
            'shipping_documents' => env('TIKTOK_SHIPPING_DOCS_PATH', '/fulfillment/{version}/packages/{package_id}/shipping_documents'),
            'handover_time_slots' => env('TIKTOK_HANDOVER_SLOTS_PATH', '/fulfillment/{version}/packages/{package_id}/handover_time_slots'),
            // Finance / Settlement (SPEC 0016) — đối chiếu SDK financeV202309Api.
            'finance_statements' => env('TIKTOK_FINANCE_STATEMENTS_PATH', '/finance/{version}/statements'),
            'finance_statement_transactions' => env('TIKTOK_FINANCE_STATEMENT_TX_PATH', '/finance/{version}/statements/{statement_id}/statement_transactions'),
        ],
        // Phase 6.2 — kéo đối soát/statement từ sàn. Off mặc định, bật bằng INTEGRATIONS_TIKTOK_FINANCE=true sau khi đối chiếu sandbox.
        'finance_enabled' => (bool) env('INTEGRATIONS_TIKTOK_FINANCE', false),

        'http' => [
            'timeout' => (int) env('TIKTOK_HTTP_TIMEOUT', 20),
            'retries' => (int) env('TIKTOK_HTTP_RETRIES', 2),
            'retry_sleep_ms' => (int) env('TIKTOK_HTTP_RETRY_SLEEP_MS', 500),
        ],

        // raw_status (TikTok) -> StandardOrderStatus. The single source of truth;
        // see docs/03-domain/order-status-state-machine.md §4. Verify against the
        // Partner API "order status" list when wiring real sandbox data.
        'status_map' => [
            'UNPAID' => 'unpaid',
            'ON_HOLD' => 'processing',
            // AWAITING_SHIPMENT = đã xác nhận, CHƯA in/arrange phiếu giao hàng ⇒ tab "Chờ xử lý".
            'AWAITING_SHIPMENT' => 'pending',
            'PARTIALLY_SHIPPING' => 'processing',
            // AWAITING_COLLECTION = đã in/arrange phiếu (TikTok "đang chờ lấy hàng") ⇒ tab "Đang xử lý"
            // (xử lý nội bộ: gói + quét). Chỉ chuyển sang "Chờ bàn giao" bằng thao tác nội bộ của ta. SPEC 0013.
            'AWAITING_COLLECTION' => 'processing',
            'IN_TRANSIT' => 'shipped',
            'DELIVERED' => 'delivered',
            'COMPLETED' => 'completed',
            'CANCELLED' => 'cancelled',
        ],

        // TikTok webhook "type" (integer) -> normalized WebhookEventDTO type.
        // Unknown types fall back to "unknown" and are recorded as ignored.
        // NOTE: numbers here are best-effort and MUST be verified against the
        // Partner API webhook docs before relying on event-type routing — order
        // events are always re-fetched via fetchOrderDetail, polling is the safety net.
        'webhook_event_types' => [
            1 => 'order_status_update',   // ORDER_STATUS_CHANGE
            2 => 'return_update',         // REVERSE / RETURN status change
            3 => 'order_status_update',   // RECIPIENT_ADDRESS_UPDATE -> re-fetch the order
            4 => 'order_status_update',   // PACKAGE_UPDATE -> re-fetch the order
            5 => 'product_update',        // PRODUCT_STATUS_CHANGE
            6 => 'shop_deauthorized',     // SELLER_DEAUTHORIZATION
            12 => 'order_cancel',         // CANCELLATION_STATUS_CHANGE
            13 => 'return_update',        // RETURN_STATUS_CHANGE
            14 => 'shop_deauthorized',    // AUTHORIZATION_REVOKE / SHOP update
        ],

        // Webhook event names we subscribe this shop to (best-effort; many apps
        // configure events in Partner Center instead).
        'subscribe_events' => array_filter(explode(',', (string) env(
            'TIKTOK_SUBSCRIBE_EVENTS',
            'ORDER_STATUS_CHANGE,RECIPIENT_ADDRESS_UPDATE,PACKAGE_UPDATE,CANCELLATION_STATUS_CHANGE,RETURN_STATUS_CHANGE,SELLER_DEAUTHORIZATION'
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazada Open Platform (Vietnam) — see docs/04-channels/lazada.md, SPEC 0008
    |--------------------------------------------------------------------------
    |
    | Sandbox vs production = config: keep the same hosts (Lazada's sandbox is a
    | separate app in the same console) and set LAZADA_APP_KEY / LAZADA_APP_SECRET
    | to the sandbox credentials. The `redirect_uri` MUST equal the callback URL
    | registered in the Lazada app console: https://<APP_URL host>/oauth/lazada/callback.
    | This is the ONLY place Lazada-specific config / status & message maps live.
    |
    */
    'lazada' => [
        'app_key' => env('LAZADA_APP_KEY'),
        'app_secret' => env('LAZADA_APP_SECRET'),
        'sandbox' => (bool) env('LAZADA_SANDBOX', true),

        // Vietnam REST gateway + auth host + seller authorization page.
        'api_base_url' => env('LAZADA_API_BASE_URL', 'https://api.lazada.vn/rest'),
        'auth_base_url' => env('LAZADA_AUTH_BASE_URL', 'https://auth.lazada.com/rest'),
        'authorize_url' => env('LAZADA_AUTHORIZE_URL', 'https://auth.lazada.com/oauth/authorize'),
        'redirect_uri' => env('LAZADA_REDIRECT_URI'),   // defaults to url('/oauth/lazada/callback')

        'http' => [
            'timeout' => (int) env('LAZADA_HTTP_TIMEOUT', 20),
            'retries' => (int) env('LAZADA_HTTP_RETRIES', 2),
            'retry_sleep_ms' => (int) env('LAZADA_HTTP_RETRY_SLEEP_MS', 500),
        ],

        // `partner_id` trong sysParams: gateway Lazada một số phiên bản chỉ chấp nhận `partner_id` trùng
        // với SDK chính thức của Open Platform — đặt mặc định khớp `LazopClient.sdkVersion`
        // (`sdk_lazada_php/lazop/LazopClient.php`, đảm bảo Lazada không trả "MissingPartner" / sai sign).
        'partner_id' => env('LAZADA_PARTNER_ID', 'lazop-sdk-php-20180422'),
        // `force_auth=true` ở URL ủy quyền — TÀI LIỆU CHÍNH THỨC khuyên dùng (làm mới session cookie để seller
        // không bị Lazada gắn vào tài khoản đang đăng nhập sẵn). Mặc định BẬT; chỉ tắt khi shop yêu cầu.
        'authorize_force_auth' => (bool) env('LAZADA_AUTHORIZE_FORCE_AUTH', true),
        // (Tuỳ chọn) Lọc consent screen theo quốc gia. Csv `sg,my,th,vn,ph,id,cb` — mặc định để trống ⇒ Lazada
        // hỏi tất cả. Set `vn` cho shop VN chỉ thấy danh sách 1 quốc gia, gọn hơn.
        'authorize_country' => env('LAZADA_AUTHORIZE_COUNTRY', 'vn'),
        // Bật log request (path + tên tham số, KHÔNG có giá trị) để soi flow cấp quyền khi cần hỗ trợ.
        'log_requests' => (bool) env('LAZADA_LOG_REQUESTS', false),
        // Cách verify chữ ký webhook push: `strict` (sai ⇒ 401, Lazada retry) | `lenient` (sai ⇒ vẫn ack 200
        // + log; tránh kẹt khi chưa chốt scheme với sandbox). KHÔNG dùng `lenient` cho production.
        'webhook_verify_mode' => env('LAZADA_WEBHOOK_VERIFY_MODE', 'strict'),

        // Some Lazada endpoints differ slightly across API versions / sandbox.
        'endpoints' => [
            'update_stock' => env('LAZADA_UPDATE_STOCK_PATH', '/product/price_quantity/update'),
            'update_stock_format' => env('LAZADA_UPDATE_STOCK_FORMAT', 'json'),   // 'json' | 'xml'
            // Fulfillment "luồng A" (SPEC 0008/0013). Cả 3 path khớp tài liệu Lazada Open Platform —
            // đổi nếu sandbox shop khác (vài region dùng path khác); không cần đổi code.
            //   - `/shipment/providers/get` (lấy danh sách 3PL hỗ trợ shop)
            //   - `/order/pack`  → assign 3PL & tracking_number cho list `order_item_id`
            //   - `/order/rts`   → push item sang ready_to_ship trên Lazada (bắt buộc sau pack)
            //   - `/order/document/get` → trả `data.document.file` (base64 PDF)
            'shipment_providers' => env('LAZADA_SHIPMENT_PROVIDERS_PATH', '/shipment/providers/get'),
            'order_pack' => env('LAZADA_ORDER_PACK_PATH', '/order/pack'),
            'order_rts' => env('LAZADA_ORDER_RTS_PATH', '/order/rts'),
            'document_get' => env('LAZADA_DOCUMENT_GET_PATH', '/order/document/get'),
            // PrintAWB (preferred VN) — lấy AWB PDF theo `package_id` thay vì `order_item_ids`; trả PDF ổn
            // định hơn cho LEX VN / GHN / J&T (legacy `/order/document/get` thường rỗng vài giây đầu sau /rts).
            // SPEC 0008b; nếu shop SoC chỉ chấp nhận legacy, đè bằng env (set thành ''/null để tắt).
            'print_awb' => env('LAZADA_PRINT_AWB_PATH', '/order/package/document/get'),
            'doc_type_map' => [
                'SHIPPING_LABEL' => env('LAZADA_DOC_TYPE_SHIPPING_LABEL', 'shippingLabel'),
                'SHIPPING_LABEL_AND_PACKING_SLIP' => env('LAZADA_DOC_TYPE_SHIPPING_LABEL', 'shippingLabel'),
                'INVOICE' => 'invoice',
                'CARRIER_MANIFEST' => 'carrierManifest',
                'PICKLIST' => 'pickList',
            ],
            // Finance (Phase 6.2 — SPEC 0016). Mặc định path tài liệu chính thức; đổi nếu app version khác.
            'transaction_details' => env('LAZADA_FINANCE_TRANSACTIONS_PATH', '/finance/transaction/details/get'),
        ],
        // "Luồng A" master flag — bật `arrangeShipment` + `getShippingDocument`. Tắt bằng
        // `INTEGRATIONS_LAZADA_FULFILLMENT=false` nếu app chưa có permission "Fulfillment".
        'fulfillment_enabled' => (bool) env('INTEGRATIONS_LAZADA_FULFILLMENT', true),
        // Chế độ luồng A:
        //   - `auto` (mặc định) — `arrangeShipment` tự gọi `/shipment/providers/get` → `/order/pack` →
        //     `/order/rts` → re-fetch ⇒ trả tracking. Đây là cách app tự đẩy đơn lên RTS trên Lazada
        //     (đúng SPEC 0013 §4 — "Chuẩn bị hàng" tự cập nhật trạng thái lên sàn).
        //   - `refetch_only` — legacy: chỉ re-fetch order detail để lấy tracking nếu shop đã pack ngoài
        //     Lazada Seller Center. Dùng khi app chưa có permission Fulfillment.
        'fulfillment_mode' => env('LAZADA_FULFILLMENT_MODE', 'auto'),
        // Lazada chỉ hỗ trợ `dropship` cho non-FBL (theo tài liệu chính thức). Để config-able phòng khi
        // sandbox shop có loại khác (`pickup` đã deprecate ở hầu hết region).
        'default_delivery_type' => env('LAZADA_DEFAULT_DELIVERY_TYPE', 'dropship'),
        // (Tuỳ chọn) Tên `shipment_provider` mặc định — nếu set, bỏ qua bước resolve qua
        // `/shipment/providers/get`. Hữu ích khi shop chỉ dùng 1 ĐVVC cố định (ví dụ "Lazada Express VN").
        'default_shipment_provider' => env('LAZADA_DEFAULT_SHIPMENT_PROVIDER'),
        // DEPRECATED — giữ làm legacy alias để env cũ không vỡ. Mode mới = `fulfillment_mode='auto'`.
        'fulfillment_auto_pack' => (bool) env('LAZADA_FULFILLMENT_AUTO_PACK', true),
        // Phase 6.2 — kéo đối soát. Mặc định off, bật bằng INTEGRATIONS_LAZADA_FINANCE=true sau khi đối chiếu sandbox.
        'finance_enabled' => (bool) env('INTEGRATIONS_LAZADA_FINANCE', false),

        // Lazada raw (item-level) order status -> StandardOrderStatus. The single source of truth;
        // see docs/03-domain/order-status-state-machine.md §4. Keys normalized (lowercase, '_').
        'status_map' => [
            'unpaid' => 'unpaid',
            // pending = đã xác nhận, chưa RTS/in phiếu ⇒ "Chờ xử lý"
            'pending' => 'pending',
            'topack' => 'pending',
            // packed / ready_to_ship = đã RTS/in phiếu (Lazada chờ ĐVVC lấy) ⇒ "Đang xử lý" (gói + quét nội bộ).
            // Chỉ chuyển sang "Chờ bàn giao" bằng thao tác nội bộ. SPEC 0013.
            'ready_to_ship' => 'processing',
            'ready_to_ship_pending' => 'processing',
            'packed' => 'processing',
            'shipped' => 'shipped',
            'shipped_back' => 'returning',
            'shipped_back_failed' => 'returning',
            'delivered' => 'delivered',
            'failed' => 'delivery_failed',
            'lost' => 'delivery_failed',
            'damaged' => 'delivery_failed',
            'returned' => 'returned_refunded',
            'canceled' => 'cancelled',
            'cancelled' => 'cancelled',
        ],

        // Lazada push "message_type" (int) -> normalized WebhookEventDTO type. Unknown but
        // carrying an order id -> treated as order_status_update. VERIFY against the Open
        // Platform "App Push" docs; order pushes always trigger a fetchOrderDetail re-fetch.
        'webhook_message_types' => [
            0 => 'order_status_update',   // Trade order status change
            1 => 'order_status_update',
            2 => 'order_status_update',
            4 => 'shop_deauthorized',     // App authorization revoked / token expiring
            5 => 'product_update',
            6 => 'data_deletion',         // (best-effort) personal-data removal request
        ],
    ],

];
