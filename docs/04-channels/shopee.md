# Tích hợp Shopee (Việt Nam)

**Status:** ✅ Implemented (faithful theo Shopee Open Platform v2 docs) — **chờ verify sandbox trước khi dùng prod**. · **Cập nhật:** 2026-05-20

> Connector code: `app/Integrations/Channels/Shopee/` · Spec thiết kế: `docs/superpowers/specs/2026-05-20-shopee-channel-connector-design.md`
>
> **Lưu ý triển khai:** `INTEGRATIONS_CHANNELS` chưa bật `shopee` mặc định — thêm vào sau khi verify sandbox. Finance (`fetchSettlements`) bị gated bởi `finance_enabled` (mặc định `false` trong config).

## 1. Đăng ký
Shopee Open Platform → app → `partner_id`, `partner_key`. Region site = **VN**. Đặt `SHOPEE_PARTNER_ID`, `SHOPEE_PARTNER_KEY`, `SHOPEE_SANDBOX=true` trong `.env` (xem `.env.example`).

## 2. Xác thực & ký
OAuth "shop authorization" (redirect → `code` → `access_token` + `refresh_token` theo `shop_id`); ký request bằng HMAC-SHA256:
- **Public API** (token get/refresh, auth_partner): base = `partner_id` + `api_path` + `timestamp`
- **Shop API**: base = `partner_id` + `api_path` + `timestamp` + `access_token` + `shop_id`

Ký bởi `ShopeeSigner::signPublic()` / `ShopeeSigner::signShop()`. Token refresh trước hạn (`TokenRefresher` truyền `shop_id` qua `$context`).

## 3. Endpoint
`order` (`get_order_list` theo `update_time` — window tối đa 15 ngày, dùng cursor, `get_order_detail`), `logistics` (`get_shipping_parameter`, `ship_order`, `get_tracking_number`, `create_shipping_document`, `download_shipping_document`), `product` (`get_item_list`, `get_item_base_info`, `get_model_list`, `update_stock`), `shop` (`get_shop_info`), `payment` (`get_escrow_detail`/`get_escrow_list` cho đối soát), `push` (cấu hình webhook một lần trong app console — không subscribe per-shop).

## 4. Webhook
URL `/webhook/shopee`; verify bằng `Authorization` header = `HMAC-SHA256(push_key, url . '|' . rawBody)` (hex thường) — đúng SDK chính thức (`Shopee\SignatureGenerator`: `$url . '|' . $body`). Xử lý bởi `ShopeeWebhookVerifier`. **`push_key`** = "**Push Partner Key**" (Shopee console → Push Mechanism) — sàn cấp RIÊNG, **khác `partner_key` của API**. Đặt `SHOPEE_PUSH_PARTNER_KEY` (hoặc `/admin/system-settings` → `marketplace.shopee.push_partner_key`); để trống ⇒ fallback `partner_key`.
- **`url` PHẢI khớp đúng URL công khai mà Shopee ký** = URL callback đã khai trong console. Sau **reverse proxy**, URL Laravel tự dựng (`$request->getUri()`) có thể lệch scheme (`http` nội bộ vs `https` công khai) ⇒ chữ ký lệch. Verifier thử **nhiều ứng viên URL**: `SHOPEE_PUSH_URL` cấu hình → `getUri()` của request → bản không-query → biến thể `https`. Khớp 1 cái là PASS.
  > ⚠️ **2026-06-03 — fix `shopee.webhook.signature_mismatch_but_accepted`:** trước đây chỉ dựng chữ ký từ 1 URL (`push_url` ?? `url('/webhook/shopee')`); sau proxy thường lệch ⇒ luôn mismatch (lenient nên vẫn ack nhưng `signature_ok=false`). **Cách xử lý vận hành:** đặt `SHOPEE_PUSH_URL` = chính xác URL callback đã khai ở Shopee console; kiểm log `shopee.webhook.signature_invalid`/`_mismatch_but_accepted` (field `request_url` + `urls_tried` + `push_partner_key_set`) để biết URL/key nào đang lệch. Strict mode sai chữ ký ⇒ 401 (Shopee retry). Map `code` push (CHÍNH THỨC — `open.shopee.com/developer-guide/18`, xem `shopee_docs/03-*.md`): `1`=auth GRANTED→`unknown`, `2`→`shop_deauthorized`, `3`=order status→`order_status_update`, `4`=tracking no→`order_status_update`, `6`=banned item→`product_update`, `12`=auth expiry→`unknown`, `15`=shipping doc→`unknown`. Host API: prod `partner.shopeemobile.com`, sandbox VN/Global `openplatform.sandbox.test-stable.shopee.sg` (KHÔNG dùng `partner.test-stable.shopeemobile.com`).

## 5. Mapping trạng thái
Xem `docs/03-domain/order-status-state-machine.md` §4. Bảng đầy đủ trong `ShopeeStatusMap` + config `integrations.shopee.status_map`:

| Raw status Shopee | Chuẩn |
|---|---|
| `UNPAID` | `unpaid` |
| `READY_TO_SHIP` | `pending` *(chưa in/arrange phiếu — SPEC 0013)* |
| `PROCESSED` | `processing` *(đã in/arrange phiếu — SPEC 0013)* |
| `RETRY_SHIP` | `processing` |
| `SHIPPED` | `shipped` |
| `TO_CONFIRM_RECEIVE` | `delivered` |
| `IN_CANCEL` | `processing` |
| `CANCELLED` | `cancelled` |
| `TO_RETURN` | `returning` |
| `COMPLETED` | `completed` |

## 6. Mã phí (đối soát)
Từ `escrow_detail.order_income` → map sang `feeType` chuẩn: `buyer_total_amount`→`revenue`, `commission_fee`→`commission`, `seller_transaction_fee`→`payment_fee`, `service_fee`→`other`, `actual_shipping_fee`→`shipping_fee`, `shopee_shipping_rebate`→`shipping_subsidy`, `voucher_from_seller`→`voucher_seller`, `voucher_from_shopee`→`voucher_platform`. Finance gated bởi `finance_enabled`.

## 7. Lưu ý
- Đơn nhiều kiện (`package_list`); SPX (Shopee Express) là ĐVVC mặc định nhiều đơn.
- `package_number` chỉ dùng cho đơn ĐÃ tách (split, ≥2 kiện). `get_order_detail.package_list` vẫn trả `package_number` cho cả đơn 1 kiện (chưa tách), nhưng `ship_order` của đơn chưa tách **không được** gửi `package_number` (lỗi `logistics.ship_order_not_need_pacakge_number`) — `arrangeShipment` chỉ đính `package_number` khi `count(packages) > 1`.
- COD (`cod: true`); document API trả PDF (download theo `package_number`). Luồng tem Shopee-generated chính thức 4 bước: `get_shipping_document_parameter` → `create_shipping_document` → `get_shipping_document_result` → `download_shipping_document` (connector hiện bỏ bước 1, hardcode `NORMAL_AIR_WAYBILL`).
- **`create_shipping_document` → `common.batch_api_all_failed`** (fix 2026-06-03): lý do thật nằm trong `result_list[].fail_error/fail_message`. `ShopeeClient` nay giữ envelope trong `ShopeeApiException::$response`; `ShopeeConnector::batchFailReason()` bóc ra để báo rõ (vd "Package is not ready for document creation") thay vì lỗi batch chung chung. Nếu log mới chỉ ra sai `shipping_document_type` ⇒ bổ sung bước `get_shipping_document_parameter` lấy đúng type (TODO).
- `fetchOrders` window tối đa 15 ngày — cursor encode `"windowStart:innerCursor"` để chia nhỏ tự động.
- `fetchReturns` (`get_return_list`) cũng giới hạn `create_time_from..create_time_to` ≤ 15 ngày — windowed y hệt `fetchOrders`, cursor encode `"windowStart:pageNo"` (page_no 0-based). `updatedFrom` xa hơn 15 ngày sẽ tự chia cửa sổ; nếu không windowed sẽ lỗi `error_param` "period ... must not more than 15 days".
- `unprocessedRawStatuses()` = `['READY_TO_SHIP']` (đơn sàn `READY_TO_SHIP` cần xử lý fulfillment).
- **`arrangeShipment` precheck `order_status`** (fix `get_shipping_parameter [error_param] ...only...when package is ready to be shipped`, 2026-06-03): Shopee chỉ cho `get_shipping_parameter`/`ship_order` khi `order_status = READY_TO_SHIP`. Connector đọc `order_status` (get_order_detail) trước khi ship:
  - `PROCESSED`/`RETRY_SHIP`/`SHIPPED`/`TO_CONFIRM_RECEIVE`/`COMPLETED` (đã arrange trước đó, có thể chưa kịp cấp tracking vì Shopee cấp async) ⇒ KHÔNG ship lại, trả `raw_status` thật (caller lấy tracking/label sau).
  - `UNPAID`/`IN_CANCEL`/`CANCELLED`/`TO_RETURN` ⇒ `ShopeeApiException` báo rõ "chưa ở READY_TO_SHIP".
  - đọc detail lỗi/rỗng ⇒ degrade an toàn (giữ luồng cũ). Idempotency cũ (get_tracking_number có mã ⇒ trả luôn) vẫn chạy trước precheck.
