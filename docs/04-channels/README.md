# Tích hợp sàn — Hợp đồng chung & DTO chuẩn

**Status:** Stable · **Cập nhật:** 2026-05-11

> Đọc `01-architecture/extensibility-rules.md` trước. File này định nghĩa **DTO chuẩn** mà mọi `ChannelConnector` phải nói; mỗi file con (`tiktok-shop.md`, `shopee.md`, `lazada.md`) đặc tả chi tiết riêng của từng sàn.

## 1. Quy tắc bất di bất dịch
1. **Core ↔ DTO chuẩn ↔ Connector.** Module nghiệp vụ chỉ thấy DTO chuẩn, không thấy JSON của sàn.
2. **Mọi cái riêng của một sàn** (ký request, version API, tên trường, mã trạng thái, mã phí, loại webhook event, định dạng địa chỉ...) sống trong `app/Integrations/Channels/<Sàn>/`.
3. **Không thêm `if ($provider === ...)` ở core.** Khác biệt giữa sàn ⇒ xử lý bằng: capability map (sàn hỗ trợ method nào), bảng mapping (status/fee/event), hoặc field nullable trong DTO chuẩn.
4. **Versioned client.** Mỗi sàn có lớp client theo version API của sàn (TikTok: v202309, v202407, ...); một adapter chuyển version → DTO chuẩn. Nâng version = thêm adapter, không vỡ core.
5. **Contract test bắt buộc** cho mỗi connector, dùng fixtures (response mẫu), chạy trong CI — đảm bảo connector luôn trả đúng DTO chuẩn dù API sàn đổi.
6. **Bí mật** (app_key/secret, partner_key, access/refresh token) — không log, mã hoá khi lưu, đọc từ config/secret manager.

## 2. DTO chuẩn (sườn — chốt cụ thể ở Phase 1)

```
TokenDTO        { accessToken, refreshToken?, expiresAt, refreshExpiresAt?, scope?, raw }
AuthContext     { channelAccountId, provider, externalShopId, accessToken, region='VN', extra{} }   // truyền vào mọi call
ShopInfoDTO     { externalShopId, name, region, sellerType?, raw }

OrderQuery      { updatedFrom?, updatedTo?, statuses?[], cursor?, pageSize }
Page<T>         { items: T[], nextCursor?, hasMore }

OrderDTO {
  externalOrderId, orderNumber?, source('tiktok'|'shopee'|'lazada'),
  rawStatus, paymentStatus?, sourceUpdatedAt, placedAt, paidAt?, shippedAt?, deliveredAt?, completedAt?, cancelledAt?, cancelReason?,
  buyer { name, phone?, email? },
  shippingAddress { fullName, phone, line1, ward?, district?, province, country='VN', zip?, note? },
  currency='VND',
  amounts { itemTotal, shippingFee, platformDiscount, sellerDiscount, tax, codAmount, grandTotal },
  items: OrderItemDTO[],
  packages?: { externalPackageId, trackingNo?, carrier?, status? }[],
  isCod, fulfillmentType?, raw
}
OrderItemDTO { externalItemId, externalProductId?, externalSkuId?, sellerSku?, name, variation?, quantity, unitPrice, discount?, image? }

ListingQuery    { updatedFrom?, status?, cursor?, pageSize }
ListingDTO      { externalProductId, externalSkuId, sellerSku?, title, price, channelStock, status, attributes{}, raw }
ListingDraftDTO { ... đăng bán đa sàn — chốt ở Phase 5 ... }

ArrangeShipmentDTO  { externalOrderId, packageIds?[], pickupOrDropoff, pickupAddress?, dimensions?, weight?, serviceCode? }
ShipmentDTO         { externalOrderId, externalPackageId?, trackingNo, carrier, status, labelReady, raw }
ShippingDocQuery    { externalOrderId|externalPackageId, type('label'|'invoice'|'packing'), format('PDF'|'A6'|'A4') }
BinaryFile          { filename, mime, bytes }
TrackingDTO         { trackingNo, status, events: { time, status, location?, note? }[] }

WebhookRequest      { headers, rawBody, query }
WebhookEventDTO     { provider, type('order_created'|'order_status_update'|'order_cancel'|'return_update'|'settlement_available'|'product_update'|'shop_deauthorized'|'data_deletion'|...), externalShopId?, externalOrderId?, externalIds{}, occurredAt?, raw }

SettlementLineDTO   { externalOrderId?, periodStart, periodEnd, feeType('commission'|'transaction_fee'|'shipping_fee_charged'|'shipping_fee_subsidy'|'affiliate_commission'|'platform_voucher'|'seller_voucher'|'adjustment'|'tax'|'other'), amount, raw }
ReturnDTO           { externalReturnId, externalOrderId, status, items, reason?, refundAmount?, raw }

StandardOrderStatus = enum { unpaid, pending, processing, ready_to_ship, shipped, delivered, completed, delivery_failed, returning, returned_refunded, cancelled }
```

> Mọi DTO giữ `raw` (payload gốc) để debug. Trường thiếu ở một sàn ⇒ `null`, không bịa.

## 3. Capability map (mỗi connector khai báo)
Ví dụ khung:
```php
public function capabilities(): array {
    return [
        'orders.fetch' => true, 'orders.webhook' => true, 'orders.confirm' => true,
        'shipping.arrange' => true, 'shipping.document' => true, 'shipping.tracking' => true,
        'listings.fetch' => true, 'listings.publish' => true, 'listings.updateStock' => true, 'listings.updatePrice' => true,
        'finance.settlements' => true, 'returns.fetch' => true,
    ];
}
```
Core gọi `$connector->supports('listings.publish')` trước khi dùng; thiếu ⇒ ẩn nút trên UI / trả lỗi rõ ràng. Không bao giờ hard-code "sàn này không có X" ở core.

## 4. Webhook & OAuth chung
- Một controller webhook chung (`WebhookController@handle`) cho mọi sàn; chỉ khác `XWebhookVerifier` và `parseWebhook()`. Xem `05-api/webhooks-and-oauth.md`.
- Một controller OAuth chung (`OAuthController@start` → trả authUrl; `@callback` → đổi token, lưu `channel_account`, đăng ký webhook, kích hoạt backfill). Mỗi connector cung cấp `buildAuthorizationUrl`, `exchangeCodeForToken`.

## 5. Danh sách connector & trạng thái
| Sàn | Code | Trạng thái | Ghi chú |
|---|---|---|---|
| TikTok Shop | `tiktok` | **Implemented (Phase 1)** — version API `202309` | Code: `app/Integrations/Channels/TikTok/`; chi tiết: [`tiktok-shop.md`](tiktok-shop.md), spec [`docs/specs/0001-tiktok-order-sync.md`](../specs/0001-tiktok-order-sync.md). Sandbox vs prod = config. Còn chờ kiểm thử với sandbox thật. |
| Shopee | `shopee` | Chờ cấp API (Phase 4) | Nộp hồ sơ Shopee Open Platform ngay tuần đầu. Route `/webhook/shopee` + `/oauth/shopee/callback` tồn tại nhưng connector chưa có ⇒ `404` cho tới khi làm. |
| Lazada | `lazada` | Chờ cấp API (Phase 4) | Nộp hồ sơ Lazada Open Platform ngay tuần đầu. (Như trên.) |
| Manual | `manual` | `ManualConnector` rỗng đã có (Phase 0); luồng tạo đơn tay = Phase 2 | Để code đối xử mọi nguồn đơn đồng nhất. |
