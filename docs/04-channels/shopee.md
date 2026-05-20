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
URL `/webhook/shopee`; verify bằng `Authorization` header = `HMAC-SHA256(partner_key, url|body)` — xử lý bởi `ShopeeWebhookVerifier`. Map `code` push: `1`→`shop_deauthorized`, `3`/`6`→`order_status_update`. Các code chưa map mặc định `unknown` (verify sandbox).

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
- COD (`cod: true`); document API trả PDF (download theo `package_number`).
- `fetchOrders` window tối đa 15 ngày — cursor encode `"windowStart:innerCursor"` để chia nhỏ tự động.
- `unprocessedRawStatuses()` = `['READY_TO_SHIP']` (đơn sàn `READY_TO_SHIP` cần xử lý fulfillment).
