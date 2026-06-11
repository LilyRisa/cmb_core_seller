# Thiết kế: Quản lý sản phẩm hệ thống & đẩy listing lên sàn (Shopee/TikTok Shop/Lazada)

- **Ngày:** 2026-06-11
- **Trạng thái:** Design — chờ duyệt
- **Repo liên quan:** `cmb_core_seller` (backend + SPA — phần chính), `cmb_copy_product` (Chrome extension — thay đổi nhỏ)
- **Tài liệu nền (req #8):** [`marketplace-product-listing-api-requirements.md`](./marketplace-product-listing-api-requirements.md)
- **Quyết định đã chốt:** đẩy sàn = **backend qua Open Platform API**; kéo listing = **marketplace API ở backend**; **phân giai đoạn**; vertical slice GĐ1 = **Lazada**; token extension = **PAT dài hạn + scope `copy-product:push` + revocable**.

## 1. Bối cảnh & vấn đề

Extension `cmb_copy_product` hiện chỉ copy 1 chiều: cào sản phẩm Shopee/TikTok/Lazada → `POST /draft-products` lên backend. Chưa có quản lý sản phẩm hệ thống và chưa có đẩy ngược lên sàn.

Tài liệu nghiên cứu chính thức (req #8) cho kết luận then chốt: **không sàn nào nhận một "sản phẩm hệ thống" thẳng** — mỗi sàn bắt buộc dữ liệu *riêng theo sàn*: danh mục **lá** theo cây của từng sàn, **thuộc tính bắt buộc theo danh mục**, **`brand_id`** theo danh sách brand của sàn, **ảnh phải re-upload lên CDN của sàn**, và **cấu hình logistics/cân nặng**. Đây là lý do bắt buộc có trạng thái "nháp cần user sửa".

## 2. Yêu cầu (nguồn: user)

1. Hai trạng thái: (a) **sản phẩm hệ thống** có thể import vào Shopee/TikTok Shop/Lazada; (b) **đã import vào sàn nhưng ở trạng thái nháp**, cần user sửa cho đúng dữ liệu sàn (danh mục, giá, tiêu đề, vận chuyển…).
2. Sản phẩm đã đẩy thành công lên 1 sàn → **clone trực tiếp sang shop khác cùng nền tảng**.
3. Token extension **không hết hạn**, **chỉ có quyền đẩy sản phẩm copy**.
4. **Mọi thao tác hiển thị popup thanh tiến trình.**
5. **Sửa hàng loạt** để đẩy lên sàn với các listing đã import.
7. **Kéo & đồng bộ listing từ sàn** để sao chép listing sàn này sang sàn khác (khác nền tảng vẫn phải sửa; cùng nền tảng vẫn phải có 1 thao tác sửa mới đẩy).
8. Đọc tài liệu công khai chính thức của các sàn, không suy đoán, lưu thành tài liệu riêng. ✅ **Đã hoàn thành.**

## 3. Kiến trúc tổng thể

```
[Marketplace pages] --copy--> [Extension] --PAT(copy-product:push)--> [Backend Catalog]
                                                                          |
[SPA: Quản lý SP + Editor nháp + Progress] <--/api/v1--> [Backend Catalog module]
                                                                          |
                                              [Integrations: ChannelConnector + ProductPublishingConnector]
                                                                          |
                                              Lazada / Shopee / TikTok Open Platform API
```

- **Backend `cmb_core_seller`** giữ OAuth per-shop, app_secret, ký request, và thực hiện toàn bộ thao tác tạo/sửa/đẩy listing. Đây là nơi an toàn duy nhất giữ secret và ký.
- **Extension** chỉ: copy + (hiển thị progress) + đẩy bản copy lên backend bằng token scope hẹp.
- **SPA** là nơi user quản lý sản phẩm hệ thống, hoàn thiện nháp sàn, push (có progress), bulk edit, clone, pull.

### 3.1 Module & layer (theo luật modular-monolith + extensibility)

- **Module mới `Catalog`** (`app/app/Modules/Catalog/`): sở hữu `MasterProduct`, `ChannelListing`, push orchestration, push progress. Phụ thuộc `Channels` (shop đã kết nối) và `Tenancy` qua **Contracts/events**, gọi Integration layer qua interface — không `use` nội bộ module khác.
- **Integration layer** (`app/app/Integrations/Channels/<Provider>/`): mở rộng connector với năng lực publish sản phẩm. **Core không bao giờ biết tên sàn** — thêm sàn = thêm connector + 1 dòng `register()` + block `config/integrations.php`, validate theo capability map; method chưa hỗ trợ ném `UnsupportedOperation`.

## 4. Mô hình dữ liệu

Tất cả bảng có `tenant_id` + trait `BelongsToTenant`.

- **`master_products`** — sản phẩm hệ thống (nâng cấp dữ liệu từ `draft_products`).
  `id, tenant_id, source_platform, source_url, source_id, title, description, short_description, brand_name, source_category_path, images(json), video_url, attributes(json), weight, dimensions(json), status('system'), created_by, timestamps`.
- **`master_product_variants`** — `id, master_product_id, options(json), sku, price, stock, image, timestamps`.
- **`channel_listings`** — phép chiếu master sang **1 shop đích**.
  `id, tenant_id, master_product_id, channel_account_id, provider, external_item_id(nullable), category_id, brand_id, attributes(json — theo sàn), media_refs(json — image_id/uri/URL CDN đã upload), logistics(json), status('draft'|'ready'|'pushing'|'live'|'failed'), validation_errors(json), raw_qc_status, last_error(json), pushed_at, timestamps`.
- **`channel_listing_skus`** — `id, channel_listing_id, master_variant_id, seller_sku, sale_props(json), price, stock, package_weight, package_dims(json), external_sku_id, image_ref`.
- **`push_batches`** — `id, tenant_id, type('push'|'bulk'|'clone'), total, succeeded, failed, status, created_by, timestamps`.
- **`push_jobs`** — `id, push_batch_id, channel_listing_id, status('queued'|'running'|'success'|'failed'), step_label, progress, error(json), timestamps`. **Đây là nguồn cho progress modal.**
- **Token extension:** dùng bảng Sanctum `personal_access_tokens` với `abilities=['copy-product:push']`, `expires_at=null`. UI admin để cấp/thu hồi.

State machine `ChannelListing`: `draft → (qua validator đủ field bắt buộc) → ready → (push) → pushing → live | failed`. `failed` quay lại `draft` để sửa.

## 5. Integration layer — năng lực mới của connector

Interface mới `ProductPublishingConnector` (capability keys trong capability map):
- `getCategoryTree(): CategoryNodeDTO[]` — `product.read_taxonomy`
- `getCategoryAttributes($categoryId): AttributeDTO[]`
- `getBrands($categoryId): BrandDTO[]`
- `uploadMedia($image): MediaRefDTO` — `media.upload`
- `createListing(ListingPayloadDTO): ListingResultDTO` — `product.create`
- `updateListing(...)`, `updatePriceStock(...)`
- `getListingStatus($externalItemId): ListingStatusDTO`
- `listListings(...)` — `product.list` (GĐ3)

DTO chuẩn ở `Integrations/Contracts` / `Support/DTO`. Mỗi provider có **validator field bắt buộc** (vd `LazadaListingValidator`) chạy trước khi tạo, ánh xạ đúng bảng "field bắt buộc" trong tài liệu nghiên cứu → quyết định `draft → ready`.

**GĐ1: chỉ Lazada implement đầy đủ.** Shopee/TikTok ném `UnsupportedOperation` cho tới GĐ2.

## 6. HTTP API (`/api/v1`, envelope chuẩn, X-Tenant-Id)

- `GET /master-products` (filter status), `GET/PUT /master-products/{id}`
- Đường push của extension: `POST /master-products` (token `copy-product:push`) — thay/đặt cạnh `/draft-products` hiện tại.
- `POST /master-products/{id}/listings` — tạo `ChannelListing` draft cho 1 shop; trả về **schema field bắt buộc + thuộc tính danh mục** để render form.
- `GET /listings/{id}` (kèm trạng thái validate), `PUT /listings/{id}` (user sửa danh mục/attr/brand/giá/logistics).
- `POST /listings/{id}/push` → enqueue → trả `push_batch_id`.
- `POST /listings/bulk-push`, `POST /listings/{id}/clone` (GĐ2).
- `GET /push-batches/{id}` (poll progress), `GET /push-jobs/{id}`.
- Taxonomy proxy cho editor: `GET /channels/{provider}/categories`, `/attributes?category_id=`, `/brands?category_id=`.
- Admin token: `POST /admin/extension-tokens`, `DELETE /admin/extension-tokens/{id}`.

## 7. Tiến trình (req #4)

- **Extension:** popup progress nhiều bước phía client (Đang lấy dữ liệu → Đang upload ảnh → Đang tạo bản copy → Xong) + thanh %. Thay cho toast hiện tại.
- **SPA:** push/bulk là job hàng đợi (Horizon). Job cập nhật `push_jobs`/`push_batches`. SPA mở **modal progress** poll `GET /push-batches/{id}` (~1.5s/lần): thanh tổng theo N + từng dòng listing với `step_label`; kết thúc tổng kết success/fail kèm lỗi sàn.

## 8. Phía Extension (`cmb_copy_product`)

- Sau login, backend cấp **PAT scope `copy-product:push`** (không expiry) → extension lưu `chrome.storage.local`, bỏ xử lý hết hạn cho đường push (req #3). System-Key vẫn giữ.
- Nâng cấp UI copy thành **popup progress** (req #4).
- Vai trò extension dừng ở copy + progress (pull listing dùng API backend, không cào).

## 9. Bảo mật

- PAT không hết hạn **nhưng** chỉ ability `copy-product:push`, **revocable**, **rate-limit**, ghi log sử dụng; middleware `abilities:copy-product:push` chặn mọi thao tác khác. Secret sàn chỉ ở server.

## 10. Phân giai đoạn

### GĐ0 — Research ✅
Tài liệu yêu cầu sàn (đã có).

### GĐ1 — Nền tảng + vertical slice **Lazada** (spec/plan riêng)
1. Migrations + models: `master_products`, `master_product_variants`, `channel_listings`, `channel_listing_skus`, `push_batches`, `push_jobs`; module `Catalog` + ServiceProvider.
2. PAT scope `copy-product:push` + middleware + admin mint/revoke; chuyển đường push extension sang PAT.
3. Connector Lazada: `getCategoryTree/Attributes/Brands`, `uploadMedia` (image/migrate, lo ≤3MB & 330–5000px → resize), `createListing` (payload XML/JSON), `getListingStatus` (QC); `LazadaListingValidator`.
4. API: master-products CRUD, tạo listing draft, editor PUT, push (queue + progress), taxonomy proxy.
5. SPA: danh sách sản phẩm hệ thống; editor nháp Lazada (chọn danh mục lá, form thuộc tính bắt buộc, brand, ảnh, giá/tồn, package); nút push + **modal progress**.
6. Extension: popup progress khi copy.
7. Test: connector (HTTP fake/sandbox), validator field bắt buộc, feature endpoint, push idempotent; pint/phpstan/test xanh cho phần mới.

### GĐ2 — Đủ 3 sàn + bulk + clone (spec/plan riêng)
- Connector Shopee (bổ sung field `add_item` còn INACCESSIBLE) + TikTok (category v2, warehouse, audit, shop_cipher).
- Bulk edit + bulk push (req #5).
- Clone cùng nền tảng từ listing `live` (req #2): tái dùng category/attr/brand đã validate, map lại giá/tồn/logistics theo shop đích.

### GĐ3 — Kéo & đồng bộ chéo sàn (req #7) (spec/plan riêng)
- `listListings` (get_item_list) trên shop đã OAuth → tạo `MasterProduct`.
- Copy chéo sàn theo **edit-gate**: khác nền tảng → `ChannelListing` draft mới cần hoàn thiện; cùng nền tảng (shop khác) → vẫn bắt buộc 1 thao tác sửa trước khi push.
  - *Phân biệt với req #2:* #2 áp cho listing **do hệ thống tự đẩy** (đã có payload validate đầy đủ → clone trực tiếp); #7 cùng nền tảng áp cho listing **kéo về từ sàn** (chưa map đủ ID nội bộ → cần 1 lần sửa).

## 11. Rủi ro / lưu ý

- **Lazada:** ảnh phải ≤3MB & 330–5000px (ảnh nguồn từ sàn khác có thể vượt → cần resize trước migrate); `SellerSku` **immutable** (sinh seller_sku ổn định); >50 SKU timeout (batch ~20); chỉ URL ảnh Lazada.
- **Ánh xạ danh mục/thuộc tính nguồn→đích không tự động** — user phải chọn (chính là bước sửa nháp).
- **Shopee** trang field `add_item` chính thức hiện INACCESSIBLE — lấy bổ sung khi vào GĐ2.
- Extension đang trỏ `api/v2/draft-products`; cần thống nhất với `api/v1` của SPA khi triển khai GĐ1.

## 12. Tiêu chí hoàn thành GĐ1

- Copy 1 sản phẩm → xuất hiện `MasterProduct(system)` trong SPA.
- Tạo nháp Lazada cho 1 shop đã OAuth, hoàn thiện field bắt buộc → validator chuyển `ready`.
- Push → modal progress chạy → listing lên Lazada (status QC `PENDING`), `external_item_id` lưu lại.
- Token extension không hết hạn, chỉ đẩy được copy; thu hồi được.
- Pint + PHPStan + test xanh cho code mới.
