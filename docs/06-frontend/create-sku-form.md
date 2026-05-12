# Trang "Thêm SKU đơn độc" — `/inventory/skus/new`

**Status:** Living document · **Cập nhật:** 2026-05-16

> Tài liệu phát triển cho trang tạo SKU (`resources/js/pages/CreateSkuPage.tsx`). Mục tiêu: tạo SKU **trong một trang đầy đủ** đúng bố cục giao diện mẫu `ui_example/them_sku.png` — 4 mục + thanh mỏ neo (anchor) bên phải, làm trọn cả ghép SKU sàn lẫn tồn đầu kỳ trong một lần lưu. Đọc kèm [SPEC 0005](../specs/0005-sku-pim-and-create-form.md) (hành vi & API), [`overview.md`](overview.md) (kiến trúc FE), [`../05-api/endpoints.md`](../05-api/endpoints.md) (`POST /skus`).

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

## 2. State & dữ liệu
- **Form cơ bản + mappings:** một AntD `Form` (`layout="horizontal"`, `labelCol flex 170px`). `mappings` là `Form.List` (mỗi item `{channel_account_id, external_sku_id, seller_sku, quantity}` — `quantity` initialValue `1`). Cân nặng/kích thước là field thường (`weight_grams`, `length_cm`, `width_cm`, `height_cm`).
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

⇒ `useCreateSku().mutate(payload)`:
- thành công ⇒ `message.success('Đã tạo SKU')` + `navigate('/inventory?tab=skus')`. Hook invalidate `['skus']`, `['inventory-levels']`, `['channel-listings']`.
- lỗi ⇒ `message.error(errorMessage(e))` (vd `422 SKU_CODE_TAKEN`, gian hàng/kho lạ).
- `validateFields` reject (thiếu trường bắt buộc) ⇒ `message.error('Vui lòng kiểm tra các trường bắt buộc.')` (AntD cũng cuộn tới field lỗi).

## 4. Ô ảnh (Cloudflare R2)
Mục "Thông tin cơ bản" có AntD **`<Upload listType="picture-card">`** (`beforeUpload` validate PNG/JPG/WEBP ≤5MB rồi `return false` để **không** tự upload — file giữ trong state `imageFile`/`imagePreview`). Khi bấm "Lưu": tạo SKU (`POST /skus`) → nếu có ảnh thì `useUploadSkuImage().mutateAsync({skuId, file})` (`POST /skus/{id}/image`, multipart) → điều hướng. Tải ảnh lỗi ⇒ `message.warning` (SKU vẫn đã tạo). Ảnh lưu trên Cloudflare R2 (xem [SPEC 0005 §7](../specs/0005-sku-pim-and-create-form.md) và [`../07-infra/cloudflare-r2-uploads.md`](../07-infra/cloudflare-r2-uploads.md)). Bảng SKU (tab Danh mục SKU) có cột ảnh nhỏ hiển thị `image_url`. Hook sẵn có để sửa ảnh SKU đã tạo: `useUploadSkuImage`, `useDeleteSkuImage` (chưa gắn UI cho danh sách — follow-up).

## 5. Bảng SKU (tab "Danh mục SKU") — thay đổi kèm theo
Thêm 2 cột tận dụng dữ liệu PIM mới: **"Giá bán TK"** (`ref_sale_price`, `—` nếu null) và **"LN/đv"** (`ref_profit_per_unit` + ` · {ref_margin_percent}%`, màu theo dấu). Cột "Mã SKU" hiện thêm dòng phụ `SPU: …` nếu có `spu_code`. Cột "Barcode" bỏ khỏi bảng (vẫn còn ở API/`PATCH`).

## 6. Việc còn lại / mở rộng
- **Sửa SKU trên trang riêng:** hiện trang chỉ *tạo*; có thể tái dùng `CreateSkuPage` cho `/inventory/skus/:id/edit` (nạp `useSku(id)`, prefill, gọi `PATCH` cho phần cơ bản; mappings/levels cần endpoint sửa riêng — chưa có).
- **SPU picker thực:** khi có bảng `product_spus`, đổi ô "Liên kết SPU" từ `Input` sang `Select` có tìm kiếm + nút tạo nhanh.
- **Upload ảnh** (§4). **Đồng bộ thông tin sản phẩm lên sàn** (cân nặng/kích thước/GTIN/danh mục) — Phase sau.
