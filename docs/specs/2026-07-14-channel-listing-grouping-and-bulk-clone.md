# SPEC: Gộp nhóm biến thể theo sản phẩm + chọn hàng loạt sao chép sang gian hàng khác (trang "Sản phẩm đã có trên sàn")

- **Trạng thái:** Design
- **Module backend liên quan:** Products (`ChannelListingController`, `MarketplaceCloneService`)
- **Tác giả / Ngày:** lilyrisa · 2026-07-14
- **Liên quan:** SPEC 0003 (`channel_listings` — 1 hàng/biến thể, thiết kế gốc, không đổi), `docs/03-domain/inventory-and-sku-mapping.md`, `MarketplaceListingEditService` (đã dùng `external_product_id` để gộp khi sửa), `MarketplaceCloneService::cloneToShops()` (đã dùng `external_product_id` để sao chép cả sản phẩm từ 1 dòng biến thể bất kỳ).

## 1. Vấn đề & mục tiêu

Điều tra qua dữ liệu thật trên prod (tenant Enko Store) xác nhận: **dữ liệu `channel_listings` không sai** — mỗi sản phẩm nhiều biến thể trên sàn vẫn lưu đúng, dùng chung `external_product_id`. Vấn đề nằm ở trang "Sản phẩm đã có trên sàn" (`OnChannelPage.tsx`): bảng hiển thị **phẳng, 1 dòng/biến thể**, không gộp nhóm — khiến người dùng thấy như 1 sản phẩm bị tách thành nhiều sản phẩm riêng. Trang cũng chưa có checkbox chọn hàng loạt.

Mục tiêu:
1. Gộp hiển thị các biến thể cùng sản phẩm thành 1 dòng cha (mở rộng xem chi tiết từng biến thể).
2. Thêm checkbox ở **cấp sản phẩm** (không phải cấp biến thể) — khớp đúng bản chất thao tác "Sao chép sàn" vốn đã hoạt động ở cấp sản phẩm.
3. Nút hành động hàng loạt: **sao chép sang gian hàng khác** cho nhiều sản phẩm đã chọn cùng lúc.

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Endpoint mới `GET /channel-listings/grouped` — phân trang **theo sản phẩm** (không phải theo dòng biến thể), tái dùng bộ filter hiện có của `index()`.
  - Endpoint mới `POST /channel-listings/bulk-clone-to-shops` — sao chép nhiều sản phẩm cùng lúc, tái dùng `MarketplaceCloneService::cloneToShops()` có sẵn (không sửa), lỗi 1 sản phẩm không hỏng cả lô.
  - `OnChannelPage.tsx`: bảng cha-con (`expandable`), checkbox cấp sản phẩm, nút "Sao chép sang gian hàng khác" hàng loạt. Dời nút "Sửa trên sàn"/"Sao chép sàn" từ mỗi dòng biến thể lên dòng cha (1 lần/sản phẩm — cả 2 hành động vốn đã thao tác ở cấp sản phẩm, xác nhận qua code `MarketplaceCloneService`/`MarketplaceListingEditService`).
- **Ngoài (không đổi):**
  - **Không đổi** endpoint `GET /channel-listings` hiện có (phẳng, theo dòng biến thể) — đang dùng chung ở 4 nơi khác (`SkuPickerModal`, `InventoryPage`, `ChannelLinkModal`, và chính `OnChannelPage` trước khi đổi) — đổi shape sẽ vỡ các chỗ đó.
  - Không đổi schema `channel_listings` (SPEC 0003 vẫn đúng — 1 hàng/biến thể ở tầng lưu trữ là thiết kế đúng, chỉ tầng hiển thị cần gộp).
  - Không đổi `MarketplaceCloneService::cloneToShops()`/`MarketplaceListingEditService` — chỉ gọi lại, không sửa logic bên trong.
  - Không làm bulk cho "Sửa trên sàn" (chỉnh sửa hàng loạt) — phạm vi lần này chỉ sao chép hàng loạt, theo đúng yêu cầu.

## 3. Luồng chính

### 3.1 Backend — endpoint gộp nhóm phân trang theo sản phẩm

Tách phần dựng filter hiện có trong `ChannelListingController::index()` (gian hàng, `channel_account_ids`, `sync_status`, `mapped`, tìm kiếm `q`) thành method riêng `applyFilters(Request, PromotionService): Builder`, dùng chung cho cả `index()` (không đổi hành vi) và `grouped()` (mới).

**`GET /channel-listings/grouped`** — 2 bước:
1. Query nhóm: `SELECT channel_account_id, COALESCE(external_product_id, CAST(id AS VARCHAR)) AS group_key, MAX(id) AS sort_id, COUNT(*) AS variant_count` trên query đã áp filter, `GROUP BY channel_account_id, group_key`, `ORDER BY sort_id DESC`, **phân trang chính query nhóm này** (per_page = số SẢN PHẨM/trang, không phải số dòng).
2. Lấy toàn bộ dòng `channel_listings` thuộc các `(channel_account_id, group_key)` của trang đó (áp lại đúng filter để nhất quán — vd nếu đang lọc `mapped=false`, nhóm chỉ chứa các biến thể chưa map, không phải toàn bộ biến thể của sản phẩm).
3. Trả `{ data: [{ channel_account_id, external_product_id, title, image, variant_count, variants: [ChannelListingResource...] }], meta: { pagination: { page, per_page, total, total_pages } } }` — `total`/`total_pages` tính theo **số sản phẩm**.

Listing không có `external_product_id` (null) tự thành nhóm 1 phần tử (dùng `id` làm group key) — không mất/gộp nhầm với sản phẩm khác.

### 3.2 Backend — sao chép hàng loạt

**`MarketplaceCloneService::bulkCloneToShops(array $channelListingIds, array $targetShopIds): array`** (mới) — lặp gọi `cloneToShops($id, $targetShopIds)` có sẵn cho từng `channel_listing_id` (đại diện 1 sản phẩm), bọc `try/catch` từng phần tử — lỗi 1 sản phẩm (vd hết hạn token sàn nguồn, sản phẩm bị gỡ) không chặn các sản phẩm còn lại. Trả `list<array{channel_listing_id:int, ok:bool, results?:array, error?:string}>`.

**`POST /channel-listings/bulk-clone-to-shops`** → `ChannelListingController::bulkCloneToShops()`, validate `channel_listing_ids` (array, ≤50) + `channel_account_ids` (array, ≤50, giống endpoint đơn hiện có), quyền `products.manage` (giống `cloneToShops` đơn).

### 3.3 Frontend

**Hook mới** (`lib/inventory.tsx`, cạnh `useChannelListings`): `useGroupedChannelListings(filters)` gọi `/channel-listings/grouped`, type `GroupedChannelListing { channel_account_id, external_product_id, title, image, variant_count, variants: ChannelListing[] }`.

**Hook mới** (`features/products/api.ts` + `hooks.ts`, cạnh `cloneChannelListingToShops`/`useCloneChannelListingToShops`): `bulkCloneChannelListingsToShops()` + `useBulkCloneChannelListingsToShops()`.

**`OnChannelPage.tsx`:**
- Chuyển `useChannelListings` → `useGroupedChannelListings`. `Table<GroupedChannelListing>` — dòng cha: ảnh + tên + gian hàng + "{variant_count} biến thể", nút "Sửa trên sàn" (điều hướng dùng `variants[0].id` làm đại diện) + "Sao chép sàn" (1 sản phẩm, dùng lại flow đơn hiện có với `variants[0].id`).
- `expandable.expandedRowRender` — bảng con hiển thị từng biến thể (biến thể/SKU/giá gốc/giá sau giảm/tồn sàn/trạng thái), **không** có nút hành động (đã dời lên dòng cha).
- `rowSelection` ở bảng cha, `rowKey` = `variants[0].id` (đại diện duy nhất cho cả nhóm). Chọn ≥1 → hiện nút "Sao chép sang gian hàng khác ({N} sản phẩm)" — mở lại modal chọn gian hàng đích hiện có (tái dùng UI), danh sách đích = **mọi gian hàng active** (không lọc trừ nguồn — backend `cloneToShops` đã tự bỏ qua nếu đích trùng nguồn của từng sản phẩm, đơn giản hoá cho trường hợp chọn nhiều sản phẩm từ nhiều gian hàng nguồn khác nhau). Gọi `useBulkCloneChannelListingsToShops({ channelListingIds: <ds id đại diện đã chọn>, channelAccountIds: <ds đích> })`.

## 4. Edge case

- Sản phẩm không có `external_product_id` → nhóm 1 phần tử, vẫn hiện đúng (không "mất tích" khỏi danh sách).
- Trang chứa hỗn hợp sản phẩm từ nhiều gian hàng nguồn khác nhau khi sao chép hàng loạt → mỗi sản phẩm tự resolve nguồn/đích qua `cloneToShops()` như cũ, chọn trùng gian hàng nguồn của riêng nó sẽ tự bỏ qua (hành vi có sẵn, không đổi).
- 1 sản phẩm lỗi khi sao chép hàng loạt (vd mất quyền truy cập sàn nguồn) → các sản phẩm khác trong lô vẫn xử lý xong; FE hiện tổng kết "Đã sao chép X/Y sản phẩm, Z lỗi" thay vì chặn toàn bộ.
- Lọc `mapped`/`sync_status`/tìm kiếm vẫn áp dụng TRƯỚC khi gộp nhóm — 1 sản phẩm có thể hiện với ít biến thể hơn thực tế nếu filter đang ẩn bớt biến thể (nhất quán với hành vi lọc hiện tại ở `index()`, không phải lỗi mới).

## 5. Testing

- Backend (Feature test): tạo ≥2 sản phẩm (mỗi sản phẩm nhiều `channel_listings` cùng `external_product_id`) + 1 listing không có `external_product_id`, gọi `/channel-listings/grouped` → đúng số nhóm, đúng `variant_count`, phân trang đúng theo SỐ SẢN PHẨM (không phải số dòng); kiểm filter (vd `mapped`) vẫn áp đúng trong nhóm.
- Backend (Feature test): `bulk-clone-to-shops` với 1 sản phẩm lỗi (mock/fake lỗi service) + 1 sản phẩm OK → response có cả 2, không throw giữa chừng; tenant khác không lọt vào.
- Frontend: không có JS test runner — verify thủ công: mở trang, xác nhận sản phẩm nhiều biến thể hiện 1 dòng + mở rộng xem đủ biến thể; tick nhiều sản phẩm → sao chép hàng loạt sang gian hàng khác thành công; nút Sửa/Sao chép ở dòng cha hoạt động đúng như trước (không mất tính năng khi dời vị trí nút).
