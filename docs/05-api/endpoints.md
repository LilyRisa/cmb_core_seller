# API endpoints (`/api/v1`)

**Status:** Living document · **Cập nhật:** 2026-05-11

> Nguồn người-đọc-được của API. Mọi endpoint mới ⇒ thêm vào đây (đường dẫn, method, quyền cần, request, response, lỗi đặc thù). Quy ước chung: [`conventions.md`](conventions.md). Auth: Sanctum SPA cookie (gọi `GET /sanctum/csrf-cookie` trước khi gửi request thay đổi dữ liệu). Tenant: header `X-Tenant-Id`.

## Hệ thống

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/health` | — | Probe DB / cache / Redis / queue worker. `200` nếu mọi check *critical* (DB) "ok", `503` nếu không. Trả `data.status` (`ok`/`degraded`), `data.checks.{database,cache,redis,queue}.status`, `app`, `env`, `time`. Mọi response có header `X-Request-Id`. |

## Auth & phiên (public + authenticated)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| POST | `/api/v1/auth/register` | — | `name`, `email`, `password`, `password_confirmation`, `tenant_name?` | `201` `{ data: { id, name, email, tenants:[{id,name,slug,role}] } }` — tạo user + tenant mới (caller = `owner`) + đăng nhập phiên. Lỗi: `422 VALIDATION_FAILED` (email trùng, mật khẩu < 8…). |
| POST | `/api/v1/auth/login` | — | `email`, `password`, `remember?` | `200` `{ data: {…user…} }`. Sai thông tin: `422 INVALID_CREDENTIALS`. |
| POST | `/api/v1/auth/logout` | sanctum | — | `204`. |
| GET | `/api/v1/auth/me` | sanctum | — | `200` `{ data: {…user… với tenants[]} }`. Chưa đăng nhập: `401`. |

## Tenant (workspace)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/tenants` | sanctum | — | `200` `{ data:[{id,name,slug,status,role}] }` — các tenant user thuộc về. |
| POST | `/api/v1/tenants` | sanctum | `name` | `201` `{ data:{id,name,slug,role:'owner'} }` — tạo tenant mới, caller = owner. |
| GET | `/api/v1/tenant` | sanctum + tenant | — | `200` `{ data:{id,name,slug,status,settings,current_role} }`. Thiếu header tenant: `400 TENANT_REQUIRED`. Không thuộc tenant: `403 TENANT_FORBIDDEN`. |
| GET | `/api/v1/tenant/members` | sanctum + tenant (owner/admin) | — | `200` `{ data:[{id,name,email,role}] }`. Vai trò khác: `403`. |
| POST | `/api/v1/tenant/members` | sanctum + tenant (owner/admin) | `email`, `role` (một trong `owner\|admin\|staff_order\|staff_warehouse\|accountant\|viewer`) | `201` `{ data:{id,name,email,role} }`. User chưa có tài khoản: `422 USER_NOT_FOUND` (luồng mời qua email bổ sung sau). Đã là thành viên: `409 ALREADY_MEMBER`. Ghi `audit_logs`. |

## Gian hàng (Channels — Phase 1)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/channel-accounts` | sanctum + tenant (`channels.view`) | — | `{ data:[{id,provider,external_shop_id,shop_name,display_name,name,shop_region,seller_type,status,token_expires_at,last_synced_at,last_webhook_at,has_shop_cipher,...}], meta:{ connectable_providers:[{code,name}] } }`. `name` = `display_name ?? shop_name ?? external_shop_id`. Tokens **không** lộ ra. |
| POST | `/api/v1/channel-accounts/{provider}/connect` | sanctum + tenant (`channels.manage`) | `redirect_after?` | `{ data:{ auth_url, provider } }` — SPA redirect tới `auth_url`. `provider ∈ {tiktok}` (Phase 1). Provider không kết nối được ⇒ `422 PROVIDER_NOT_CONNECTABLE`. |
| PATCH | `/api/v1/channel-accounts/{id}` | sanctum + tenant (`channels.manage`) | `{ display_name: string\|null }` | `{ data: ChannelAccountResource }` — đặt alias hiển thị (hai shop có thể trùng `shop_name`). `null`/rỗng = bỏ alias. |
| DELETE | `/api/v1/channel-accounts/{id}` | sanctum + tenant (`channels.manage`) | — | `{ data:{…account, status:'revoked'} }` — `connector.revoke()` best-effort; lịch sử đơn giữ lại; dừng sync. `404` nếu không thuộc tenant. |
| POST | `/api/v1/channel-accounts/{id}/resync` | sanctum + tenant (`channels.manage`) | — | `{ data:{ queued:true, channel_account_id } }` — dispatch `SyncOrdersForShop`. Account không `active` ⇒ `409`. |
| POST | `/api/v1/channel-accounts/{id}/resync-listings` | sanctum + tenant (`channels.manage`) | — | `{ data:{ queued:true, channel_account_id } }` — dispatch `FetchChannelListings` (kéo listing của shop về `channel_listings` + auto-match SKU). Account không `active` ⇒ `409`; connector không hỗ trợ `listings.fetch` ⇒ `422`. |

## Nhật ký đồng bộ (Sync log — Phase 1)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/sync-runs` | sanctum + tenant (`channels.view`) | query: `channel_account_id`, `type` (csv `poll\|backfill\|webhook`), `status` (csv `running\|done\|failed`), `page`, `per_page` (≤100) | `{ data:[{id,channel_account_id,shop_name,provider,type,status,started_at,finished_at,duration_seconds,cursor,stats:{fetched,created,updated,skipped,errors},error}], meta:{ pagination } }`. Scoped theo tenant (`BelongsToTenant`). |
| POST | `/api/v1/sync-runs/{id}/redrive` | sanctum + tenant (`channels.manage`) | — | `{ data:{ queued:true, channel_account_id, type } }` — dispatch lại `SyncOrdersForShop` (kiểu `backfill` nếu run gốc là backfill, ngược lại `poll`). Account không `active` ⇒ `409`. `404` nếu không thuộc tenant. |
| GET | `/api/v1/webhook-events` | sanctum + tenant (`channels.view`) | query: `channel_account_id`, `provider`, `event_type` (csv), `status` (csv `pending\|processed\|ignored\|failed`), `signature_ok` (bool), `page`, `per_page` (≤100) | `{ data:[{id,provider,event_type,raw_type,external_id,external_shop_id,order_raw_status,channel_account_id,shop_name,signature_ok,status,attempts,error,received_at,processed_at}], meta:{ pagination } }`. Lọc theo cột `tenant_id` (bảng log — không có global scope); **payload thô không lộ ra** (có thể chứa PII buyer — SPEC 0001 §8). `order_raw_status` = trạng thái đơn do push mang theo (nếu có) — dùng để cập nhật đơn ngay cả khi re-fetch detail thất bại. |
| POST | `/api/v1/webhook-events/{id}/redrive` | sanctum + tenant (`channels.manage`) | — | `{ data:{ queued:true, webhook_event_id } }` — reset `status=pending`, xoá `error`, dispatch lại `ProcessWebhookEvent`. `404` nếu không thuộc tenant. |

## Đơn hàng (Orders — Phase 1)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/orders` | sanctum + tenant (`orders.view`) | query: `status` (csv mã chuẩn), `source` (csv), `channel_account_id`, `carrier` (csv ĐVVC), `sku` (LIKE `order_items.seller_sku`), `product` (LIKE `order_items.name`), `q` (mã đơn / tên người mua), `placed_from` / `placed_to` (YYYY-MM-DD), `has_issue` (1), `tag`, `sort` (`-placed_at`\|`placed_at`\|`-grand_total`\|`grand_total`), `page`, `per_page` (≤100), `include=items` | `{ data:[OrderResource], meta:{ pagination } }`. `OrderResource`: `status`+`status_label`+`raw_status`, `channel_account:{id,name,provider}` (gian hàng), `carrier`, tiền là số nguyên VND đồng + `currency`, `items_count`, `has_issue`/`issue_reason`, `tags`, `note`, `packages`, `customer` (SPEC 0002), các mốc ISO-8601. |
| GET | `/api/v1/orders/{id}` | sanctum + tenant (`orders.view`) | — | `{ data: OrderResource kèm `items[]` & `status_history[]` }`. `404` nếu không thuộc tenant. |
| GET | `/api/v1/orders/stats` | sanctum + tenant (`orders.view`) | cùng filter như `/orders` (`page` bỏ qua) | `{ data:{ total, has_issue, unmapped, by_status:{ <mã>: N }, by_source:[{source,count}], by_shop:[{channel_account_id,count}], by_carrier:[{carrier,count}] } }` — đếm faceted cho status tabs + panel "Lọc" + banner "đơn chưa liên kết SKU" (`unmapped` = số đơn `issue_reason='SKU chưa ghép'`). `by_status`/`has_issue`/`unmapped` áp mọi filter trừ `status`/`has_issue`; `by_source`/`by_shop`/`by_carrier` áp mọi filter trừ `source`/`channel_account_id`/`carrier`. Chi tiết UI: `docs/06-frontend/orders-filter-panel.md`. |
| POST | `/api/v1/orders/sync` | sanctum + tenant (`orders.view`) | — | `{ data:{ queued: N } }` — dispatch `SyncOrdersForShop` cho mọi gian hàng `active` của tenant (nút "Đồng bộ đơn" ở trang Đơn hàng). |
| POST | `/api/v1/orders` | sanctum + tenant (`orders.create`) | `{ sub_source?, status?: pending\|processing, buyer:{name?,phone?,address?,ward?,district?,province?}, items:[{sku_id?, name?, image?, variation?, quantity?, unit_price?, discount?}], shipping_fee?, tax?, is_cod?, cod_amount?, note?, tags? }` | `201 { data: OrderResource }` — tạo đơn `source=manual`, `order_number` tự sinh, reserve tồn ngay (qua `OrderUpserted`), khớp sổ khách hàng nếu có SĐT. Mỗi dòng hàng là **một SKU hệ thống** (`sku_id` — `name`/`seller_sku`/`image` tự lấy từ SKU nếu bỏ trống) **hoặc một "sản phẩm nhanh" ad-hoc** (không `sku_id`; `name` bắt buộc, `image` là URL ảnh đã upload — không theo dõi tồn kho, không bị gắn cờ "SKU chưa ghép"). Xem `docs/03-domain/manual-orders-and-finance.md` §1. (Phase 2 / SPEC 0003.) |
| PATCH | `/api/v1/orders/{id}` | sanctum + tenant (`orders.update`) | `{ buyer?, shipping_fee?, tax?, is_cod?, cod_amount?, note?, tags? }` | `{ data: OrderResource }` — chỉ đơn `manual` chưa `shipped` (sửa line-item: backlog). `422` nếu đã bàn giao. |
| POST | `/api/v1/orders/{id}/cancel` | sanctum + tenant (`orders.update`) | `{ reason? }` | `{ data: OrderResource (cancelled) }` — chỉ đơn `manual` chưa `shipped`; release tồn. `422` nếu đã bàn giao. |
| POST | `/api/v1/orders/{id}/tags` | sanctum + tenant (`orders.update`) | `{ add?:string[], remove?:string[] }` | `{ data: OrderResource }`. |
| PATCH | `/api/v1/orders/{id}/note` | sanctum + tenant (`orders.update`) | `{ note: string\|null }` | `{ data: OrderResource }`. *(Đổi trạng thái "lõi" của đơn sàn: chặn — chỉ tag/note; đơn manual đi state machine qua các action riêng — Phase 3.)* |

## Sản phẩm / SKU / Tồn kho / Ghép SKU (Phase 2 — SPEC 0003)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET/POST | `/api/v1/products` | `products.view` / `products.manage` | `q?` / `{name, image?, brand?, category?, meta?}` | `ProductResource[]` (+`skus_count`) / `201`. |
| GET/PATCH/DELETE | `/api/v1/products/{id}` | `products.view` / `products.manage` | — / partial | `ProductResource` / soft delete. |
| GET | `/api/v1/skus` | `inventory.view` | `q?` (code/name/barcode), `product_id?`, `is_active?`, `low_stock?` (≤N), `page`, `per_page≤100` | `{ data:[SkuResource{...,on_hand_total,reserved_total,available_total}], meta:{pagination} }`. `SkuResource` gồm cả PIM: `spu_code, category, gtins[], base_unit, cost_price, ref_sale_price, ref_profit_per_unit, ref_margin_percent, sale_start_date, note, weight_grams, length_cm, width_cm, height_cm, image_url`. |
| POST | `/api/v1/skus` | `products.manage` | `{ sku_code, name, product_id?, spu_code?, category?, barcode?, gtins?:[≤10], base_unit?, cost_price?, ref_sale_price?, sale_start_date?, note?, weight_grams?, length_cm?, width_cm?, height_cm?, attributes?, mappings?:[{channel_account_id, external_sku_id, seller_sku?, quantity?}], levels?:[{warehouse_id, on_hand?, cost_price?}] }` | `201 { data: SkuResource }` — tạo SKU + (tuỳ chọn) ghép listing sàn + tồn đầu kỳ/giá vốn theo kho trong 1 transaction. Mã trùng ⇒ `422 SKU_CODE_TAKEN`; `channel_account_id`/`warehouse_id` lạ ⇒ `422`. `on_hand>0` ⇒ movement `goods_receipt` (`ref_type='sku_create'`, ghi chú "Tồn đầu kỳ"). (SPEC 0005) |
| GET | `/api/v1/skus/{id}` | `inventory.view` | — | `SkuResource` + `levels[]` (theo kho, có `cost_price`) + `mappings[]` (có `channel_listing{id,channel_account_id,external_sku_id,seller_sku,title}`) + `movements[]` (50 gần nhất). |
| PATCH/DELETE | `/api/v1/skus/{id}` | `products.manage` | partial (mọi trường catalogue/PIM kể cả `is_active`; FE khoá `sku_code`); thêm tuỳ chọn `mappings:[{channel_account_id, external_sku_id, seller_sku?, quantity?}]` — khi có ⇒ **thay thế toàn bộ** liên kết SKU sàn của SKU này (firstOrCreate listing + setMapping single×qty; gỡ link tới listing không còn trong danh sách; `[]` = gỡ hết). **Không** sửa `levels` (tồn) qua đây. / — | `SkuResource` (kèm `mappings`) / soft delete (`409` nếu còn `on_hand`/`reserved`). |
| POST | `/api/v1/skus/{id}/image` | `products.manage` | multipart `image` (PNG/JPG/WEBP, ≤`MEDIA_IMAGE_MAX_KB`≈5MB) | `{ data: SkuResource }` — tải/đè ảnh lên kho media (Cloudflare R2 ở prod); ghi `image_url`+`image_path`, xoá ảnh cũ. Sai định dạng/quá lớn ⇒ `422`. Cấu hình: `docs/07-infra/cloudflare-r2-uploads.md`. (SPEC 0005 §7) |
| DELETE | `/api/v1/skus/{id}/image` | `products.manage` | — | `{ data:{ deleted:true } }` — xoá object ảnh + clear `image_url`/`image_path`. |
| POST | `/api/v1/media/image` | sanctum + tenant (`orders.create` hoặc `products.manage`) | multipart `image` (PNG/JPG/WEBP, ≤`MEDIA_IMAGE_MAX_KB`≈5MB), `folder?` (a-z0-9_-, ≤40, mặc định `misc`) | `{ data:{ path, url } }` — upload ảnh chung lên kho media (Cloudflare R2 ở prod); trả object key + URL công khai để bên gọi tự lưu. Dùng cho ảnh dòng "sản phẩm nhanh" của đơn thủ công (order_item không gắn SKU). Sai định dạng/quá lớn ⇒ `422`. |
| GET/POST | `/api/v1/warehouses` | `inventory.view` / `inventory.adjust` | — / `{name, code?, address?, is_default?}` | `WarehouseResource[]` (tự đảm bảo có 1 kho mặc định) / `201`. |
| PATCH | `/api/v1/warehouses/{id}` | `inventory.adjust` | partial | `WarehouseResource`. |
| GET | `/api/v1/inventory/levels` | `inventory.view` | `sku_id?`, `warehouse_id?`, `negative?` (1), `low_stock?` (≤N), `page` | `{ data:[InventoryLevelResource{on_hand,reserved,safety_stock,available,is_negative,sku,warehouse}], meta }`. |
| POST | `/api/v1/inventory/adjust` | `inventory.adjust` | `{ sku_id, warehouse_id?, qty_change (≠0), note? }` | `201 { data: InventoryMovementResource{qty_change,type,balance_after,...} }` — `on_hand += qty_change`, ghi sổ cái, phát `InventoryChanged` ⇒ đẩy tồn. |
| POST | `/api/v1/inventory/bulk-adjust` | `inventory.adjust` | `{ kind: goods_receipt\|manual_adjust, warehouse_id?, note?, lines:[{ sku_id, qty_change }] }` (≤500 dòng) | `201 { data:{ applied:N, movements:[InventoryMovementResource] } }` — "phiếu" nhập/xuất hàng loạt: mỗi dòng → 1 movement (`ref_type='manual_bulk'`, cùng `note`). `goods_receipt` ⇒ mọi qty > 0; SKU trùng trong phiếu ⇒ `422 DUPLICATE_SKU`; SKU không thuộc tenant ⇒ `422`. (SPEC 0004) |
| POST | `/api/v1/inventory/push-stock` | `inventory.map` | `{ sku_ids:[int] }` (≤500) | `{ data:{ queued:N } }` — đẩy tồn hàng loạt theo bộ chọn: dispatch `PushStockForSku` ngay cho mỗi SKU (không delay). SKU tenant khác bị bỏ. (SPEC 0004) |
| GET | `/api/v1/inventory/movements` | `inventory.view` | `sku_id?`, `warehouse_id?`, `type?` (csv), `ref_type?`+`ref_id?`, `page` | `{ data:[InventoryMovementResource], meta }`. |
| GET | `/api/v1/channel-listings` | `products.view` | `channel_account_id?`, `sync_status?`, `mapped?` (0\|1), `q?`, `page` | `{ data:[ChannelListingResource{...,channel_stock,sync_status,is_stock_locked,is_mapped,mappings[]}], meta }`. |
| POST | `/api/v1/channel-listings/sync` | `inventory.map` | — | `{ data:{ queued: N } }` — dispatch `FetchChannelListings` cho mọi shop `active` hỗ trợ `listings.fetch` (nút "Đồng bộ listing từ sàn" ở tab Liên kết SKU). |
| PATCH | `/api/v1/channel-listings/{id}` | `inventory.map` | `{ is_stock_locked? }` | `ChannelListingResource` — ghim/bỏ ghim tự-đẩy tồn. |
| POST | `/api/v1/sku-mappings` | `inventory.map` | `{ channel_listing_id, type?: single\|bundle, lines:[{sku_id, quantity?}] }` | `201 { data: SkuMappingResource[] }` — thay thế mapping của listing; `single` ⇒ đúng 1 line (ngược lại `422`); SKU không thuộc tenant ⇒ `422`. Phát `InventoryChanged` ⇒ tính lại & đẩy tồn. |
| POST | `/api/v1/sku-mappings/auto-match` | `inventory.map` | — | `{ data:{ matched: N } }` — tạo `single×1` cho mọi listing chưa ghép có `seller_sku` (chuẩn hoá) trùng `sku_code`. |
| DELETE | `/api/v1/sku-mappings/{id}` | `inventory.map` | — | `{ data:{ deleted:true } }` + đẩy tồn lại. |
| GET | `/api/v1/orders/unmapped-skus` | `orders.view` + `inventory.map` | `order_ids?` (csv — bỏ trống = mọi đơn còn dòng chưa ghép) | `{ data:[{ channel_account_id, channel_account_name, external_sku_id, seller_sku, sample_name, order_count, item_count, existing_listing_id, suggested_sku_id }] }` — các SKU sàn distinct **đã gộp** (cùng `(channel_account_id, external_sku_id\|seller_sku)`); `suggested_sku_id` = master SKU có `sku_code` chuẩn-hoá trùng `seller_sku`. (SPEC 0004) |
| POST | `/api/v1/orders/link-skus` | `inventory.map` | `{ links:[{ channel_account_id, external_sku_id?, seller_sku?, sku_id }] }` (≤200) | `{ data:{ linked:N, listings_created:N, orders_resolved:N } }` — mỗi link: `channel_listing` firstOrCreate `(channel_account_id, external_sku_id)` (tạo từ dữ liệu đơn nếu chưa có) → `sku_mappings` single×1 → sau cùng re-fire `OrderUpserted` cho mọi đơn còn dòng chưa ghép ⇒ listener re-resolve `order_items.sku_id`, reserve tồn, clear `has_issue`, phát `InventoryChanged`. Idempotent. SKU/shop khác tenant ⇒ `422`. (SPEC 0004) |

## Giao hàng & in ấn (Fulfillment — Phase 3, SPEC-0006)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/carriers` | `fulfillment.view` | — | `{ data:[{ code, name, capabilities:[], needs_credentials }] }` — các ĐVVC khả dụng (`manual` luôn có; thêm theo `INTEGRATIONS_CARRIERS`). |
| GET/POST | `/api/v1/carrier-accounts` | `fulfillment.view` / `fulfillment.carriers` | — / `{ carrier, name, credentials?, default_service?, is_default?, meta? }` | `CarrierAccountResource[]` / `201`. `credentials` được mã hoá, response chỉ trả `credential_keys`. Carrier không hỗ trợ ⇒ `422`. |
| PATCH/DELETE | `/api/v1/carrier-accounts/{id}` | `fulfillment.carriers` | partial / — | `CarrierAccountResource` / `{ deleted:true }`. |
| GET | `/api/v1/fulfillment/processing` | `fulfillment.view` | `stage` (`prepare`\|`pack`\|`handover`), `source` (csv nền tảng), `carrier` (csv ĐVVC), `customer` (LIKE tên/mã đơn), `product` (LIKE tên SP/`seller_sku`), `channel_account_id?`, `page`, `per_page≤200` | `{ data:[OrderResource (đã nạp `shipment`)], meta:{pagination, stage} }` — màn xử lý đơn (SPEC 0009): `prepare`=cần xử lý (chưa có vận đơn / chưa in tem), `pack`=đã in chờ đóng gói (vận đơn `created` & in≥1, hoặc `manual` không tem), `handover`=đã đóng gói chờ bàn giao (vận đơn `packed`). |
| GET | `/api/v1/fulfillment/processing/counts` | `fulfillment.view` | cùng filter (trừ `page`) | `{ data:{ prepare:N, pack:N, handover:N } }` — badge số lượng mỗi bước. |
| GET | `/api/v1/fulfillment/ready` | `fulfillment.view` | (= `processing?stage=prepare`) | như trên — alias tương thích SPEC-0006. |
| POST | `/api/v1/orders/{id}/ship` | `fulfillment.ship` | `{ carrier_account_id?, service?, tracking_no?, cod_amount?, weight_grams?, note? }` | `201 { data: ShipmentResource }` — tạo vận đơn (đã có shipment chưa huỷ ⇒ trả lại nó). `carrier_account_id` để trống = tài khoản mặc định, hoặc `manual` nếu chưa cấu hình. Đơn `processing→ready_to_ship`. Trạng thái đơn không hợp lệ ⇒ `422`. |
| POST | `/api/v1/shipments/bulk-create` | `fulfillment.ship` | `{ order_ids:[≤200], carrier_account_id?, service? }` | `{ data:{ created:[ShipmentResource], errors:[{order_id, message}] } }` — lỗi từng đơn không chặn batch. |
| GET | `/api/v1/shipments` | `fulfillment.view` | `status?, carrier?, order_id?, q?(tracking/mã đơn), page, per_page` | `{ data:[ShipmentResource], meta:{pagination} }`. |
| GET | `/api/v1/shipments/{id}` | `fulfillment.view` | — | `ShipmentResource` + `events[]`. |
| POST | `/api/v1/shipments/{id}/track` | `fulfillment.ship` | — | `ShipmentResource` (+events) — gọi `connector.getTracking`, ghi `shipment_events` mới, đồng bộ trạng thái shipment & đơn. |
| POST | `/api/v1/shipments/{id}/cancel` | `fulfillment.ship` | — | `ShipmentResource` — `connector.cancel`, shipment `cancelled`, đơn về `processing` nếu chưa shipped. |
| GET | `/api/v1/shipments/{id}/label` | `fulfillment.print` | — | `302` redirect tới `label_url` (`404` nếu chưa có tem; ĐVVC `manual` không có tem). |
| POST | `/api/v1/shipments/pack` | `fulfillment.scan`\|`fulfillment.ship` | `{ shipment_ids:[≤500] }` | `{ data:{ packed:N } }` — đóng gói hàng loạt (`created → packed`; **không** đổi trạng thái đơn, **không** trừ tồn). (SPEC 0009) |
| POST | `/api/v1/shipments/handover` | `fulfillment.ship` | `{ shipment_ids:[≤500] }` | `{ data:{ handed_over:N } }` — bàn giao ĐVVC hàng loạt (`created/packed → picked_up`, đơn `→shipped`, trừ tồn). |
| POST | `/api/v1/scan-pack` | `fulfillment.scan` | `{ code }` | `{ data:{ action:'pack', message, shipment, order:{id,order_number,status} } }` — quét mã vận đơn/mã đơn → đánh dấu **đã đóng gói** (`created → packed`). Đã đóng gói/bàn giao ⇒ `409`; không thấy ⇒ `404`. (SPEC 0009 — đổi so với SPEC-0006: trước đây đi thẳng `picked_up`/`shipped`.) |
| POST | `/api/v1/scan-handover` | `fulfillment.ship`\|`fulfillment.scan` | `{ code }` | `{ data:{ action:'handover', … } }` — (app quét đơn gọi cái này) quét → **bàn giao ĐVVC** (`created/packed → picked_up`, đơn `shipped`, trừ tồn). Đã bàn giao ⇒ `409`. (SPEC 0009) |
| GET/POST | `/api/v1/print-jobs` | `fulfillment.print` | `type?` / `{ type:'label'\|'picking'\|'packing', order_ids?:[≤500], shipment_ids?:[≤500] }` | `{ data:[PrintJobResource], meta:{pagination} }` / `201 { data: PrintJobResource }` (`status=pending` → job ở queue `labels` render PDF; FE poll). Thiếu cả order_ids & shipment_ids ⇒ `422`. `type=label` mà bộ chọn **lẫn nhiều nền tảng / nhiều ĐVVC** ⇒ `422`. Mỗi lần in `type=label` xong ⇒ `print_count++` trên các shipment được ghép. (SPEC 0009) |
| GET | `/api/v1/print-jobs/{id}` | `fulfillment.print` | — | `PrintJobResource` (`status`, `file_url` khi `done`, `meta.skipped[]`). |
| GET | `/api/v1/print-jobs/{id}/download` | `fulfillment.print` | — | `302` redirect tới `file_url` (`409` nếu chưa `done`). |

`OrderResource.shipment` = vận đơn mới nhất chưa huỷ `{ id, carrier, tracking_no, status, label_url, print_count, packed_at }` hoặc null. `ShipmentResource` thêm `print_count`, `last_printed_at`, `packed_at` (trạng thái vận đơn: `pending\|created\|packed\|picked_up\|in_transit\|delivered\|failed\|returned\|cancelled`).

## Dashboard

| Method | Path | Auth | Response |
|---|---|---|---|
| GET | `/api/v1/dashboard/summary` | sanctum + tenant (`dashboard.view`) | `{ data:{ channel_accounts:{total,active,needs_reconnect}, orders:{today,to_process,ready_to_ship,shipped,has_issue,total}, revenue_today } }`. |

## Webhook & OAuth (ngoài `/api`)

Xem [`webhooks-and-oauth.md`](webhooks-and-oauth.md). `POST /webhook/tiktok` → `WebhookController@handle` (verify chữ ký → ghi `webhook_events` → 200 → `ProcessWebhookEvent`); sai chữ ký ⇒ `401`. `GET /oauth/tiktok/callback?app_key=&code=&state=` → `OAuthCallbackController` (đổi token, tạo `channel_account`, redirect `/channels?connected=tiktok` hoặc `?error=…`). `shopee`/`lazada`: route + handler tồn tại nhưng connector chưa có ⇒ `404 UNKNOWN_PROVIDER` (Phase 4).

## Sổ khách hàng (Customers — Phase 2, SPEC-0002 · Implemented)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/customers` | sanctum + tenant (`customers.view`) | query: `q` (chuẩn hoá được thành SĐT ⇒ hash + lookup `phone_hash`; ngược lại LIKE `name`), `reputation` csv (`ok\|watch\|risk\|blocked`), `tag`, `min_orders`, `has_note` (1), `blocked` (1), `sort` (`-last_seen_at`\|`-lifetime_revenue`\|`-orders_total`\|`-cancellation_rate`\|`±reputation_score`), `page`, `per_page≤100` | `{ data: CustomerResource[], meta:{ pagination } }`. `CustomerResource`: `name`, `phone_masked` (luôn) + `phone` (chỉ khi role có `customers.view_phone`), `reputation:{score,label}`, `is_blocked`/`block_reason`, `tags`, `lifetime_stats`, `addresses_meta`, `manual_note`, `is_anonymized`, `first_seen_at`/`last_seen_at`, `latest_warning_note?`. Hồ sơ đã ẩn danh ⇒ name/phone/addresses = null/[]. |
| GET | `/api/v1/customers/{id}` | sanctum + tenant (`customers.view`) | — | `{ data: CustomerResource kèm `notes[]` (id-desc, ≤50) }`. `404` nếu không thuộc tenant / đã merge. |
| GET | `/api/v1/customers/{id}/orders` | sanctum + tenant (`customers.view` + `orders.view`) | query: `source` (csv), `status` (csv), `page`, `per_page≤100` | `{ data: OrderResource[], meta:{ pagination } }` — scoped theo `customer_id`, sort `-placed_at`. |
| POST | `/api/v1/customers/{id}/notes` | sanctum + tenant (`customers.note`) | `{ note: string, severity?: info\|warning\|danger, order_id?: int }` | `201 { data: CustomerNoteResource }` (`kind=manual`). |
| DELETE | `/api/v1/customers/{id}/notes/{noteId}` | sanctum + tenant (`customers.note` + là `author_user_id` hoặc owner/admin) | — | `{ data:{ deleted:true } }`. Auto-note ⇒ `422`. |
| POST | `/api/v1/customers/{id}/block` | sanctum + tenant (`customers.block`) | `{ reason?: string }` | `{ data: CustomerResource }` — `is_blocked=true`, `reputation.label='blocked'`, event `CustomerBlocked`. |
| POST | `/api/v1/customers/{id}/unblock` | sanctum + tenant (`customers.block`) | — | `{ data: CustomerResource }` — label tính lại từ stats, event `CustomerUnblocked`. |
| POST | `/api/v1/customers/{id}/tags` | sanctum + tenant (`customers.note`) | `{ add?:string[], remove?:string[] }` | `{ data: CustomerResource }`. |
| POST | `/api/v1/customers/merge` | sanctum + tenant (`customers.merge`) | `{ keep_id, remove_id }` (`different`) | `{ data: CustomerResource (kept) }` — chuyển `orders.customer_id` + `customer_notes` từ `remove` sang `keep`, recompute stats, soft-delete `remove` (`merged_into_customer_id=keep`), event `CustomersMerged`. Reject khác tenant. |

**Sửa endpoint hiện có:** `GET /api/v1/orders/{id}` (và `GET /orders`) trả thêm field `customer` (object con hoặc `null`) — `null` nếu `customer_id IS NULL` hoặc role không có `customers.view`: `{ id, name, phone_masked, reputation:{score,label}, is_blocked, tags, is_anonymized, lifetime_stats:{orders_total,orders_completed,orders_cancelled,orders_returned,orders_delivery_failed}, manual_note, latest_warning_note? }`. Đọc qua `CustomerProfileContract` (Orders không phụ thuộc model `Customer`).

## Sắp có (theo roadmap)

`/api/v1/orders/{id}/status` + `/bulk` (Phase 3 — đơn manual đi state machine) · `/api/v1/print-jobs`, `/api/v1/shipments` (Phase 3) · `/api/v1/stock-transfers`, `/api/v1/stock-takes`, `/api/v1/goods-receipts` (Phase 5 — WMS) · `/api/v1/jobs/{id}` … — thêm vào đây khi xây.
