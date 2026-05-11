# Tích hợp Lazada (Việt Nam)

**Status:** ⏳ Chờ cấp API — STUB. Bắt đầu Phase 4. Nộp hồ sơ **Lazada Open Platform** ngay tuần đầu Phase 0 (đường găng). · **Cập nhật:** 2026-05-11

> Khi có credentials, viết theo cấu trúc của `tiktok-shop.md` (mục 1→7). Connector implement `ChannelConnector`, trả DTO chuẩn — không đụng core.

## Cần điền khi bắt đầu
1. **Đăng ký:** Lazada Open Platform → app → `app_key`, `app_secret`. Site = **VN** (`lazada.vn`). Gateway: `https://api.lazada.vn/rest`.
2. **Xác thực & ký:** OAuth (`/oauth/authorize` → `code` → `/auth/token/create` → `access_token` + `refresh_token`); ký request bằng **HMAC-SHA256** (sort tham số, nối `app_secret` + apiName + params, theo quy tắc Lazada); refresh qua `/auth/token/refresh`.
3. **Endpoint:** `/orders/get` (theo `update_after`), `/order/get` & `/order/items/get`, `/order/document/get` (AWB/invoice/packing list — trả base64 PDF), `/logistic/...` (RTS - ready to ship, tracking), `/products/get`, `/product/price_quantity/update`, `/products/category/tree/get` & `/category/attributes/get` (mass listing), `/finance/payout/status/get` & `/finance/transaction/details/get` (đối soát), `/reverse/...` (trả hàng).
4. **Webhook:** URL `/webhook/lazada`; verify bằng `app_secret` (chữ ký trên payload). Map message type (order, RTS, tracking updated, ...) → `WebhookEventDTO.type`.
5. **Mapping trạng thái:** `pending`, `packed`, `ready_to_ship`, `shipped`, `delivered`, `failed`, `canceled`, `returned`, `lost`, `damaged` → tập chuẩn (điền vào `LazadaStatusMap` + `03-domain/order-status-state-machine.md` §4).
6. **Mã phí (đối soát):** từ transaction details → map sang `feeType` chuẩn (commission, payment fee, shipping fee, lazada bonus/voucher seller, ...).
7. **Lưu ý:** `order/document/get` trả base64 → decode lưu MinIO; đơn có thể nhiều item-status khác nhau; RTS theo package; COD.
