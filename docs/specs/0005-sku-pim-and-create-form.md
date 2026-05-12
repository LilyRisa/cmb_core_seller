# SPEC 0005: SKU PIM (thông tin hàng hoá) & form "Thêm SKU đơn độc"

- **Trạng thái:** Implemented (2026-05-16 — mở rộng SPEC-0003)
- **Phase:** 2 *(mở rộng SPEC-0003; phần upload ảnh & SPU thực thể để Phase sau)*
- **Module backend liên quan:** Inventory (chính), Products, Channels
- **Tác giả / Ngày:** Team · 2026-05-16
- **Liên quan:** SPEC-0003 (sản phẩm/SKU/tồn kho lõi), SPEC-0004 (ghép SKU nhanh), `docs/03-domain/inventory-and-sku-mapping.md`, `docs/06-frontend/create-sku-form.md`, mẫu UI: `ui_example/them_sku.png`.

## 1. Vấn đề & mục tiêu
SPEC-0003 chỉ cho tạo SKU với `sku_code/name/barcode/cost_price`. Để dùng được như bộ chọn BigSeller ("Thêm SKU đơn độc" — `ui_example/them_sku.png`) và để **mở đường cho các tính năng tương lai** (giá vốn theo kho, tính lợi nhuận, đồng bộ thông tin sản phẩm lên sàn, in tem theo kích thước/cân nặng), `skus` cần mang đủ thông tin PIM (Product Information Management) cơ bản, và form tạo SKU phải làm trọn vẹn trong một trang: thông tin cơ bản + ghép nối SKU sàn + cân nặng/kích thước + tồn đầu kỳ theo kho.

Mục tiêu cụ thể:
1. Mở rộng `skus`: SPU (mã nhóm), danh mục, GTIN (≤10), đơn vị cơ bản, **giá vốn tham khảo** (giữ `cost_price`), **giá bán tham khảo** (`ref_sale_price`), ngày bắt đầu bán, ghi chú, cân nặng & kích thước, `image_url` (chỗ chờ cho upload ảnh — **chưa làm**, xem §7).
2. Thêm `inventory_levels.cost_price` — **giá vốn theo từng kho** (đặt khi nhập đầu kỳ; sau này là chỗ bám của giá vốn bình quân / FIFO ở Phase 5).
3. `POST /skus` nhận thêm các trường trên + (tuỳ chọn) `mappings[]` (ghép listing sàn) + `levels[]` (tồn đầu kỳ + giá vốn theo kho) ⇒ làm tất cả trong một request.
4. Form FE full-trang `/inventory/skus/new` đúng bố cục mẫu, có thanh điều hướng mỏ neo (anchor) bên phải, có ô tính **lợi nhuận tham khảo / biên lợi nhuận** tự động.

## 2. Trong / ngoài phạm vi
**Trong:** mở rộng cột `skus` + `inventory_levels.cost_price`; `POST /skus` & `PATCH /skus/{id}` nhận trường mới; form FE; hiển thị Giá bán TK / Lợi nhuận/đv ở bảng SKU.
**Ngoài (Phase sau):**
- **Upload ảnh SKU** (§7) — hiện chỉ là ô placeholder + cột `image_url` để trống.
- **SPU như thực thể riêng** (bảng `product_spus`, biến thể, thuộc tính chuẩn hoá) — hiện `spu_code` chỉ là chuỗi nhóm tự do (giống `product_id` nhưng nhẹ hơn).
- **Giá vốn bình quân / FIFO / `cost_layers`, sổ giá vốn, báo cáo lãi/lỗ theo đơn** — Phase 5/6. `inventory_levels.cost_price` & `skus.cost_price`/`ref_sale_price` là **dữ liệu nền** cho việc đó (xem §6).
- **Đồng bộ thông tin sản phẩm (cân nặng, kích thước, mô tả) ngược lên sàn** — Phase sau; cân nặng/kích thước hiện chỉ lưu để in tem & tham khảo.
- **Sửa SKU trên trang riêng** — *(2026-05-17 đã làm)* `CreateSkuPage` dùng cho cả `/inventory/skus/new` và `/inventory/skus/:id/edit`: sửa được mọi trường catalogue/PIM **trừ `sku_code`** (khoá), sửa cả **ghép nối SKU sàn** (`PATCH /skus/{id}` nhận `mappings[]` — thay thế toàn bộ liên kết); mục **Kho** ở chế độ sửa là **chỉ-xem** (đổi tồn vẫn qua sổ cái — `PATCH` không nhận `levels`). Xem `docs/06-frontend/create-sku-form.md`.

## 3. Luồng chính
SPA → Tồn kho → tab "Danh mục SKU" → "Thêm SKU" ⇒ điều hướng `/inventory/skus/new` (trang đầy đủ, không modal). Trang gồm 4 mục, mỏ neo bên phải:
1. **Thông tin cơ bản** — (ô ảnh placeholder, vô hiệu hoá) Mã SKU\*, Tên\*, Liên kết SPU (mã nhóm — tuỳ chọn), Danh mục, GTIN (nhập-Enter, ≤10), Đơn vị cơ bản\* (mặc định `PCS`), Giá vốn tham khảo (₫), Giá bán tham khảo (₫) → ngay dưới là dòng **"Lợi nhuận tham khảo: X ₫ · biên Y%"** tính tự động, Ngày bắt đầu bán, Ghi chú SKU hàng hoá (≤500).
2. **Ghép nối với SKU gian hàng** — danh sách dòng `(gian hàng, mã SKU sàn, seller SKU?, × số lượng)`; "Thêm ghép nối SKU gian hàng". Mỗi dòng ⇒ `channel_listing` `firstOrCreate` theo `(channel_account_id, external_sku_id)` rồi `SkuMappingService::setMapping(single)`.
3. **Thông tin cân nặng** — Cân nặng (g), Kích thước Dài/Rộng/Cao (cm).
4. **Kho** — bảng các kho của tenant, mỗi dòng: checkbox "tính kho này", Tồn kho (số đầu kỳ), Giá vốn (₫ — giá vốn theo kho). Tồn > 0 ⇒ tạo phiếu nhập `receipt` ghi chú "Tồn đầu kỳ" (`ref_type='sku_create'`) qua `InventoryLedgerService`.

"Lưu" ⇒ `POST /api/v1/skus` (một request) ⇒ tạo SKU + mappings + tồn đầu kỳ trong **một transaction** ⇒ về `/inventory?tab=skus`. "Hủy" ⇒ quay lại danh sách.

## 4. Hành vi & quy tắc
- **Bắt buộc:** `sku_code`, `name`, `base_unit`. Mã trùng trong tenant ⇒ `422 SKU_CODE_TAKEN` (như cũ).
- **GTIN:** mảng chuỗi, ≤10 phần tử, mỗi phần tử ≤64 ký tự. (Không validate checksum ở Phase này.)
- **Tiền:** `cost_price`, `ref_sale_price`, `levels[].cost_price` là **số nguyên VND đồng** (theo quy ước tiền của dự án). `ref_sale_price` có thể null.
- **`mappings[]`:** mỗi phần tử `{ channel_account_id, external_sku_id, seller_sku?, quantity? (mặc định 1) }`; `channel_account_id` không thuộc tenant ⇒ `422`. `setMapping` thay thế mapping cũ của listing (cho phép sửa). Tạo `channel_listing` tối thiểu (`currency='VND'`, `is_active=true`) nếu chưa có. Việc ghép phát `InventoryChanged` ⇒ debounce push tồn như thường lệ.
- **`levels[]`:** mỗi phần tử `{ warehouse_id, on_hand? (≥0), cost_price? (≥0) }`; `warehouse_id` không thuộc tenant ⇒ `422`. `on_hand>0` ⇒ một movement `goods_receipt` (`ref_type='sku_create'`, `ref_id=sku.id`, ghi chú "Tồn đầu kỳ"). `cost_price` (nếu có) ghi thẳng vào `inventory_levels.cost_price` của (sku, kho) đó. Không gửi `levels` ⇒ SKU tạo ra **không có dòng tồn nào** (giống SPEC-0003: dòng tồn sinh khi điều chỉnh/đơn về).
- **Idempotent / atomic:** toàn bộ tạo SKU + mappings + receipts nằm trong `DB::transaction` — lỗi giữa chừng ⇒ rollback hết.
- **Phân quyền:** `products.manage` để tạo/sửa SKU (mappings & levels đi kèm trong `POST /skus` cũng do quyền này — không tách quyền riêng cho phần đi kèm, vì đó là một thao tác "tạo SKU").
- **Lợi nhuận tham khảo:** `ref_profit_per_unit = ref_sale_price - cost_price` (null nếu chưa có giá bán); `ref_margin_percent = profit / ref_sale_price * 100` (làm tròn 0.1; null nếu giá bán 0/null). Đây là **tham khảo tĩnh** — báo cáo lãi/lỗ thật (Phase 6) sẽ dùng giá vốn theo kho/lô tại thời điểm xuất.

## 5. Dữ liệu (migration `2026_05_16_100001_extend_skus_for_pim`)
`skus` thêm: `spu_code` (string, null), `category` (string, null), `gtins` (json, null), `base_unit` (string16, default `PCS`), `ref_sale_price` (bigint, null), `sale_start_date` (date, null), `note` (text, null), `weight_grams` (uint, null), `length_cm`/`width_cm`/`height_cm` (decimal 8,2, null), `image_url` (string, null — **để trống, chờ §7**). Giữ nguyên `cost_price` (= giá vốn tham khảo) & `attributes` (json tự do cho thuộc tính khác).
`inventory_levels` thêm: `cost_price` (bigint, default 0) — giá vốn theo kho.
Không bảng mới. `inventory_movements` thêm giá trị `ref_type='sku_create'` (cột string tự do).

## 6. Đường nối cho tính năng tương lai
- **Giá vốn & lợi nhuận:** `skus.cost_price` (chuẩn), `skus.ref_sale_price`, `inventory_levels.cost_price` (theo kho) — Phase 5 thêm `cost_layers`/FIFO sẽ *bổ sung* lớp này chứ không thay; Phase 6 báo cáo lãi/lỗ đọc giá vốn theo kho/lô + giá bán thực trên đơn. API đã trả sẵn `ref_profit_per_unit`, `ref_margin_percent`.
- **Đồng bộ sản phẩm lên sàn:** `gtins`, `weight_grams`, `length/width/height_cm`, `category`, `base_unit` là các trường connector cần khi đẩy thông tin sản phẩm/biến thể lên TikTok/Shopee/Lazada.
- **SPU/biến thể:** `spu_code` là chỗ bám tạm; khi có bảng `product_spus` thì migrate `spu_code` → khoá ngoại.
- **In tem & chọn ĐVVC theo kích thước/cân nặng:** Phase 3 (fulfillment) đọc `weight_grams` + kích thước.

## 7. Upload ảnh SKU — Cloudflare R2 (đã làm 2026-05-16)
- **Lưu trữ:** disk `r2` (driver `s3`, endpoint R2) trong `config/filesystems.php`; `config/media.php` → `media.disk` = `r2` ở production, `public` ngoài prod (local/test không cần credential cloud). `app/Support/MediaUploader` đặt object key `tenants/<tenantId>/skus/<ULID>.<ext>`, trả URL công khai (`R2_URL`).
- **Cột:** `skus.image_url` (URL công khai để FE render) + `skus.image_path` (object key, để xoá/thay) — migration `..._add_image_path_to_skus`.
- **API:** `POST /api/v1/skus/{id}/image` (multipart `image`, quyền `products.manage`) — validate PNG/JPG/WEBP, ≤ `MEDIA_IMAGE_MAX_KB` (~5MB) → lưu, ghi 2 cột, xoá ảnh cũ → trả `SkuResource`. `DELETE /api/v1/skus/{id}/image` — xoá object + clear 2 cột.
- **FE:** trang "Thêm SKU đơn độc" thay ô placeholder bằng AntD `<Upload listType="picture-card">` — giữ file ở client (validate type/size), sau khi `POST /skus` thành công thì `POST /skus/{id}/image` rồi điều hướng; lỗi tải ảnh ⇒ cảnh báo (SKU vẫn đã tạo). Danh sách SKU hiển thị `image_url` (cột ảnh nhỏ).
- **Triển khai:** cần `composer require league/flysystem-aws-s3-v3` (đã thêm), đặt các biến `R2_*`/`MEDIA_DISK`, chạy migration mới — **chi tiết & cách kiểm tra: `docs/07-infra/cloudflare-r2-uploads.md`**.
- **Còn lại / mở rộng:** xoá SKU chưa xoá object ảnh (chờ job dọn rác xoá cứng); chưa tạo thumbnail nhiều kích thước; chưa hỗ trợ nhiều ảnh/gallery (sẽ là bảng `sku_images`); chưa đồng bộ ảnh ngược lên sàn; chưa cho sửa ảnh của SKU đã tạo từ danh sách (cần modal — đã có sẵn hook `useUploadSkuImage`/`useDeleteSkuImage`).

## 8. API & UI
**Endpoint** (cập nhật `docs/05-api/endpoints.md`):
- `POST /api/v1/skus` (`products.manage`) — body: `{ sku_code, name, product_id?, spu_code?, category?, barcode?, gtins?:[string≤10], base_unit?, cost_price?, ref_sale_price?, sale_start_date?, note?, weight_grams?, length_cm?, width_cm?, height_cm?, attributes?, mappings?:[{channel_account_id, external_sku_id, seller_sku?, quantity?}], levels?:[{warehouse_id, on_hand?, cost_price?}] }` ⇒ `201 { data: SkuResource }` (mã trùng ⇒ `422 SKU_CODE_TAKEN`; gian hàng/kho lạ ⇒ `422`).
- `PATCH /api/v1/skus/{id}` (`products.manage`) — nhận partial các trường cơ bản mới (không sửa mappings/levels qua đây).
- `GET /api/v1/skus` / `GET /api/v1/skus/{id}` — `SkuResource` nay có thêm: `spu_code, category, gtins, base_unit, ref_sale_price, ref_profit_per_unit, ref_margin_percent, sale_start_date, note, weight_grams, length_cm, width_cm, height_cm, image_url`; `levels[].cost_price`; `mappings[].channel_listing{ id, channel_account_id, external_sku_id, seller_sku, title }`.

**UI:** trang `/inventory/skus/new` (route mới) — chi tiết bố cục & state ở `docs/06-frontend/create-sku-form.md`. Tab "Danh mục SKU" của Tồn kho: nút "Thêm SKU" điều hướng sang trang này (bỏ modal cũ); bảng SKU thêm cột "Giá bán TK" và "LN/đv" (lợi nhuận/đơn vị + biên %).

## 9. Cách kiểm thử
- `tests/Feature/Inventory/InventoryApiTest::test_create_sku_with_catalogue_fields_mappings_and_opening_stock` — tạo SKU kèm GTIN/giá/cân nặng + 1 mapping + 1 dòng tồn đầu kỳ; kiểm `ref_profit_per_unit`, `available_total`, `levels[].cost_price`, `mappings[].channel_listing`, listing được tạo; gian hàng/kho lạ ⇒ `422`.
- Các test cũ của SPEC-0003/0004 vẫn xanh (POST /skus tối giản chỉ `sku_code/name` vẫn `201`).
