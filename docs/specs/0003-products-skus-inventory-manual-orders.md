# SPEC 0003: Sản phẩm / SKU master · Kho & Tồn kho lõi · Đơn thủ công · Ghép SKU · Đẩy tồn TikTok

- **Trạng thái:** Implemented (2026-05-13 — core tồn kho + đơn thủ công + ghép SKU + PushStockToChannel + TikTok updateStock; còn lại: TikTok `fetchListings`, WMS đầy đủ, FE Vitest — xem §10/§11)
- **Phase:** 2 *(đi cùng SPEC-0002 "Sổ khách hàng" — cùng pha)*
- **Module backend liên quan:** **Inventory** (chính), **Products**, Orders, Channels, Tenancy (RBAC)
- **Tác giả / Ngày:** Team · 2026-05-13
- **Liên quan:** ADR-0008 (master SKU = single source of truth), ADR-0003 (modular monolith), ADR-0004 (connector/registry); docs `03-domain/inventory-and-sku-mapping.md`, `03-domain/manual-orders-and-finance.md` (Phần 1), `03-domain/order-status-state-machine.md`, `02-data-model/overview.md`.

## 1. Vấn đề & mục tiêu
Sau Phase 1, đơn TikTok về nhưng `order_items.sku_id = null` (chưa biết trừ tồn từ đâu) và chưa có kho/tồn. Phase 2 dựng **lõi tồn kho**: một **master SKU** là nguồn sự thật duy nhất về tồn; bán trên TikTok và tạo **đơn thủ công** đều trừ **chung một kho**; mọi thay đổi tồn để lại **một dòng bất biến** trong `inventory_movements`; thay đổi tồn → **tự đẩy** lên listing TikTok đã ghép. Tuân thủ `inventory-and-sku-mapping.md` (đã Stable) — spec này chỉ cụ thể hoá phần triển khai Phase 2 (chưa làm WMS đầy đủ: nhập/xuất/điều chuyển/kiểm kê/FIFO — để Phase 5).

## 2. Trong / ngoài phạm vi
**Trong:**
- Module **Products**: `products`, `channel_listings` (SP gốc + listing trên sàn, đồng bộ từ TikTok). FetchListings cho TikTok (lấy product/SKU của shop về `channel_listings`).
- Module **Inventory**: `skus` (master SKU, `sku_code` unique/tenant), `warehouses` (kho, có `is_default`), `inventory_levels` (`on_hand`/`reserved`/`safety_stock`/`available_cached` theo `(sku_id, warehouse_id)`), `inventory_movements` (sổ cái), `sku_mappings` (`channel_listing` ↔ `sku` × `quantity`, type `single|bundle`).
- `InventoryLedgerService`: `adjust` / `receipt` / `reserve` / `release` / `ship` / `returnIn` — transaction + `lockForUpdate` trên `inventory_levels`, ghi `inventory_movements` với `balance_after`, cập nhật `available_cached`, phát `InventoryChanged`. Chống oversell: reserve khi không đủ vẫn cho (đơn có thật) → `available` đẩy lên sàn = 0 + cảnh báo "âm kho".
- **Ghép SKU**: auto-match `channel_listing.seller_sku == skus.sku_code` (chuẩn hoá trim/upper/bỏ space) ⇒ gợi ý/tạo `single×1`; thủ công qua API (chọn SKU cho listing, thêm nhiều dòng = combo). `order_items.sku_id` được resolve khi `OrderUpserted` (qua mapping của `channel_listing`, hoặc auto-match `order_items.seller_sku == sku_code`); chưa map ⇒ `sku_id=null` + `order.has_issue` ("đơn có SKU chưa ghép").
- **Tác động tồn theo vòng đời đơn** (RULES ở `inventory-and-sku-mapping.md` §3): `pending`/`processing` lần đầu ⇒ `reserve`; huỷ/hoàn trước ship ⇒ `release`; `shipped` ⇒ `reserved-=q, on_hand-=q` (`order_ship`). Combo ⇒ tác động lên mọi SKU thành phần × quantity. Idempotent (theo `(ref_type, ref_id, sku_id, warehouse_id, movement_type)` để không trừ lặp).
- **Đơn thủ công**: `POST /api/v1/orders` (source=manual, `order_number` tự sinh) — chọn master SKU + số lượng + đơn giá + chiết khấu, khách (tên/SĐT/địa chỉ), phí ship/COD/note/tag → tạo `orders`+`order_items(sku_id)` → reserve tồn. Sửa/huỷ khi chưa `shipped`. Cảnh báo trùng (cùng SĐT + cùng SKU trong khoảng ngắn).
- **PushStockToChannel**: `InventoryChanged(sku)` → `PushStockForSku` (debounce key `push-stock:{tenant}:{sku}`, delay ~10s) → tính `available` tổng → mỗi `channel_listing` ghép X: tính `channel_stock` mong muốn (`single`⇒`floor(available/quantity)`, `bundle`⇒`min` thành phần); khác hiện tại ⇒ `PushStockToListing(listing)` (queue `inventory-push`) → `connector.updateStock(auth, external_sku_id, desired)` → cập nhật `channel_listings.channel_stock`/`last_pushed_at`/`sync_status`. TikTok bật capability `listings.updateStock=true` + implement `updateStock`. Khoá đẩy per-listing (`is_stock_locked`).
- **Cảnh báo**: hết hàng / âm kho / listing `sync_status=error` / listing chưa ghép → qua field/flag (UI hiển thị; thông báo realtime để Phase 6).
- RBAC: thêm `products.manage`, `inventory.map` (`inventory.view`/`inventory.adjust` đã có).

**Ngoài (Phase 5 / sau):** nhập/xuất/điều chuyển kho (`stock_transfers`/`goods_receipts`), kiểm kê (`stock_takes`), giá vốn FIFO (`cost_layers`/`order_costs`), mass-listing/đăng bán đa sàn, đồng bộ category/attribute sàn, đồng bộ ngược tồn (đối chiếu), Shopee/Lazada (Phase 4). `inventory_movements` partition theo tháng (bảng thường ở Phase 2; helper `MonthlyPartition` sẵn).

## 3. Luồng chính
- **Tạo SKU & kho:** SPA → "Sản phẩm" tạo `product` → thêm `sku` (sku_code, barcode, name, cost_price, attributes). Kho mặc định tự tạo (tenant đầu tiên có 1 `warehouse(is_default)`); thêm kho khác nếu cần. Điều chỉnh tồn tay: `POST /inventory/adjust { sku_id, warehouse_id?, qty_change, note }` ⇒ `manual_adjust` movement.
- **Lấy listing TikTok & ghép:** `channels:fetch-listings` (hoặc nút Resync listing) → `connector.fetchListings` → upsert `channel_listings` → listing chưa map vào "Chưa ghép SKU"; auto-match khi `seller_sku==sku_code`; người dùng `POST /sku-mappings { channel_listing_id, lines:[{sku_id, quantity}], type }`.
- **Đơn về (TikTok) → trừ tồn:** `OrderUpserted` → listener Inventory: với mỗi `order_item` resolve `sku_id` (qua `channel_listing` của item ↦ `sku_mappings`, hoặc auto-match `seller_sku==sku_code`); rồi áp dụng tác động tồn theo `order.status` (reserve/release/ship). Chưa map ⇒ `order.has_issue=true`, `issue_reason='SKU chưa ghép'`, không reserve item đó.
- **Đơn tay → trừ tồn:** `POST /orders` ⇒ tạo đơn `pending`/`processing` ⇒ reserve ngay.
- **Đổi tồn → đẩy sàn:** mọi `adjust/reserve/release/ship/...` phát `InventoryChanged(sku_ids)` → debounce → `PushStockForSku` → `PushStockToListing` → `updateStock`.

## 4. Hành vi & quy tắc nghiệp vụ
- **`available` = `max(0, on_hand − reserved − safety_stock)`**. Cache `available_cached`; nguồn tính là 3 cột kia. Tổng available của SKU = Σ qua các kho được bán cho gian hàng (Phase 2: tất cả kho).
- **Movement bất biến** với `balance_after` (= `on_hand` sau thay đổi của kho đó). `inventory_movements` không soft delete. `qty_change` âm/dương theo loại.
- **Idempotency tồn:** mỗi (đơn × order_item × kho × loại tác động) chỉ ghi **một** movement — dùng cột `ref_type`/`ref_id`/`movement_type` + uniqueness mềm (kiểm tra tồn tại trước khi áp). Chạy lại `OrderUpserted` không trừ lặp.
- **Chống oversell:** đọc-sửa trong transaction + `lockForUpdate`. Reserve mà `available<q` ⇒ vẫn reserve (đơn thật), `on_hand`/`available` có thể âm tạm ⇒ `inventory_levels` flag (negative) ⇒ `available` đẩy sàn = 0.
- **Combo:** order_item là combo ⇒ reserve/ship lên **mọi** SKU thành phần × `quantity_i × item.quantity`.
- **Auto-match:** chỉ tạo `single×1` khi `normalize(seller_sku) == normalize(sku_code)` và chưa có mapping; ghi audit. Người dùng đổi mapping ⇒ audit + tính lại tồn cần đẩy.
- **PushStock:** debounce/coalesce; tôn trọng throttle per `(provider, shop)` trong client; thất bại ⇒ retry/backoff; quá hạn ⇒ `channel_listings.sync_status=error` (giữ giá trị mong muốn). `is_stock_locked` ⇒ bỏ qua.
- **Phân quyền:** `inventory.view` xem; `inventory.adjust` điều chỉnh tay; `inventory.map` ghép SKU; `products.view` xem SP/listing; `products.manage` tạo/sửa SP/SKU; `orders.create` tạo đơn tay; `orders.update` sửa đơn tay. Owner/admin = tất cả.

## 5. Dữ liệu
**Module Products** (sở hữu): `products` (tenant_id, name, image?, brand?, category?, meta jsonb; soft delete) · `channel_listings` (tenant_id, channel_account_id, external_product_id, external_sku_id, seller_sku?, title, variation?, price?, channel_stock?, currency, image?, is_active, is_stock_locked, sync_status[`ok|error|pending`], sync_error?, last_pushed_at?, last_fetched_at?, meta jsonb; unique `(channel_account_id, external_sku_id)`; index `(tenant_id, channel_account_id)`, `(tenant_id, seller_sku)`).

**Module Inventory** (sở hữu): `skus` (tenant_id, product_id?, sku_code, barcode?, name, cost_price bigint default 0, attributes jsonb, is_active; unique `(tenant_id, sku_code)`; index `(tenant_id, barcode)`; soft delete) · `warehouses` (tenant_id, name, code?, address jsonb?, is_default bool; unique `(tenant_id, code)` khi code != null) · `inventory_levels` (tenant_id, sku_id, warehouse_id, on_hand int, reserved int, safety_stock int default 0, available_cached int, is_negative bool; unique `(sku_id, warehouse_id)`; index `(tenant_id, sku_id)`) · `inventory_movements` (tenant_id, sku_id, warehouse_id, qty_change int, type[`manual_adjust|goods_receipt|order_reserve|order_release|order_ship|return_in|transfer_out|transfer_in|stocktake_adjust`], ref_type?, ref_id?, balance_after int, note?, created_by?, created_at; index `(tenant_id, sku_id, id)`, `(ref_type, ref_id)`) · `sku_mappings` (tenant_id, channel_listing_id, sku_id, quantity int default 1, type[`single|bundle`], created_by?; index `(tenant_id, channel_listing_id)`, `(tenant_id, sku_id)`; unique `(channel_listing_id, sku_id)`).

**Thay đổi bảng `orders`** (Orders sở hữu) — không cần migration mới: `order_items.sku_id` đã có (Phase 1) — Phase 2 chỉ điền giá trị.

**Domain event:** `InventoryChanged(int tenantId, list<int> skuIds, string reason)` (Inventory phát) · `StockPushed(channelListingId, desired, ok)` (Inventory phát sau push) · listener: Inventory **lắng** `OrderUpserted` (resolve sku + áp tồn) — thay listener no-op stub của Phase 1.

**Migration:** reversible; index như trên; bảng log (`inventory_movements`) không soft delete; không FK cứng xuyên module (soft ref như `orders.channel_account_id`).

## 6. API & UI
**Endpoint mới** (cập nhật `05-api/endpoints.md`):
- Products: `GET /products` (filter `q`, `has_unmapped_listings?`; pagination), `POST /products` (`products.manage`), `GET /products/{id}`, `PATCH /products/{id}`, `DELETE /products/{id}` (soft).
- SKUs: `GET /skus` (filter `q`=code/name/barcode, `product_id`, `low_stock=1`; trả kèm `available_total`/`on_hand_total`/`reserved_total`), `POST /skus` (`products.manage`), `GET /skus/{id}` (kèm `levels[]`, `mappings[]`, `movements[]` gần đây), `PATCH /skus/{id}`, `DELETE /skus/{id}`.
- Warehouses: `GET /warehouses`, `POST /warehouses` (`inventory.adjust`), `PATCH /warehouses/{id}`.
- Inventory: `GET /inventory/levels` (filter `sku_id`/`warehouse_id`/`low_stock`/`negative`), `POST /inventory/adjust` (`inventory.adjust`) `{ sku_id, warehouse_id?, qty_change, note? }`, `GET /inventory/movements` (filter `sku_id`/`warehouse_id`/`type`/`ref_type`,`ref_id`).
- Listings & mapping: `GET /channel-listings` (filter `channel_account_id`, `mapped=0|1`, `sync_status`, `q`), `POST /channel-accounts/{id}/resync-listings` (`channels.manage` — dispatch fetch listings), `POST /sku-mappings` (`inventory.map`) `{ channel_listing_id, type, lines:[{sku_id, quantity}] }`, `DELETE /sku-mappings/{id}` (`inventory.map`), `POST /sku-mappings/auto-match` (`inventory.map` — chạy auto-match cho listing chưa ghép, trả số ghép được).
- Manual orders: `POST /orders` (`orders.create`) `{ sub_source?, buyer:{name?,phone?,address?}, items:[{sku_id, quantity, unit_price?, discount?}], shipping_fee?, cod_amount?, is_cod?, note?, tags?, status? }` ⇒ `201 OrderResource`; `PATCH /orders/{id}` (`orders.update`, chỉ đơn manual chưa shipped — sửa items/khách/phí) ; `POST /orders/{id}/cancel` (`orders.update` — huỷ + release tồn).

**FE** (`06-frontend/overview.md`): `features/products` (list SP, list SKU, chi tiết SKU với tồn theo kho + sổ cái + mapping), `features/inventory` (bảng tồn kho, điều chỉnh tay, sổ cái movements), `features/listings` (màn "Liên kết SKU": list listing chưa ghép + ghép manual + combo + nút auto-match), `features/orders` (form "Tạo đơn" + sửa/huỷ đơn manual). Component: `<StockBadge>` (xanh/vàng/đỏ theo ngưỡng), `<SkuPicker>` (tìm theo code/tên/barcode). *(Phase 2 ưu tiên: bảng tồn kho + list SP/SKU + màn ghép SKU + form tạo đơn tay; mass-listing để Phase 5.)*

**Job mới** (cập nhật `07-infra/queues-and-scheduler.md`): `PushStockForSku` (queue `inventory-push`, debounce/delay ~10s, coalesce per sku), `PushStockToListing` (queue `inventory-push`, throttle per provider+shop, retry/backoff), `FetchChannelListings` (queue `listings`, per channel_account, scheduled hằng ngày + manual). Connector method dùng: `fetchListings`, `updateStock` (TikTok bật `listings.fetch=true`, `listings.updateStock=true`). **Không** `if($provider==='tiktok')` ở core.

## 7. Edge case & lỗi
- SKU chưa ghép cho order_item ⇒ `sku_id=null`, `order.has_issue=true`; ghép sau ⇒ resync (`OrderUpserted` chạy lại hoặc nút "Áp tồn lại").
- Reserve khi thiếu hàng (bán vượt) ⇒ vẫn reserve, `is_negative=true`, `available` đẩy = 0, cảnh báo.
- `updateStock` TikTok lỗi ⇒ retry; quá hạn ⇒ `sync_status=error`, giữ desired, cảnh báo; không tự sửa master.
- Xoá SKU đang được mapping/đơn tham chiếu ⇒ chặn (`409`) hoặc soft delete + cảnh báo mapping còn lại.
- Xoá warehouse mặc định ⇒ chặn; warehouse còn tồn ≠ 0 ⇒ chặn.
- Đơn manual sửa/huỷ sau `shipped` ⇒ chặn (`409`).
- `OrderUpserted` đến lại (out-of-order) ⇒ idempotent (so movement đã có; tồn không trừ lặp).
- Combo có thành phần SKU không tồn tại ⇒ mapping bị từ chối khi tạo.
- Nhiều kho: Phase 2 trừ ở **kho mặc định**; thiếu ⇒ âm kho mặc định (chọn-nhiều-kho để Phase 5).

## 8. Bảo mật & dữ liệu cá nhân
Không PII mới (đơn tay dùng lại pipeline `orders`/`customers` — SĐT mã hoá như cũ; listener `LinkOrderToCustomer` của SPEC-0002 tự khớp đơn tay vào sổ khách). `cost_price` là dữ liệu nhạy cảm thương mại (giá vốn) — chỉ role có `inventory.view`/`products.view` xem; không lộ ra response công khai nào ngoài tenant.

## 9. Kiểm thử
- **Unit:** `InventoryLevel::available()` (công thức + clamp ≥0); `SkuCodeNormalizer` (trim/upper/bỏ space); combo math (`min floor`).
- **Feature:** adjust → movement + balance_after đúng; reserve/release/ship theo vòng đời đơn (idempotent: `OrderUpserted` 2 lần không trừ lặp); auto-match `seller_sku==sku_code` ⇒ `order_items.sku_id` set + reserve; manual order create ⇒ order+items+reserve; manual order cancel ⇒ release; SKU mapping API (single + combo) + auto-match endpoint; oversell ⇒ negative flag + available 0; PushStock: `InventoryChanged` ⇒ `PushStockForSku` ⇒ `PushStockToListing` ⇒ `updateStock` gọi đúng (Bus::fake / Http::fake); tenant isolation; phân quyền.
- **Contract (TikTok):** fixtures `product/list` (fetchListings → ChannelListingDTO), `product/inventory/update` (updateStock OK / error code). `Http::fake`, không gọi mạng thật.
- **FE:** smoke render trang Tồn kho / Sản phẩm / Ghép SKU / Tạo đơn.

## 10. Tiêu chí hoàn thành
- [x] Migrations + models: Products (`products`, `channel_listings`), Inventory (`skus`, `warehouses`, `inventory_levels`, `inventory_movements`, `sku_mappings`). `Warehouse::defaultFor()` tự tạo kho mặc định.
- [x] `InventoryLedgerService` (adjust/receipt/reserve/release/ship/returnIn, transaction+`lockForUpdate`, movements+`balance_after`, `available_cached`, `is_negative` khi oversell, `InventoryChanged`) + `SkuCodeNormalizer` + combo math (`min floor`).
- [x] Listener `ApplyOrderInventoryEffects` (listen `OrderUpserted`) — `OrderInventoryService` resolve `order_items.sku_id` (qua `channel_listing`→`sku_mappings`, hoặc auto-match `seller_sku==sku_code`) + áp tồn theo vòng đời (reserve/ship/release/return), idempotent, set/clear `order.has_issue='SKU chưa ghép'`; thay no-op stub Phase 1.
- [x] `ManualOrderService` + `POST /orders` / `PATCH /orders/{id}` / `POST /orders/{id}/cancel` — tạo đơn `source=manual`, fire `OrderUpserted` (⇒ reserve tồn + link khách SPEC 0002). *(Sửa line-item sau khi tạo: backlog.)*
- [x] Auto-match + `SkuMappingService` (set single/bundle, remove, `autoMatchUnmapped`) + API `POST /sku-mappings`, `POST /sku-mappings/auto-match`, `DELETE /sku-mappings/{id}`.
- [x] TikTok `updateStock` (bật capability `listings.updateStock=true`, signed POST tới endpoint config-able — *cần xác nhận shape với Partner API*); `ChannelConnector::updateStock` thêm tham số `$context`. **`fetchListings` + `FetchChannelListings` job + DTO `ChannelListingDTO` — chưa làm** (channel_listings hiện nhập qua API/seed; xem §11).
- [x] `PushStockForSku` + `PushStockToListing` jobs (queue `inventory-push`, retry/backoff; `UnsupportedOperation`/listing locked ⇒ `sync_status=error`/skip) + listener `PushStockOnInventoryChange` (debounce: `ShouldBeUnique` per sku + dispatch `->delay(10s)`).
- [x] API: products/skus/warehouses/inventory(levels,adjust,movements)/channel-listings/sku-mappings + phân quyền (`products.manage`, `inventory.map` thêm vào `Role`; `staff_warehouse` có `products.manage`+`inventory.map`, `staff_order` có `inventory.map`).
- [ ] FE: trang Tồn kho, Sản phẩm/SKU, Liên kết SKU, Tạo đơn tay (+ `StockBadge`/`SkuPicker`) — *làm tối thiểu / một phần*.
- [x] Test (unit + feature): `SkuCodeNormalizer`, `InventoryLedger` (adjust/reserve/release/ship idempotent/oversell), `OrderInventoryEffects` (auto-match, ship, combo, unmapped, cancel), `ManualOrder` (create/cancel/permissions/customer link), `InventoryApi` (CRUD/adjust/sku-mapping/auto-match/tenant isolation/permissions), `PushStock` (chain + TikTok HTTP call). Contract TikTok `updateStock` qua `Http::fake`. *(FE Vitest — sau.)*
- [x] Tài liệu: spec này (Implemented), `05-api/endpoints.md`, `07-infra/queues-and-scheduler.md`, `00-overview/roadmap.md`, `04-channels/tiktok-shop.md` (updateStock).

## 11. Câu hỏi mở
- Đẩy tồn khi `available ≤ ngưỡng` thành 0 (chống oversell mép)? *Cấu hình per-tenant `inventory.push_zero_below` — Phase 2 mặc định tắt; chỉ trừ `safety_stock`.*
- Đồng bộ ngược tồn (đối chiếu `channel_stock` thực): để Phase 5 (cùng WMS đầy đủ).
- TikTok product API: dùng version `202309`? `fetchListings` endpoint `/product/202309/products/search`, `updateStock` `/product/202309/products/{product_id}/inventory/update` — xác nhận với SDK Partner; cấu hình path qua `config/integrations.php`.
