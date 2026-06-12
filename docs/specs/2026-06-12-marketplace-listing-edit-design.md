# Sửa sản phẩm đã có trên sàn (marketplace listing edit)

- Date: 2026-06-12
- Status: Implemented slice
- Scope: màn "Sản phẩm đã có trên sàn" trong nhóm Đăng bán sàn — đồng bộ, hiển thị, và sửa (giá / tiêu đề / mô tả / ảnh) đẩy thẳng lên sàn.

## Mục tiêu

Seller xem các sản phẩm THẬT đang có trên từng gian hàng đã kết nối, sửa nội dung và đẩy thay đổi ngược lại sàn — không rời app. Tồn kho KHÔNG nằm trong luồng này: tồn đẩy lên sàn theo master SKU qua `sku_mappings` (giữ một nguồn sự thật).

## Nguồn dữ liệu

`ChannelListing` (sản phẩm/biến thể trên shop, kéo về bằng `FetchChannelListings` — capability `listings.fetch`). Một sản phẩm sàn = nhiều dòng `ChannelListing` cùng `external_product_id` (mỗi biến thể một dòng).

## Luồng

- **Đồng bộ:** `POST /channel-listings/sync` (đã có) → `FetchChannelListings` cho mọi shop active hỗ trợ `listings.fetch`. FE poll tới khi xong.
- **Hiển thị:** `GET /channel-listings` (đã có) — ảnh, tiêu đề, gian hàng, giá, tồn sàn, trạng thái đồng bộ.
- **Sửa:** mở form → `GET /channel-listings/{id}/marketplace-detail` đọc nội dung đầy đủ từ sàn → sửa → `PUT /channel-listings/{id}/marketplace` đẩy lên sàn → mirror local + đồng bộ lại.

## Tầng tích hợp (Connector)

Mở rộng `ProductPublishingConnector` (cốt lõi không biết tên sàn — gọi qua `PublisherRegistry`):

- `getListingDetail(auth, externalProductId): ListingDetailDTO` — title, description, images[], skus[{external_sku_id, seller_sku, price(VND)}].
- `updateListing(auth, externalProductId, ListingEditDTO): ListingResultDTO` — đẩy field khác null. Ảnh là URL nguồn; connector tự upload (tái dùng `uploadMedia`).

Endpoint chính thức theo từng sàn (đã đọc tài liệu trong repo + Meta docs):

| | Lazada | Shopee v2 | TikTok Shop |
|---|---|---|---|
| Detail | `/product/item/get` | `get_item_base_info` + `get_model_list` | `GET /product/202309/products/{id}` |
| Sửa tiêu đề/mô tả/ảnh | `/product/update` | `update_item` | `partial_edit` |
| Sửa giá | `/product/price_quantity/update` | `update_price` | `prices/update` |
| Ảnh tham chiếu bằng | URL (image/migrate) | `image_id` (upload trước) | `uri` (upload trước) |

Money = integer VND. Giá là per-SKU (Lazada SkuId, Shopee model_id, TikTok sku id). Sản phẩm không biến thể: Shopee bỏ `model_id`.

## Quy tắc

- Tồn kho không sửa ở luồng này (đẩy theo master SKU).
- Chỉ gửi field đã thay đổi (FE diff với detail) → tránh ghi đè / upload lại ảnh thừa.
- `external_product_id` rỗng hoặc sàn chưa đăng ký publisher ⇒ `422`.
- Sau khi đẩy thành công, mirror các field đã đổi (title/ảnh đầu/giá theo SKU) lên mọi dòng `ChannelListing` cùng `external_product_id`, set `sync_status=ok`, `last_pushed_at=now`.
- **Sandbox-gated:** payload ghi lên sàn theo tài liệu, cần xác minh sandbox từng sàn trước khi bật prod (cùng mức như luồng publish hiện tại).

## API

- `GET /api/v1/channel-listings/{id}/marketplace-detail` (`products.manage`)
- `PUT /api/v1/channel-listings/{id}/marketplace` (`products.manage`) — `{ title?, description?, images?:[url], prices?:[{external_sku_id, price}] }`

## UI

Trang `/marketplace/on-channel` ("Đã có trên sàn"): nút **Đồng bộ sản phẩm**, lọc theo gian hàng + tìm kiếm, bảng listing, nút **Sửa trên sàn** mở `MarketplaceEditDrawer` (tiêu đề, mô tả, ảnh, giá theo SKU). Tồn hiển thị read-only kèm ghi chú đẩy theo SKU.
