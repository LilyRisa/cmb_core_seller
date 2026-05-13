# Trang Đơn hàng — panel "Lọc" (chip rows, kiểu BigSeller)

**Status:** Living document · **Cập nhật:** 2026-05-15

> Tài liệu phát triển cho bộ lọc đơn ở `OrdersPage`. Mục tiêu: bộ lọc là **một group inline duy nhất** ("Lọc"), trong đó các facet dạng danh sách (sàn / gian hàng / ĐVVC / thời gian) hiển thị **chip có số lượng** — bấm chọn, **không** dùng ô input/dropdown. Tham chiếu giao diện mẫu: `ui_example/cho_xu_ly.png`, `ui_example/cho_lay_hang.png`, `ui_example/cho_in.png`. Đọc kèm [`overview.md`](overview.md) (kiến trúc FE) và [`../05-api/endpoints.md`](../05-api/endpoints.md) (`GET /orders`, `GET /orders/stats`).

## 1. Bố cục màn hình

```
PageHeader: "Đơn hàng"            [Tạo đơn] [Đồng bộ đơn] [Làm mới]
┌─ Card: status tabs ───────────────────────────────────────────────┐
│ Tất cả · Chờ xử lý (n) · Đang xử lý (n) · … · Có vấn đề (n)        │  ← ORDER_STATUS_TABS, mỗi tab có badge từ stats.by_status
└───────────────────────────────────────────────────────────────────┘
┌─ Card title="Lọc" ────────────────────────────────────────────────┐
│ [Mã đơn / người mua ▾]  [ô tìm kiếm ............................🔍]│  ← dropdown chọn field + 1 input → set q | sku | product
│ Sàn TMĐT   [Tất cả] [TikTok Shop (20)] [Shopee (29)] …            │  ← FilterChipRow, đếm từ stats.by_source
│ Gian hàng  [Tất cả] [cmb audio 1 (8)] [CMB audio store (19)] …    │  ← stats.by_shop × ChannelAccount.name
│ Vận chuyển [Tất cả] [TikTok-VN-J&T Express (17)] [GHN (3)] …       │  ← stats.by_carrier
│ Thời gian  [Tất cả] [Hôm nay] [Hôm qua] [7 ngày] [30/90 ngày]  …  │  ← TIME_PRESETS + RangePicker (tuỳ chỉnh) ở "extra"
│                                                  [Sắp xếp ▾]      │
└───────────────────────────────────────────────────────────────────┘
┌─ Card: bảng đơn ──────────────────────────────────────────────────┐
│ Mã đơn (+ sàn + tên shop + COD + Lỗi) · Sản phẩm · Người mua ·    │
│ ĐVVC · Tổng tiền · Trạng thái · Đặt lúc                           │
└───────────────────────────────────────────────────────────────────┘
```

Mọi state lọc nằm trong **query string** của URL (`?source=tiktok&channel_account_id=3&carrier=...&placed_from=...&q=...&tab=...&sort=...&page=...`) ⇒ chia sẻ link / back-forward giữ nguyên bộ lọc. Helper `set(patch)` trong `OrdersPage` merge vào `useSearchParams` và reset `page=1` khi đổi filter.

## 2. Component `<FilterChipRow>` (`resources/js/components/FilterChipRow.tsx`)

```ts
interface ChipItem { value: string; label: ReactNode; count?: number | null; icon?: ReactNode }

<FilterChipRow
  label="Sàn TMĐT"
  items={sourceChips}                       // ChipItem[]
  value={source || undefined}               // chip đang chọn (string) — undefined = "Tất cả"
  onChange={(v) => set({ source: v })}      // v = value chip, hoặc undefined khi bỏ chọn
  allLabel="Tất cả"                         // (mặc định "Tất cả")
  extra={<RangePicker .../>}                // (tuỳ chọn) control phụ bên phải hàng
/>
```

- Render: nhãn trái (`width 92px`, xám) + danh sách chip wrap. Chip dùng AntD `Tag.CheckableTag` (đã có sẵn style "checked" = nền/chữ tím primary). Hàng phân cách bằng `border-bottom: 1px dashed`.
- **Single-select**: bấm 1 chip → set value đó; bấm lại chip đang active (hoặc "Tất cả") → clear (`undefined`). (BigSeller cho multi-select; bản này v1 single — đủ cho đa số case. Mở rộng multi: đổi `value` thành `string[]`, `onChange` trả mảng, query param dạng csv.)
- `count != null` ⇒ hiện ` (n)` mờ sau nhãn. `items === []` ⇒ hiện `—`.

## 3. Hợp đồng đếm — `GET /api/v1/orders/stats` (faceted counts)

Trả:
```json
{ "data": {
  "total": N, "has_issue": N,
  "by_status":  { "<mã chuẩn>": N, ... },
  "by_source":  [ { "source": "tiktok", "count": N }, ... ],          // sắp xếp count desc
  "by_shop":    [ { "channel_account_id": 3, "count": N }, ... ],
  "by_carrier": [ { "carrier": "TikTok-VN-J&T Express", "count": N }, ... ]
} }
```

Quy tắc đếm (xem `OrderController::stats()` + `applyFilters($request, $query, skip: [...])`):
- `by_status` / `total` / `has_issue`: áp **mọi** filter **trừ** `status` & `has_issue` ⇒ tab trạng thái hiển thị số của chính nó.
- Chip rows lọc theo **cây cha → con**: `source` (nền tảng) là cha của `channel_account_id` (gian hàng); cả hai là cha của `carrier` (vận chuyển). Mỗi facet "cởi" filter của **chính nó + các con** (để cha vẫn liệt kê đủ phương án và đổi cha không bị kẹt bởi giá trị con cũ); các filter cha vẫn áp.
  - `by_source`: skip `source`, `channel_account_id`, `carrier` ⇒ luôn hiện đủ nền tảng.
  - `by_shop`: skip `channel_account_id`, `carrier` ⇒ chỉ hiện shop **của nền tảng đang chọn** (nếu có).
  - `by_carrier`: skip `carrier` ⇒ chỉ hiện ĐVVC **của nền tảng + gian hàng đang chọn** (nếu có).
  - Các filter còn lại — `q`/`sku`/`product`/`placed_from`/`placed_to`/`status`/`has_issue` — vẫn áp cho cả 3 ⇒ chip phản ánh tab trạng thái + thanh tìm + khoảng ngày hiện tại.
- FE đồng bộ: `OrdersPage` clear con khi đổi cha (đổi `source` ⇒ clear `channel_account_id` + `carrier`; đổi `channel_account_id` ⇒ clear `carrier`) để URL không còn giá trị con không thuộc nhánh cha mới.

FE: `useOrderStats(statsFilters)` (trong `lib/orders.tsx`) gửi đúng các filter hiện tại lên `/orders/stats`; kiểu trả là `OrderStats`. `by_shop` chỉ trả `channel_account_id` — FE map sang tên qua `useChannelAccounts()` (`account.name` = `display_name ?? shop_name ?? external_shop_id`). `by_source` map sang nhãn qua `CHANNEL_META`.

## 4. Ô tìm kiếm (dropdown field + 1 input)

`SEARCH_FIELDS = [{q: 'Mã đơn / người mua'}, {sku: 'Mã SKU'}, {product: 'Tên sản phẩm'}]`. Dropdown chọn field hiện tại (`searchField` = param nào đang có giá trị, mặc định `q`); `onSearch(field, value)` clear cả 3 param rồi set param tương ứng. Backend:
- `q` ⇒ `Order::scopeSearch` (LIKE `order_number` / `external_order_id` / `buyer_name`).
- `sku` ⇒ `whereHas('items', seller_sku LIKE ...)`.
- `product` ⇒ `whereHas('items', name LIKE ...)`.

(Tìm theo SĐT đầy đủ: không trong SQL — `buyer_phone` mã hoá; để search engine ở Phase 7. Có thể tìm khách theo SĐT ở trang Khách hàng.)

## 5. Hàng "Thời gian"

`TIME_PRESETS = [today, yesterday, 7d, 30d, 90d]` — mỗi preset trả `[from,to]` (`YYYY-MM-DD`) tính bằng `dayjs`. Bấm chip ⇒ `set({placed_from, placed_to})`; bấm "Tất cả" ⇒ clear. Chip active = preset có `[from,to]` khớp đúng `placed_from`/`placed_to` hiện tại; nếu có khoảng ngày nhưng không khớp preset nào ⇒ không chip nào active và khoảng đó hiện trong `<RangePicker>` (đặt ở `extra` của hàng) để chỉnh tay.

## 6. Recipe — thêm một hàng chip mới (facet mới)

1. **Backend `applyFilters`**: thêm nhánh `if ($use('newkey') && $v = $request->query('newkey')) { $query->where(...) }`.
2. **Backend `stats()`**: thêm vào danh sách `skip` của `$facetBase` (nếu facet này cũng "không tự constrain"), rồi `$byNew = (clone $facetBase)->selectRaw('col, count(*) c')->groupBy('col')->pluck('c','col')->map(...)->values()->all();` → thêm `'by_new' => $byNew` vào response.
3. **FE `lib/orders.tsx`**: thêm field vào `OrderStats` + (nếu là param mới) vào `OrderFilters`.
4. **FE `OrdersPage`**: đọc param, build `ChipItem[]` từ `stats.by_new`, render `<FilterChipRow label="…" items={...} value={...} onChange={(v) => set({ newkey: v })} />`, thêm `newkey` vào `filters` & `statsFilters` memo.
5. (nếu cần lọc theo cột đặc thù — vd carrier denormalized) thêm migration/cột như `orders.carrier`.

## 7. Ngoài phạm vi (đang chưa làm — đối chiếu BigSeller)

- **Chip multi-select** (BigSeller chọn nhiều sàn/shop cùng lúc) — v1 single-select.
- **"Bộ lọc khác"** (lưới dropdown: COD?, yêu cầu hoá đơn, hình thức lấy hàng, loại SKU, đơn hoả tốc, nhân viên kinh doanh, danh sách đen…) — phần lớn phụ thuộc các tính năng Phase 3/6 (fulfillment, finance, rules) — bổ sung khi các module đó lên.
- **"Trạng thái in"** (chip: chưa in / đã in phiếu giao hàng / hoá đơn / chứng từ…) — Phase 3 (Fulfillment / print jobs).
- **"+ Lưu mục lọc"** (preset bộ lọc) — backlog (lưu vào `tenant_settings` hoặc per-user).
- **Kho** (checkbox `Tất cả` / từng kho) — sẽ thêm khi đơn được gắn kho cụ thể (Phase 5 — hiện trừ ở 1 kho mặc định).
- Đếm facet "đúng kiểu BigSeller" (mỗi facet áp tất cả filter của các facet *khác*, 4 query) — bản này gộp về 1 base chung để gọn; đủ dùng.
