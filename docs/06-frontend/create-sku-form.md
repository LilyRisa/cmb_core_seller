# Trang "Thêm / Sửa SKU đơn độc" — `/inventory/skus/new` & `/inventory/skus/:id/edit`

**Status:** Living document · **Cập nhật:** 2026-05-17

> Tài liệu phát triển cho trang tạo **và sửa** SKU (`resources/js/pages/CreateSkuPage.tsx` — một component dùng cho cả 2 route). Mục tiêu: làm việc với SKU **trong một trang đầy đủ** đúng bố cục giao diện mẫu `ui_example/them_sku.png` — 4 mục + thanh mỏ neo (anchor) bên phải. Trang **tạo** làm trọn cả ghép SKU sàn lẫn tồn đầu kỳ trong một lần lưu (`POST /skus`); trang **sửa** dùng cùng layout, **sửa được mọi trường trừ `Mã SKU`** (khoá định danh — bị disable), ghép nối SKU sàn vẫn sửa được (qua `PATCH /skus/{id}` với `mappings[]` — thay thế toàn bộ liên kết), riêng mục "Kho" thành **chỉ-xem** (đổi tồn ở tab Tồn theo SKU). Đọc kèm [SPEC 0005](../specs/0005-sku-pim-and-create-form.md) (hành vi & API), [`overview.md`](overview.md) (kiến trúc FE), [`../05-api/endpoints.md`](../05-api/endpoints.md) (`POST /skus`, `PATCH /skus/{id}`).

## 1. Bố cục màn hình
```
PageHeader: ← "SKU hàng hoá › Thêm SKU đơn độc"                 [Hủy] [Lưu]
┌─ col lg=19 ───────────────────────────────────────┐  ┌─ col lg=5 ────┐
│ Card #basic  "Thông tin cơ bản"                   │  │ Anchor (sticky)│
│  [ô ảnh ⊘]  Mã SKU*  Tên*  Liên kết SPU  Danh mục │  │ • Thông tin cơ bản
│             GTIN(tags ≤10)  Đơn vị cơ bản*        │  │ • Ghép nối SKU gian hàng
│             Giá vốn TK (₫)  Giá bán TK (₫)        │  │ • Thông tin cân nặng
│             → Lợi nhuận TK: X ₫ · biên Y%         │  │ • Kho
│             Ngày bắt đầu bán   Ghi chú (≤500)     │  └───────────────┘
│ Card #mappings "Ghép nối với SKU gian hàng"       │
│  Alert info. Form.List dòng:                      │
│   [Gian hàng ▾][Mã SKU sàn][Seller SKU?][× SL][🗑]│
│  [+ Thêm ghép nối SKU gian hàng]                  │
│ Card #weight "Thông tin cân nặng"                 │
│  Cân nặng (g)   Kích thước [Dài][Rộng][Cao] (cm)  │
│ Card #warehouses "Kho"                            │
│  Table: [☑ Kho] [Tồn kho] [Giá vốn (₫)]           │  ← 1 dòng / kho của tenant
└───────────────────────────────────────────────────┘
```
- Route mới trong `App.tsx`: `inventory/skus/new` → `<CreateSkuPage/>`. Tab "Danh mục SKU" của `InventoryPage` (`SkusTab`): nút **"Thêm SKU"** `navigate('/inventory/skus/new')` (bỏ modal tạo SKU cũ).
- `Hủy` / sau khi lưu thành công ⇒ `navigate('/inventory?tab=skus')`.
- **Chế độ tạo vs sửa:** `useParams().id` có ⇒ chế độ **sửa** (`isEdit`). Khi sửa: `useSku(id)` nạp dữ liệu (chờ ⇒ `Skeleton`; không tìm thấy ⇒ `Alert`); `useEffect([editing.id])` `form.setFieldsValue(...)` prefill (gồm `mappings` map từ `editing.mappings[].channel_listing`), set `imagePreview = editing.image_url`; field `sku_code` `disabled`; mục "Kho" thay bằng bảng tồn **chỉ-xem** (`editing.levels` → Thực có / Đang giữ / Khả dụng / Giá vốn) + link sang tab Tồn theo SKU; thêm field `is_active` (checkbox). Tiêu đề "SKU hàng hoá › Sửa SKU — `<mã>`". Tab "Danh mục SKU" của `InventoryPage`: nút ✎ mỗi dòng ⇒ `navigate('/inventory/skus/:id/edit')` (không còn modal sửa).

## 2. State & dữ liệu
- **Form cơ bản + mappings:** một AntD `Form` (`layout="horizontal"`, `labelCol flex 170px`). `mappings` là `Form.List` (mỗi item `{channel_account_id, external_sku_id, seller_sku, quantity, _listing?}` — `quantity` initialValue `1`; `_listing` chỉ giữ client để hiển thị, không gửi server). **Mỗi dòng ghép nối** (bọc trong `Form.Item noStyle shouldUpdate` để re-render theo giá trị dòng): (1) Select **gian hàng** — đổi gian hàng ⇒ reset `external_sku_id`/`seller_sku`/`_listing`; (2) `ChannelListingPicker` (component trong file) — Select `showSearch filterOption={false}` gọi `useChannelListings({channel_account_id, q})` ⇒ option = **ảnh + tên SP + biến thể + mã SKU sàn + tồn trên sàn**; chọn xong điền `external_sku_id` (form-controlled) + `seller_sku` + `_listing`; listing chưa đồng bộ thì gõ mã rồi chọn "Dùng mã thủ công"; (3) `InputNumber` **× số lượng** (combo/lốc — `tooltip` `QuestionCircleOutlined`: số SKU hàng hoá trong 1 SKU sàn, để `1` nếu bán lẻ từng cái, **không phải số tồn** — tồn lấy từ SKU, tự đẩy lên sàn); (4) nút xoá. Dưới dòng: preview **ảnh + tên + biến thể + gian hàng + SKU sàn + tồn trên sàn** của listing đã chọn (hoặc cảnh báo "listing chưa đồng bộ" nếu chỉ có mã). `seller_sku` là `Form.Item hidden`. Card có `Alert` nhắc "tồn tự đồng bộ — không cần nhập số tồn ở đây". Cân nặng/kích thước là field thường (`weight_grams`, `length_cm`, `width_cm`, `height_cm`).
- **Mục "Kho":** **không** nằm trong Form — dùng React state `whRows: Record<warehouseId, {included:boolean; on_hand:number; cost_price:number}>` (mặc định mỗi kho `included:true, 0, 0`). Bảng render checkbox + 2 `InputNumber<number>` controlled.
- **Lợi nhuận tham khảo:** `Form.Item shouldUpdate` đọc `cost_price`/`ref_sale_price` ⇒ hiện `profit = sale - cost` và `margin = profit/sale*100` (xanh nếu ≥0, đỏ nếu <0). Khớp `ref_profit_per_unit`/`ref_margin_percent` mà backend trả về (xem SPEC 0005 §4).
- **Nguồn options:** `useWarehouses()` (bảng Kho), `useChannelAccounts()` (`.data?.data` → options gian hàng cho `mappings`). `BASE_UNITS` là hằng trong file (`PCS, Cái, Bộ, Hộp, Thùng, Đôi, Kg, Gói, Cuộn, Mét`) — Select đơn giản, mặc định `PCS`.

## 3. Submit
`form.validateFields()` ⇒ build `CreateSkuPayload` (kiểu trong `lib/inventory.tsx`):
- chuỗi: `.trim()`, rỗng → `null` (`spu_code`, `category`, `note`, `seller_sku`).
- `gtins`: `(v.gtins ?? []).map(trim).filter(Boolean)`.
- `sale_start_date`: `Dayjs` → `'YYYY-MM-DD'` hoặc `null`.
- số: `cost_price ?? 0`, `ref_sale_price ?? null`, `weight_grams/length_cm/width_cm/height_cm ?? null`.
- `mappings`: lọc dòng có `channel_account_id` **và** `external_sku_id` ⇒ `{channel_account_id, external_sku_id, seller_sku||null, quantity||1}`.
- `levels`: từ `warehouses` lọc `whRow(id).included` ⇒ `{warehouse_id, on_hand||0, cost_price||0}`.

⇒ **tạo:** `useCreateSku().mutate(payload)`. **sửa:** build `UpdateSkuPayload` y hệt (trừ `sku_code` — **không gửi**, vì bị khoá; thêm `is_active`; luôn gửi `mappings` (kể cả `[]`) ⇒ thay thế toàn bộ liên kết; **không gửi `levels`**) ⇒ `useUpdateSku().mutate({id, patch})`.
- thành công ⇒ áp ảnh (xem §4) → `message.success('Đã tạo SKU' | 'Đã lưu SKU')` + `navigate('/inventory?tab=skus')`. Hook invalidate `['skus']`, `['sku']`, `['inventory-levels']`, (`useCreateSku` còn `['channel-listings']`).
- lỗi ⇒ `message.error(errorMessage(e))` (vd `422 SKU_CODE_TAKEN`, gian hàng/kho lạ).
- `validateFields` reject (thiếu trường bắt buộc) ⇒ `message.error('Vui lòng kiểm tra các trường bắt buộc.')` (AntD cũng cuộn tới field lỗi).

## 4. Ô ảnh (Cloudflare R2)
Mục "Thông tin cơ bản" có AntD **`<Upload listType="picture-card">`** (`beforeUpload` validate PNG/JPG/WEBP ≤5MB rồi `return false` để **không** tự upload — file giữ trong state `imageFile`/`imagePreview`; `onRemove` đặt `imageDeleted=true` + xoá preview). **Tạo:** `imagePreview` ban đầu rỗng. **Sửa:** `imagePreview` ban đầu = `editing.image_url`. Khi bấm "Lưu" và `POST/PATCH /skus` xong ⇒ `applyImage(skuId)`: nếu có `imageFile` ⇒ `useUploadSkuImage().mutateAsync({skuId, file})` (`POST /skus/{id}/image`); else nếu sửa & `imageDeleted` & SKU vốn có ảnh ⇒ `useDeleteSkuImage().mutateAsync(skuId)` (`DELETE /skus/{id}/image`) → rồi điều hướng. Lỗi áp ảnh ⇒ `message.warning` (SKU vẫn đã lưu). Ảnh lưu trên Cloudflare R2 (xem [SPEC 0005 §7](../specs/0005-sku-pim-and-create-form.md) và [`../07-infra/cloudflare-r2-uploads.md`](../07-infra/cloudflare-r2-uploads.md)). Bảng SKU (tab Danh mục SKU) có cột ảnh nhỏ hiển thị `image_url`. *(Một dòng ad-hoc của đơn thủ công dùng `POST /media/image` chung — xem `docs/03-domain/manual-orders-and-finance.md`.)*

## 5. Bảng SKU (tab "Danh mục SKU") — thay đổi kèm theo
Thêm 2 cột tận dụng dữ liệu PIM mới: **"Giá bán TK"** (`ref_sale_price`, `—` nếu null) và **"LN/đv"** (`ref_profit_per_unit` + ` · {ref_margin_percent}%`, màu theo dấu). Cột "Mã SKU" hiện thêm dòng phụ `SPU: …` nếu có `spu_code`. Cột "Barcode" bỏ khỏi bảng (vẫn còn ở API/`PATCH`). Tên SKU & mã SKU dài ⇒ `ellipsis` (tooltip đầy đủ) — không vỡ layout.

**Sửa / xoá SKU (`products.manage`):** mỗi dòng có nút ✎ ⇒ `navigate('/inventory/skus/:id/edit')` (trang đầy đủ ở §1 — sửa **mọi trường trừ Mã SKU**, sửa cả ghép nối SKU sàn, mục Kho chỉ-xem) và nút 🗑 ⇒ `Popconfirm` ⇒ `DELETE /skus/{id}` qua `useDeleteSku` (chặn ở client nếu `on_hand_total`/`reserved_total` ≠ 0, server cũng trả `409`; gỡ luôn các liên kết SKU sàn). Inputs tên/mã có `maxLength` (255 / 100) khớp validate backend.

## 6. Việc còn lại / mở rộng
- **`PATCH /skus/{id}` không sửa tồn (`levels`)** — đổi tồn vẫn qua sổ cái (tab Tồn theo SKU / phiếu nhập-xuất). Nếu sau này muốn nhập/sửa tồn ngay trong trang sửa SKU thì cần thống nhất ngữ nghĩa (phiếu điều chỉnh có ghi chú) trước khi cho `PATCH` nhận `levels`.
- **`SkuPicker`/`SkuPickerField` (`components/SkuPicker.tsx`):** bộ chọn master SKU dạng **danh sách** (ảnh · tên · mã + ô tìm kiếm) thay cho `<Select>` — dùng ở `LinkSkusModal`, modal "Ghép SKU" của tab listing, dòng "Phiếu nhập/xuất hàng loạt", và `CreateOrderPage`. Một master SKU có thể ghép với nhiều SKU sàn (mỗi dòng ghép nối là một picker). Mô phỏng `ui_example/ghep_noi_sku.png`.
- **SPU picker thực:** khi có bảng `product_spus`, đổi ô "Liên kết SPU" từ `Input` sang `Select` có tìm kiếm + nút tạo nhanh.
- **Upload ảnh** (§4). **Đồng bộ thông tin sản phẩm lên sàn** (cân nặng/kích thước/GTIN/danh mục) — Phase sau.
