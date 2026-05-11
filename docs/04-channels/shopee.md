# Tích hợp Shopee (Việt Nam)

**Status:** ⏳ Chờ cấp API — STUB. Bắt đầu Phase 4. Nộp hồ sơ **Shopee Open Platform** ngay tuần đầu Phase 0 (đường găng). · **Cập nhật:** 2026-05-11

> Khi có credentials, viết theo đúng cấu trúc của `tiktok-shop.md` (mục 1→7). Connector implement `ChannelConnector`, trả DTO chuẩn — **không** đụng core.

## Cần điền khi bắt đầu
1. **Đăng ký:** Shopee Open Platform → app → `partner_id`, `partner_key`. Region site = **VN**.
2. **Xác thực & ký:** OAuth "shop authorization" (redirect → `code` → `access_token` + `refresh_token` theo `shop_id`); ký request bằng HMAC-SHA256 (`partner_id` + path + timestamp + access_token + shop_id + body, theo quy tắc Shopee). Token refresh trước hạn.
3. **Endpoint:** `order` (`get_order_list` theo `update_time`, `get_order_detail`), `logistics` (`get_shipping_parameter`, `ship_order`, `get_tracking_number`, `create_shipping_document`, `download_shipping_document`), `product` (`get_item_list`, `get_model_list`, `update_stock`, `update_price`), `shop`/`merchant` info, `returns` (`get_return_list`), `payment` (`get_escrow_detail`/`get_payout` cho đối soát), `push` (cấu hình webhook).
4. **Webhook:** URL `/webhook/shopee`; verify bằng `partner_key` + raw body (header chữ ký Shopee). Map các `code` push (order status update, tracking no, banned/deauth shop...) → `WebhookEventDTO.type`.
5. **Mapping trạng thái:** `UNPAID`, `READY_TO_SHIP`, `PROCESSED`, `RETRY_SHIP`, `SHIPPED`, `TO_CONFIRM_RECEIVE`, `IN_CANCEL`, `CANCELLED`, `TO_RETURN`, `COMPLETED` → tập chuẩn (điền vào `ShopeeStatusMap` + `03-domain/order-status-state-machine.md` §4).
6. **Mã phí (đối soát):** từ `escrow_detail` → map sang `feeType` chuẩn (commission, service fee, transaction fee, shipping fee/subsidy, voucher seller/Shopee, ...).
7. **Lưu ý:** đơn nhiều kiện; SPX (Shopee Express) là ĐVVC mặc định nhiều đơn; COD; document API trả PDF (download theo `package_number`).
