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
    | Enabled messaging providers (SPEC-0024 / ADR-0017 — Phase 7.x đề xuất)
    |--------------------------------------------------------------------------
    |
    | Provider codes whose messaging connectors should be loaded into
    | MessagingRegistry. `manual` luôn nạp (test/dev — không cần env).
    | Khi connector thật sẵn sàng (S2+): thêm vào env, vd
    | `INTEGRATIONS_MESSAGING=facebook_page,tiktok_chat`.
    |
    */
    'messaging' => array_filter(explode(',', (string) env('INTEGRATIONS_MESSAGING', ''))),

    /*
    |--------------------------------------------------------------------------
    | Facebook Page Messenger (SPEC-0024 — S2)
    |--------------------------------------------------------------------------
    |
    | `verify_token` cho GET /webhook/messaging/facebook hub.challenge.
    | App secret / Page tokens sống ở `channel_accounts` per page.
    |
    */
    'messaging_facebook_page' => [
        'verify_token' => env('MESSAGING_FACEBOOK_VERIFY_TOKEN'),
        'app_id' => env('MESSAGING_FACEBOOK_APP_ID'),
        'app_secret' => env('MESSAGING_FACEBOOK_APP_SECRET'),
        'graph_version' => env('MESSAGING_FACEBOOK_GRAPH_VERSION', 'v19.0'),
        // Redirect URI OAuth — PHẢI giống hệt giữa dialog login & đổi code lấy token
        // (Meta yêu cầu khớp tuyệt đối). Mặc định suy từ APP_URL; override khi domain khác.
        'redirect_uri' => env('MESSAGING_FACEBOOK_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazada IM Chat — app "IM ERP" RIÊNG (tách khỏi orders)
    |--------------------------------------------------------------------------
    |
    | Lazada gate quyền IM theo app ⇒ chat KHÔNG dùng chung app/token orders
    | (`integrations.lazada`). App IM ERP có OAuth + token riêng, lưu ở
    | `channel_accounts` provider `lazada_im`. Mặc định kế thừa host Lazada VN.
    | Xem docs/superpowers/specs/2026-06-04-lazada-im-chat-separate-app-design.md.
    |
    */
    'messaging_lazada_im' => [
        'app_key' => env('LAZADA_IM_APP_KEY'),
        'app_secret' => env('LAZADA_IM_APP_SECRET'),
        'api_base_url' => env('LAZADA_IM_API_BASE_URL', env('LAZADA_API_BASE_URL', 'https://api.lazada.vn/rest')),
        'auth_base_url' => env('LAZADA_IM_AUTH_BASE_URL', env('LAZADA_AUTH_BASE_URL', 'https://auth.lazada.com/rest')),
        'authorize_url' => env('LAZADA_IM_AUTHORIZE_URL', env('LAZADA_AUTHORIZE_URL', 'https://auth.lazada.com/oauth/authorize')),
        'redirect_uri' => env('LAZADA_IM_REDIRECT_URI'),   // mặc định suy từ APP_URL + /oauth/lazada_im/callback
        'partner_id' => env('LAZADA_PARTNER_ID', 'lazop-sdk-php-20180422'),
        'authorize_force_auth' => (bool) env('LAZADA_AUTHORIZE_FORCE_AUTH', true),
        'authorize_country' => env('LAZADA_AUTHORIZE_COUNTRY', 'vn'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Facebook Ads (Marketing API) — near-real-time insights + AI optimization
    |--------------------------------------------------------------------------
    |
    | `ads` = enabled providers CSV (INTEGRATIONS_ADS, vd `facebook`). Reuse the
    | existing Meta app by default (Meta cho 1 app nhiều product/scope); override
    | với FACEBOOK_ADS_* nếu dùng app riêng. ads_read cho Phase 1 (đọc); thêm
    | ads_management ở Phase 3 (ghi). Xem docs/superpowers/specs/2026-06-04-...
    |
    */
    'ads' => array_filter(explode(',', (string) env('INTEGRATIONS_ADS', ''))),

    'ads_facebook' => [
        // Dùng lại app Facebook hiện có (Page/Messenger) cho Ads — KHÔNG cần app riêng.
        // Mặc định fallback sang MESSAGING_FACEBOOK_*; chỉ set FACEBOOK_ADS_* nếu sau này tách app.
        'app_id' => env('FACEBOOK_ADS_APP_ID', env('MESSAGING_FACEBOOK_APP_ID')),
        'app_secret' => env('FACEBOOK_ADS_APP_SECRET', env('MESSAGING_FACEBOOK_APP_SECRET')),
        // graph_version cố định ở FacebookAdsConnector::GRAPH_VERSION (đổi version phải sửa code → env thừa).
        'redirect_uri' => env('FACEBOOK_ADS_REDIRECT_URI'), // mặc định APP_URL + /oauth/facebook_ads/callback
        // ads_management cần cho: tạo/sửa quảng cáo, sửa ngân sách/tạm dừng, đọc & chia sẻ Pixel.
        'scopes' => env('FACEBOOK_ADS_SCOPES', 'ads_management,ads_read,business_management'),
    ],

    // TikTok Marketing API (Ads) — ADR-0025, read-only Phase 1. App RIÊNG (KHÔNG dùng
    // lại TIKTOK_APP_KEY của TikTok Shop). Token dài hạn không hết hạn; redirect đã
    // cấu hình trên TikTok portal. Xem docs/04-channels/tiktok-ads-setup.md.
    'ads_tiktok' => [
        'app_id' => env('TIKTOK_ADS_APP_ID'),
        'app_secret' => env('TIKTOK_ADS_APP_SECRET'),
        'base_url' => env('TIKTOK_ADS_BASE_URL', 'https://business-api.tiktok.com/open_api/v1.3'),
        'auth_url' => env('TIKTOK_ADS_AUTH_URL', 'https://business-api.tiktok.com/portal/auth'),
        'redirect_uri' => env('TIKTOK_ADS_REDIRECT_URI'), // mặc định APP_URL + /oauth/tiktok_marketing/redirect
    ],

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
    | Viettel Post (VTP) Open API — SPEC 0034
    |--------------------------------------------------------------------------
    |
    | Connector dùng base_url cho mọi API (login, danh mục, tạo đơn, hủy, in mã).
    | Prod: https://partner.viettelpost.vn · Dev: https://partnerdev.viettelpost.vn.
    | In nhãn dùng host RIÊNG (print_base_url): prod digitalize.viettelpost.vn,
    | dev dev-release-print.viettelpost.vn. Credentials (username/password HOẶC
    | token web VTP) lưu per-tenant ở carrier_accounts — KHÔNG ở đây.
    |
    */
    'viettelpost' => [
        'base_url' => env('VIETTELPOST_BASE_URL', 'https://partner.viettelpost.vn'),
        'print_base_url' => env('VIETTELPOST_PRINT_BASE_URL', 'https://digitalize.viettelpost.vn'),
        'http' => [
            'timeout' => (int) env('VIETTELPOST_HTTP_TIMEOUT', 20),
        ],
    ],

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
        // Trần số trang mỗi lần đồng bộ. Khi chạm trần mà sàn vẫn còn trang, watermark KHÔNG nhảy
        // lên "bây giờ" mà dừng ở update_time đơn cuối đã xử lý (sort ASC) để lần poll sau tiếp tục
        // — tránh bỏ sót đơn mới nhất khi backlog vượt trần. Xem SyncOrdersForShop.
        'poll_max_pages' => (int) env('SYNC_POLL_MAX_PAGES', 50),
        'backfill_max_pages' => (int) env('SYNC_BACKFILL_MAX_PAGES', 500),
        'unprocessed_lookback_days' => (int) env('SYNC_UNPROCESSED_LOOKBACK_DAYS', 365),
        'unprocessed_max_pages_per_status' => (int) env('SYNC_UNPROCESSED_MAX_PAGES_PER_STATUS', 200),
        // After-sales (Hoàn & Hủy — SPEC 0025): cửa sổ poll theo update_time + trần trang.
        'returns_lookback_days' => (int) env('SYNC_RETURNS_LOOKBACK_DAYS', 90),
        'returns_max_pages' => (int) env('SYNC_RETURNS_MAX_PAGES', 50),
        // Giữ mô hình 2 bước nội bộ cho đơn sàn: "Chuẩn bị hàng" → Processing; chỉ thao tác "Đã gói & sẵn sàng
        // bàn giao" (markPacked) mới đẩy ReadyToShip. Một số sàn (vd Lazada vài delivery_type) tự đẩy đơn lên
        // ready_to_ship ngay sau /order/pack ⇒ đồng bộ ngược sẽ "tự nhảy" Processing→Chờ bàn giao phi lý.
        // BẬT (mặc định) ⇒ KHÔNG auto-nhảy Processing→ReadyToShip từ sync (vẫn lưu raw_status thật). Tắt nếu
        // muốn theo sát sàn tuyệt đối. Xem docs/03-domain/order-status-state-machine.md.
        'hold_channel_ready_to_ship' => (bool) env('SYNC_HOLD_CHANNEL_READY_TO_SHIP', true),
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
    | Per-marketplace listing media limits (product publishing)
    |--------------------------------------------------------------------------
    |
    | Số ảnh / video tối đa mỗi listing theo từng sàn — dùng cho cả validator
    | (backend) lẫn UI soạn nháp (chặn upload vượt mức). Đối chiếu tài liệu sàn;
    | đổi qua env khi sàn nới/siết hạn mức.
    |
    */
    'listing_limits' => [
        'shopee' => ['max_images' => (int) env('SHOPEE_MAX_IMAGES', 9), 'max_videos' => (int) env('SHOPEE_MAX_VIDEOS', 1)],
        'tiktok' => ['max_images' => (int) env('TIKTOK_MAX_IMAGES', 9), 'max_videos' => (int) env('TIKTOK_MAX_VIDEOS', 1)],
        'lazada' => ['max_images' => (int) env('LAZADA_MAX_IMAGES', 8), 'max_videos' => (int) env('LAZADA_MAX_VIDEOS', 1)],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment gateways (SPEC 0018 — Billing SaaS, Phase 6.4)
    |--------------------------------------------------------------------------
    |
    | Mỗi cổng = 1 dòng `class-string` trong IntegrationsServiceProvider + 1 block ở đây.
    | `enabled` csv quyết định cổng nào được nạp vào PaymentRegistry. SePay là cổng đầu
    | tiên đi production (PR2); VNPay (PR3); MoMo skeleton (PR3, capability=false).
    |
    */
    'payments' => [
        'enabled' => array_filter(explode(',', (string) env('INTEGRATIONS_PAYMENTS', 'sepay'))),

        // SePay — chuyển khoản tự động qua webhook sao kê. Cấu hình từ env:
        //   SEPAY_ACCOUNT_NO, SEPAY_ACCOUNT_NAME, SEPAY_BANK_CODE (vd: 'MB', 'TCB', 'VCB'),
        //   SEPAY_WEBHOOK_API_KEY (key SePay cấp khi cấu hình webhook URL).
        'sepay' => [
            'account_no' => env('SEPAY_ACCOUNT_NO'),
            'account_name' => env('SEPAY_ACCOUNT_NAME'),
            'bank_code' => env('SEPAY_BANK_CODE', 'MB'),
            'webhook_api_key' => env('SEPAY_WEBHOOK_API_KEY'),
            // Template VietQR (compact, compact2, qr_only, print). compact2 = giao diện đẹp + đủ thông tin.
            'qr_template' => env('SEPAY_QR_TEMPLATE', 'compact2'),
        ],

        // VNPay — redirect + IPN (PR3, SPEC 0018). Cấu hình:
        //   VNPAY_TMN_CODE, VNPAY_HASH_SECRET, VNPAY_RETURN_URL.
        'vnpay' => [
            'tmn_code' => env('VNPAY_TMN_CODE'),
            'hash_secret' => env('VNPAY_HASH_SECRET'),
            'pay_url' => env('VNPAY_PAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
            'return_url' => env('VNPAY_RETURN_URL'),   // SPA URL, được sinh nếu để trống
            'version' => env('VNPAY_VERSION', '2.1.0'),
            'curr_code' => 'VND',
            'locale' => 'vn',
        ],

        // MoMo — chỉ skeleton ở PR3 (capability=false). Cấu hình sẽ thêm khi triển khai thật.
        'momo' => [
            'partner_code' => env('MOMO_PARTNER_CODE'),
            'access_key' => env('MOMO_ACCESS_KEY'),
            'secret_key' => env('MOMO_SECRET_KEY'),
        ],
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
            'order' => '202309',              // Get Order List: POST /order/202309/orders/search
            // Get Order Detail có generation mới hơn (docv2_page_get-order-detail-202507).
            // Tách riêng để nâng độc lập với list — response 202507 là superset của 202309
            // (cùng tên field payment/line_items/recipient_address) nên mapper không đổi.
            'order_detail' => '202507',       // Get Order Detail: GET /order/202507/orders
            'authorization' => '202309',
            'event' => '202309',
            'product' => '202309',
            'fulfillment' => '202309',
            'return_refund' => '202309',      // After-sales: /return_refund/202309/returns|cancellations/search
            // Finance (SPEC 0016) — tách 2 đời vì khác nhau: bản LIST statements CHỈ tồn tại ở 202309
            // (financeV202309Api), còn statement_transactions có cả 202309 (schema phẳng) lẫn 202501
            // (schema breakdown). Mapper xử lý được cả hai. Xem tailieuapi_itiktok_shopee_lazada/tiktok/finance/.
            'finance_statements' => env('TIKTOK_FINANCE_STATEMENTS_VERSION', '202309'),
            'finance_transactions' => env('TIKTOK_FINANCE_TRANSACTIONS_VERSION', '202501'),
        ],

        // Đồng bộ Hoàn & Hủy (SPEC 0025). Bật mặc định (API return_refund 202309 ổn định). Tắt bằng
        // INTEGRATIONS_TIKTOK_RETURNS=false. Gồm fetch (poll/webhook) + manage (duyệt/từ chối).
        'returns_enabled' => (bool) env('INTEGRATIONS_TIKTOK_RETURNS', true),
        // raw return/cancel status (TikTok) → AfterSalesStatus. Mở rộng khi gặp sample sandbox; mapper có fallback.
        'return_status_map' => [
            'RETURN_OR_REFUND_REQUEST_PENDING' => 'requested',
            'AFTERSALE_APPLYING' => 'requested',
            'CANCELLATION_REQUEST_PENDING' => 'requested',
            'AGREE_REFUND' => 'approved',
            'APPROVED' => 'approved',
            'AWAITING_BUYER_SHIP' => 'processing',
            'BUYER_SHIPPED' => 'processing',
            'RETURNING' => 'processing',
            'SELLER_RECEIVED_RETURN' => 'processing',
            'RETURN_OR_REFUND_REQUEST_SUCCESS' => 'completed',
            'CANCELLATION_REQUEST_SUCCESS' => 'completed',
            'REQUEST_SUCCESS' => 'completed',
            'COMPLETED' => 'completed',
            'RETURN_OR_REFUND_REQUEST_REJECT' => 'rejected',
            'CANCELLATION_REQUEST_REJECT' => 'rejected',
            'REQUEST_REJECTED' => 'rejected',
            'RETURN_OR_REFUND_CANCEL' => 'cancelled',
            'CANCEL' => 'cancelled',
            'CANCELLED' => 'cancelled',
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
        // Phase 6.2 — kéo đối soát/statement từ sàn. Bật mặc định (đã đối chiếu tài liệu Finance
        // API + SDK, xem tailieuapi_itiktok_shopee_lazada/tiktok/finance/). Tắt bằng INTEGRATIONS_TIKTOK_FINANCE=false.
        'finance_enabled' => (bool) env('INTEGRATIONS_TIKTOK_FINANCE', true),

        'http' => [
            'timeout' => (int) env('TIKTOK_HTTP_TIMEOUT', 20),
            'retries' => (int) env('TIKTOK_HTTP_RETRIES', 2),
            'retry_sleep_ms' => (int) env('TIKTOK_HTTP_RETRY_SLEEP_MS', 500),
        ],

        // raw_status mà sàn KHÔNG cho "Chuẩn bị hàng" (arrange shipment). TikTok ON_HOLD: doc ghi rõ
        // "ON_HOLD orders are NOT allowed to be fulfilled" + địa chỉ người nhận chưa có ⇒ chặn ở
        // ShipmentService::createForOrder để khỏi gọi sàn lỗi / in tem khi sàn chưa cho.
        'unfulfillable_raw_statuses' => ['ON_HOLD'],

        // raw_status (TikTok) -> StandardOrderStatus. The single source of truth;
        // see docs/03-domain/order-status-state-machine.md §4. Verify against the
        // Partner API "order status" list when wiring real sandbox data.
        'status_map' => [
            'UNPAID' => 'unpaid',
            // ON_HOLD = đã nhận đơn, CHỜ fulfillment, buyer còn tự huỷ KHÔNG cần seller duyệt (doc get-order-list:
            // "awaiting fulfillment. The buyer may still cancel without the seller's approval"). Chưa in/arrange
            // phiếu ⇒ cùng nhóm "Chờ xử lý" với AWAITING_SHIPMENT = pending (KHÔNG để processing kẻo seller tưởng
            // đã arrange). PRE_ORDER có thể ở ON_HOLD lâu — vẫn pull về để seller chuẩn bị.
            'ON_HOLD' => 'pending',
            // AWAITING_SHIPMENT = đã xác nhận, CHƯA in/arrange phiếu giao hàng ⇒ tab "Chờ xử lý".
            'AWAITING_SHIPMENT' => 'pending',
            'PARTIALLY_SHIPPING' => 'processing',
            // AWAITING_COLLECTION = đã in/arrange phiếu (TikTok "đang chờ lấy hàng") ⇒ tab "Đang xử lý"
            // (xử lý nội bộ: gói + quét). Chỉ chuyển sang "Chờ bàn giao" bằng thao tác nội bộ của ta. SPEC 0013.
            'AWAITING_COLLECTION' => 'processing',
            'IN_TRANSIT' => 'shipped',
            'DELIVERED' => 'delivered',
            'COMPLETED' => 'completed',
            // List/Detail trả "CANCELLED"; webhook order_status trả "CANCEL" (doc 1-order-status-change).
            // Khai báo cả hai để raw_status nào cũng map tường minh (không phụ thuộc fallback chuỗi).
            'CANCELLED' => 'cancelled',
            'CANCEL' => 'cancelled',
        ],

        // TikTok webhook "type" (integer) -> normalized WebhookEventDTO type.
        // Unknown types fall back to "unknown" and are recorded as ignored.
        // NOTE: numbers here are best-effort and MUST be verified against the
        // Partner API webhook docs before relying on event-type routing — order
        // events are always re-fetched via fetchOrderDetail, polling is the safety net.
        // Đối chiếu tài liệu Partner (tailieuapi.../tiktok/docv2_page_<type>-*.md).
        // LƯU Ý: type tin nhắn CS 13 (new-conversation) / 14 (new-message) / 33
        // (new-message-listener) KHÔNG được map ở đây — chúng đã được
        // TikTokWebhookController demux sang pipeline messaging trước khi tới map này.
        // (Trước đây map nhầm 13→return_update, 14→shop_deauthorized ⇒ tin buyer revoke shop.)
        'webhook_event_types' => [
            1 => 'order_status_update',   // (1) order-status-change
            2 => 'return_update',         // reverse / return status change
            3 => 'order_status_update',   // (3) recipient-address-update -> re-fetch the order
            4 => 'order_status_update',   // (4) package-update -> re-fetch the order
            5 => 'product_update',        // (5) product-status-change
            6 => 'shop_deauthorized',     // (6) seller-deauthorization
        ],

        // Type push TikTok coi là tin nhắn CS (Webchat) — demux ở TikTokWebhookController
        // sang pipeline messaging (tiktok_chat) thay vì pipeline đơn hàng. Đối chiếu docs:
        // 13 = new-conversation, 14 = new-message, 33 = new-message-listener.
        'chat_push_types' => array_values(array_filter(array_map(
            'intval',
            explode(',', (string) env('TIKTOK_CHAT_PUSH_TYPES', '13,14,33'))
        ))),

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
            // Pack đơn JIT (Seller Fulfillment): API CHÍNH THỨC là `/order/fulfill/pack` (packReq.pack_order_list),
            // KHÔNG phải `/order/pack` (legacy — trả "20 Invalid Order Item ID"). Xem lazada doc fulfill/pack.
            'order_pack' => env('LAZADA_ORDER_PACK_PATH', '/order/fulfill/pack'),
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
            // After-sales (SPEC 0025) — reverse order. Field/path đối chiếu doc reverse; verify sandbox.
            'reverse_list' => env('LAZADA_REVERSE_LIST_PATH', '/reverse/getreverseordersforseller'),
            'reverse_cancel_decide' => env('LAZADA_REVERSE_CANCEL_DECIDE_PATH', '/order/reverse/cancel/seller/decide'),
            'reverse_refund_decide' => env('LAZADA_REVERSE_REFUND_DECIDE_PATH', '/order/reverse/onlyrefund/seller/decide'),
        ],
        // Khổ tem AWB khi Lazada trả HTML và HTML thiếu `@page { size }` — dùng để Gotenberg render đúng khổ
        // (preferCssPageSize), tránh rơi về A4 (thừa vùng trắng). Mặc định 10×15cm. Đổi nếu shop dùng khổ khác.
        'label_page_size' => env('LAZADA_LABEL_PAGE_SIZE', '100mm 150mm'),
        // Hoàn & Hủy (SPEC 0025). Tắt bằng INTEGRATIONS_LAZADA_RETURNS=false.
        'returns_enabled' => (bool) env('INTEGRATIONS_LAZADA_RETURNS', true),
        'return_status_map' => [
            'REQUEST_INITIATE' => 'requested',
            'SELLER_AGREE_RETURN' => 'approved',
            'BUYER_SHIP' => 'processing',
            'RETURNING' => 'processing',
            'REFUND_SUCCESS' => 'completed',
            'REVERSE_SUCCESS' => 'completed',
            'RETURN_CANCELED' => 'cancelled',
            'SELLER_REJECT_RETURN' => 'rejected',
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
        // shipping_allocate_type cho /order/fulfill/pack: `TFS` = local store (Lazada tự gán 3PL, KHÔNG truyền
        // shipment_provider_code) | `NTFS` = cross-border (BẮT BUỘC shipment_provider_code). Lấy giá trị hợp lệ
        // qua /shipment/providers/get. Đa số shop VN local ⇒ TFS.
        'shipping_allocate_type' => env('LAZADA_SHIPPING_ALLOCATE_TYPE', 'TFS'),
        // (Tuỳ chọn) Tên `shipment_provider` mặc định — nếu set, bỏ qua bước resolve qua
        // `/shipment/providers/get`. Hữu ích khi shop chỉ dùng 1 ĐVVC cố định (ví dụ "Lazada Express VN").
        'default_shipment_provider' => env('LAZADA_DEFAULT_SHIPMENT_PROVIDER'),
        // DEPRECATED — giữ làm legacy alias để env cũ không vỡ. Mode mới = `fulfillment_mode='auto'`.
        'fulfillment_auto_pack' => (bool) env('LAZADA_FULFILLMENT_AUTO_PACK', true),
        // Phase 6.2 — kéo đối soát. Bật mặc định. Tắt bằng INTEGRATIONS_LAZADA_FINANCE=false.
        // Lưu ý: app Lazada vẫn cần quyền nhóm Finance trên Open Platform mới gọi được API.
        'finance_enabled' => (bool) env('INTEGRATIONS_LAZADA_FINANCE', true),

        // Lazada raw (item-level + order-level) status → StandardOrderStatus. Source of truth duy nhất;
        // xem docs/03-domain/order-status-state-machine.md §4 + `lazada_order.md` (chuẩn hoá theo Lazada
        // Support 2026-05-14): 3 tab "công việc" của app khớp với 3 trạng thái Lazada như sau —
        //   "Chờ xử lý"     ← Lazada **paid** (order-level) / `pending`/`topack` (item-level)
        //   "Đang xử lý"    ← Lazada **packed**  (sau /order/fulfill/pack — seller đã gói, có tracking_number)
        //   "Chờ bàn giao"  ← Lazada **ready_to_ship** (sau /order/rts — chờ 3PL tới lấy hàng)
        // KHÔNG dùng ready_to_ship cho SOF/DBS (Lazada không trả status đó cho 2 type này).
        'status_map' => [
            'unpaid' => 'unpaid',
            // pending (item) | paid (order) | topack = chưa pack ⇒ "Chờ xử lý"
            'pending' => 'pending',
            'topack' => 'pending',
            'paid' => 'pending',
            // packed = đã /order/fulfill/pack, có tracking_number nhưng chưa /order/rts ⇒ "Đang xử lý" (chờ user
            // bấm "Đã gói & sẵn sàng bàn giao" để app gọi /order/rts đẩy lên Lazada).
            'packed' => 'processing',
            // ready_to_ship = đã /order/rts, Lazada chờ 3PL pickup ⇒ "Chờ bàn giao". `toship` là alias.
            'ready_to_ship' => 'ready_to_ship',
            'ready_to_ship_pending' => 'ready_to_ship',
            'toship' => 'ready_to_ship',
            'shipped' => 'shipped',
            'shipped_back' => 'returning',
            'shipped_back_failed' => 'returning',
            'delivered' => 'delivered',
            'confirmed' => 'completed',
            'failed' => 'delivery_failed',
            'failed_delivered' => 'delivery_failed',
            'lost' => 'delivery_failed',
            'damaged' => 'delivery_failed',
            'returned' => 'returned_refunded',
            'return_to_seller' => 'returning',
            'rtm_init' => 'returning',
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

    /*
    |--------------------------------------------------------------------------
    | Shopee Open Platform API v2 — see docs/04-channels/shopee.md, SPEC 0020
    |--------------------------------------------------------------------------
    |
    | Sandbox vs production = config: base_url points at sandbox/prod host.
    | partner_key is a secret — never log it. INTEGRATIONS_CHANNELS does NOT
    | include 'shopee' by default; enable only after sandbox verification.
    | This is the ONLY place Shopee-specific config / status & event maps live.
    |
    */
    'shopee' => [
        'partner_id' => env('SHOPEE_PARTNER_ID'),            // int
        'partner_key' => env('SHOPEE_PARTNER_KEY'),           // bí mật — ký HMAC request API
        // Shopee Push Mechanism cấp "Push Partner Key" RIÊNG (khác partner_key) để ký webhook push.
        // Để trống ⇒ verify push fallback về partner_key (khi sàn dùng chung 1 key). Bí mật.
        'push_partner_key' => env('SHOPEE_PUSH_PARTNER_KEY'),
        'sandbox' => (bool) env('SHOPEE_SANDBOX', true),
        // base_url KHÔNG cấu hình ở đây (đã bỏ env SHOPEE_API_BASE_URL): ShopeeClient::baseUrl() tự switch theo
        // cờ `sandbox` ĐÃ RESOLVE (env SHOPEE_SANDBOX + đè bằng DB system_setting marketplace.shopee.sandbox).
        // Sandbox (VN/Global): https://openplatform.sandbox.test-stable.shopee.sg · Production: https://partner.shopeemobile.com.
        // (CN sandbox: ...shopee.cn). KHÔNG dùng partner.test-stable.shopeemobile.com (sai). Xem shopee_docs/02-*.md.
        'redirect_uri' => env('SHOPEE_REDIRECT_URI'),          // default url('/oauth/shopee/callback')
        'push_url' => env('SHOPEE_PUSH_URL'),               // default url('/webhook/shopee') — để verify chữ ký push
        'http' => ['timeout' => (int) env('SHOPEE_HTTP_TIMEOUT', 20), 'retries' => (int) env('SHOPEE_HTTP_RETRIES', 2), 'retry_sleep_ms' => (int) env('SHOPEE_HTTP_RETRY_SLEEP_MS', 500)],
        'webhook_verify_mode' => env('SHOPEE_WEBHOOK_VERIFY_MODE', 'strict'),
        'order_window_days' => 15,                            // max get_order_list window
        'fulfillment_enabled' => (bool) env('INTEGRATIONS_SHOPEE_FULFILLMENT', true),
        'fulfillment_mode' => env('SHOPEE_FULFILLMENT_MODE', 'auto'),  // 'auto' | 'refetch_only'
        'ship_method' => env('SHOPEE_SHIP_METHOD', 'auto'),  // 'auto' | 'dropoff' | 'pickup' — exact selection verify trên sandbox
        // Trạng thái sàn KHÔNG cho "Chuẩn bị hàng" — chặn SỚM + báo tiếng Việt rõ ràng, KHÔNG gọi API rồi lỗi
        // khó hiểu (vd đơn UNPAID gọi get_shipping_parameter → error_param). UNPAID = chưa thanh toán;
        // IN_CANCEL = buyer đang xin huỷ. CSV; đổi bằng SHOPEE_UNFULFILLABLE_RAW_STATUSES.
        'unfulfillable_raw_statuses' => array_values(array_filter(array_map('trim', explode(',', (string) env('SHOPEE_UNFULFILLABLE_RAW_STATUSES', 'UNPAID,IN_CANCEL'))))),
        // Phase 6.2 — kéo đối soát. Bật mặc định. Tắt bằng INTEGRATIONS_SHOPEE_FINANCE=false.
        'finance_enabled' => (bool) env('INTEGRATIONS_SHOPEE_FINANCE', true),
        'endpoints' => [
            'auth_partner' => '/api/v2/shop/auth_partner',
            'token_get' => '/api/v2/auth/token/get',
            'token_refresh' => '/api/v2/auth/access_token/get',
            'shop_info' => '/api/v2/shop/get_shop_info',
            'order_list' => '/api/v2/order/get_order_list',
            'order_detail' => '/api/v2/order/get_order_detail',
            'shipping_parameter' => '/api/v2/logistics/get_shipping_parameter',
            'ship_order' => '/api/v2/logistics/ship_order',
            'tracking_number' => '/api/v2/logistics/get_tracking_number',
            'document_parameter' => '/api/v2/logistics/get_shipping_document_parameter',
            'create_document' => '/api/v2/logistics/create_shipping_document',
            'get_document_result' => '/api/v2/logistics/get_shipping_document_result',
            'download_document' => '/api/v2/logistics/download_shipping_document',
            'item_list' => '/api/v2/product/get_item_list',
            'item_base_info' => '/api/v2/product/get_item_base_info',
            'model_list' => '/api/v2/product/get_model_list',
            'update_stock' => '/api/v2/product/update_stock',
            'escrow_detail' => '/api/v2/payment/get_escrow_detail',
            'escrow_list' => '/api/v2/payment/get_escrow_list',
            'send_message' => '/api/v2/sellerchat/send_message',
            // Seller Chat polling (backfill + lưới an toàn cho webhook code 10). Endpoint cộng đồng —
            // verify sandbox (shape get_* không có trong tài liệu chính thức Shopee).
            'conversation_list' => '/api/v2/sellerchat/get_conversation_list',
            'get_message' => '/api/v2/sellerchat/get_message',
            // After-sales (SPEC 0025) — doc 227-return-refund-management.
            'return_list' => '/api/v2/returns/get_return_list',
            'return_detail' => '/api/v2/returns/get_return_detail',
            'return_confirm' => '/api/v2/returns/confirm',
            'return_dispute' => '/api/v2/returns/dispute',
            'handle_cancellation' => '/api/v2/order/handle_buyer_cancellation',
        ],
        // Hoàn & Hủy (SPEC 0025). Tắt bằng INTEGRATIONS_SHOPEE_RETURNS=false. Field/endpoint cần đối chiếu sandbox.
        'returns_enabled' => (bool) env('INTEGRATIONS_SHOPEE_RETURNS', true),
        'return_status_map' => [
            'REQUESTED' => 'requested',
            'ACCEPTED' => 'approved',
            'PROCESSING' => 'processing',
            'JUDGING' => 'processing',
            'CLOSED' => 'completed',
        ],
        'document_type' => env('SHOPEE_DOCUMENT_TYPE', 'NORMAL_AIR_WAYBILL'),
        'document_poll_attempts' => (int) env('SHOPEE_DOC_POLL_ATTEMPTS', 6),
        'document_poll_sleep_ms' => (int) env('SHOPEE_DOC_POLL_SLEEP_MS', 1000),
        // Upload video listing (media_space) — chờ transcode xong mới dùng được trong add_item.
        'video_poll_attempts' => (int) env('SHOPEE_VIDEO_POLL_ATTEMPTS', 15),
        'video_poll_sleep_ms' => (int) env('SHOPEE_VIDEO_POLL_SLEEP_MS', 3000),
        'status_map' => [
            'UNPAID' => 'unpaid',
            'READY_TO_SHIP' => 'pending',
            'PROCESSED' => 'processing',
            'RETRY_SHIP' => 'processing',
            'SHIPPED' => 'shipped',
            'TO_CONFIRM_RECEIVE' => 'delivered',
            'COMPLETED' => 'completed',
            'IN_CANCEL' => 'processing',
            'CANCELLED' => 'cancelled',
            'TO_RETURN' => 'returning',
        ],
        // Mã push CHÍNH THỨC (open.shopee.com/developer-guide/18 — xem shopee_docs/03-push-mechanism-webhook.md).
        'webhook_event_types' => [
            1 => 'unknown',              // Shop Authorization GRANTED (KHÔNG phải deauth)
            2 => 'shop_deauthorized',    // Shop Authorization Canceled (đây mới là huỷ quyền)
            3 => 'order_status_update',  // Order Status Update (data.ordersn, data.status)
            4 => 'order_status_update',  // Order TrackingNo Update → re-fetch đơn
            6 => 'product_update',       // Banned Item
            12 => 'unknown',              // Auth Expiry warning (7 ngày trước) — KHÔNG revoke sớm
            15 => 'unknown',              // Shipping Document Status (READY/FAILED)
            16 => 'shop_penalty_update',  // violation_item_push — listing bị BANNED/deboost
            28 => 'shop_penalty_update',  // shop_penalty_update_push — điểm phạt/bậc phạt thay đổi ("sao quả tạ")
            // 5,7,8,9,10,11,13: marketing/product/chat — chưa dùng cho connector đơn.
        ],
        // Push code coi là tin chat (Webchat). Demux ở ShopeeWebhookController: code này → pipeline messaging.
        'chat_push_codes' => [10],
    ],

    /*
    |--------------------------------------------------------------------------
    | Chrome Extension login (EXTENSION_OAUTH_LOGIN_CONTRACT)
    |--------------------------------------------------------------------------
    |
    | `/extension/connect` chỉ cho callback `https://<id>.chromiumapp.org/` (Chrome
    | Identity). Bản dev/test có ID khác có thể thêm URI cố định qua CSV — KHÔNG nới
    | lỏng regex chung ở controller.
    |
    */
    'extension' => [
        'dev_redirect_uris' => array_values(array_filter(array_map('trim', explode(',', (string) env('EXTENSION_DEV_REDIRECT_URIS', ''))))),
    ],

];
