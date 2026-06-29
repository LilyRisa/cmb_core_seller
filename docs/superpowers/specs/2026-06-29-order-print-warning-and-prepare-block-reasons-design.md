# Cảnh báo "không in được" + Lý do cụ thể khi đơn chưa thể chuẩn bị

**Ngày:** 2026-06-29
**Trạng thái:** Đã duyệt thiết kế — chờ review spec trước khi lập kế hoạch triển khai.

## Bối cảnh & mục tiêu

Trên danh sách đơn (`OrdersPage`), người bán gặp 2 điểm khó hiểu:

1. **Đơn đã ở trạng thái cuối nhưng không thể in tem.** Đơn `Hoàn tất` / `Đã huỷ` / `Đã trả-hoàn` mà chưa từng lưu phiếu giao hàng (tem) thì không thể in được nữa, nhưng UI không nói rõ — người dùng cứ chọn rồi bấm "In phiếu" mà không hiểu vì sao trống.

2. **Đơn "đang chờ" chung chung.** Nhiều đơn hiển thị trạng thái chuẩn "Chờ xử lý" nhưng thực chất chưa thể chuẩn bị hàng vì lý do cụ thể của sàn (chờ thanh toán, sàn giữ đơn trong thời gian remorse, đang xử lý yêu cầu huỷ, sàn tự xử lý kho FBT...). Thông báo "đang chờ" không cho biết phải làm gì / chờ gì.

Mục tiêu: hiển thị **cảnh báo + thông báo rõ ràng** cho cả 2 tình huống, lấy nguồn lý do từ **tài liệu chính chủ** của Shopee/Lazada/TikTok.

## Luật vàng phải tuân theo

- **Core không biết tên sàn.** Mọi ánh xạ `raw_status → lý do` phải nằm ở tầng connector (`Integrations/Channels/*`), giống `mapStatus()` — không được có `match($source)`/`if ($provider === ...)` trong module Orders/Fulfillment.
- Tiền VND số nguyên, chuỗi hiển thị tiếng Việt, định danh tiếng Anh.
- Tính toán per-row trong API Resource phải **rẻ, không gọi API sàn, không N+1** (chỉ match chuỗi trên dữ liệu đã có sẵn trên order).

---

## Part A — Cảnh báo "không thể in đơn được"

### Quy tắc
Hiển thị cảnh báo khi: **đơn ở trạng thái cuối VÀ chưa có tem đã lưu.**

- `is_terminal === true` — gồm `Completed` (Hoàn tất), `Cancelled` (Đã huỷ), `ReturnedRefunded` (Đã trả/hoàn). Đây đúng là "thành công, hoàn, hủy".
- `shipment?.has_label !== true` — chưa lưu phiếu (`shipment.label_path` rỗng), kể cả khi không có shipment.
- Đơn terminal mà **đã** có tem (`has_label=true`) ⇒ KHÔNG cảnh báo (vẫn in lại được bình thường).

Cả 2 field (`is_terminal`, `shipment.has_label`) **đã có sẵn** trong `OrderResource` → **không cần đổi backend cho Part A**.

### Frontend
File: `app/resources/js/pages/OrdersPage.tsx`, trong cột "Đơn hàng" (chỗ render các badge: `out_of_stock`, `has_issue`, `PrintCountBadge`...).

```tsx
{o.is_terminal && !o.shipment?.has_label && (
  <Tooltip title="Đơn đã ở trạng thái cuối (hoàn tất/đã huỷ/đã trả) mà chưa từng lưu phiếu giao hàng — không thể in đơn được.">
    <Tag color="default" icon={<WarningOutlined />} style={{ marginInlineEnd: 0 }}>Không in được</Tag>
  </Tooltip>
)}
```

- Dùng `WarningOutlined` (đúng quy ước icon font, không emoji).
- Đây là **cảnh báo nhìn thấy được** (tag có icon) + tooltip chứa thông báo đầy đủ — đúng yêu cầu "hiển thị cảnh báo, di chuột vào thấy thông báo".

---

## Part B — Lý do cụ thể thay cho "đang chờ" chung chung

### B.1 — Bộ mã lý do chuẩn (core, không gắn tên sàn)

File mới: `app/app/Support/Enums/PrepareBlockReason.php` — enum string, mỗi case có `label()` tiếng Việt.

| Case (value) | `label()` |
|---|---|
| `awaiting_payment` | Chờ người mua thanh toán |
| `platform_hold` | Sàn đang tạm giữ đơn (thời gian người mua được huỷ / duyệt COD) — chưa cho chuẩn bị |
| `platform_fulfilled` | Đơn do sàn xử lý kho (FBT/FBL) — bạn không cần chuẩn bị |
| `cancel_in_progress` | Đang xử lý yêu cầu huỷ — chưa thể chuẩn bị |
| `platform_processing` | Sàn đang xử lý đơn — chưa thể chuẩn bị |

Ghi chú: **âm tồn** (out_of_stock) đã có cảnh báo riêng nên KHÔNG đưa vào enum này.

### B.2 — Ánh xạ trong connector (nguồn lý do)

Thêm vào contract `ChannelConnector` (mẫu y hệt `mapStatus` — không cần `AuthContext`, là nơi DUY NHẤT chuỗi trạng thái sàn được phép xuất hiện):

```php
/**
 * Lý do đơn CHƯA thể chuẩn bị hàng, suy từ raw status của sàn.
 * Trả null khi đơn đã sẵn sàng chuẩn bị, hoặc đã/đang giao, hoặc terminal (Part A lo terminal).
 * Thuần map chuỗi — KHÔNG gọi API.
 *
 * @param array<string,mixed> $rawOrder  để đọc fulfillment_type (vd FBT) khi cần
 */
public function prepareBlockReason(string $rawStatus, array $rawOrder = []): ?PrepareBlockReason;
```

Bảng ánh xạ (trích tài liệu chính chủ, đã đọc bằng trình duyệt 2026-06-29):

**TikTok** (`partner.tiktokshop.com/docv2` — Order API overview):
- `UNPAID` → `awaiting_payment`
- `ON_HOLD` → `platform_hold` (doc nêu rõ: "ON_HOLD orders are not allowed to be fulfilled", remorse 1 giờ)
- `fulfillment_type = FULFILLMENT_BY_TIKTOK` (FBT) → `platform_fulfilled`
- `AWAITING_SHIPMENT`, `PARTIALLY_SHIPPING` → `null` (cho chuẩn bị)
- còn lại (AWAITING_COLLECTION/IN_TRANSIT/DELIVERED/COMPLETED/CANCELLED) → `null`

**Lazada** (`open.lazada.com` — GetOrders status enum + pack/document flow):
- `unpaid` → `awaiting_payment`
- `topack` → `platform_processing`
- `pending` → `null` (đây là trạng thái để bấm Pack `/order/fulfill/pack`)
- còn lại → `null`

**Shopee** (`open.shopee.com/developer-guide/229` — Order Management §10/§8):
- `UNPAID` → `awaiting_payment`
- `IN_CANCEL` → `cancel_in_progress`
- `READY_TO_SHIP`, `RETRY_SHIP` → `null` (cho chuẩn bị / chuẩn bị lại)
- còn lại → `null`

**Manual** (đơn tự tạo, không có `channel_account_id`): connector `manual` trả `null` (preparability đã chặn bằng out_of_stock / terminal ở `assertPreparable`).

Mỗi connector chưa override ⇒ mặc định trả `null` (đặt default ở base/trait nếu có, hoặc khai trong từng connector hiện hữu).

### B.3 — Hợp nhất với logic chặn fulfill sẵn có (bỏ trùng lặp)

Hiện `ShipmentService::assertChannelOrderFulfillable()` đọc `config("integrations.{provider}.unfulfillable_raw_statuses")` rồi ném 1 thông báo VN **chung chung**. Thay bằng dùng `prepareBlockReason`:

```php
private function assertChannelOrderFulfillable(Order $order): void
{
    $account = ChannelAccount::query()->find($order->channel_account_id);
    if (! $account) { return; }
    $reason = $this->registry->for($account->provider)
        ->prepareBlockReason((string) $order->raw_status, (array) $order->raw /* hoặc field chứa fulfillment_type */);
    if ($reason !== null) {
        throw new RuntimeException($reason->label());
    }
}
```

- Lợi: FE và BE dùng **chung một nguồn sự thật**; thông báo chặn khi bấm "Chuẩn bị hàng" cũng trở nên cụ thể (đúng lý do) thay vì chung chung.
- `config(...unfulfillable_raw_statuses)` trở nên dư thừa cho mục đích này — giữ lại làm fallback tùy chọn, hoặc dọn ở bước plan (quyết định khi triển khai, mặc định: connector là nguồn chính).
- Lưu ý tenant context: `assertChannelOrderFulfillable` chạy trong luồng HTTP đồng bộ (có tenant) nên `ChannelAccount::find()` OK — KHÁC bug job thiếu `runAs` đã biết. Việc resolve connector chỉ để map thuần, không gọi sàn.

### B.4 — Expose ra API

File: `app/app/Modules/Orders/Http/Resources/OrderResource.php` — thêm field:

```php
'prepare_block_reason' => $this->prepareBlockReasonLabel(), // string|null (nhãn VN)
```

- Tính qua helper resolve connector theo `provider`/`source` của đơn rồi gọi `prepareBlockReason(...)->label()`.
- Chỉ tính khi đơn là pre-shipment & **chưa có shipment** (chưa chuẩn bị). Đơn đã có shipment / đã giao / terminal ⇒ `null`.
- Đơn manual / source không resolve được connector ⇒ `null` (nuốt lỗi, không vỡ list).
- Rẻ: chỉ match chuỗi trên `raw_status` + `raw`/`fulfillment_type` đã nằm sẵn trên order (không query thêm, không gọi sàn).

### B.5 — Frontend

1. **Type** (`app/resources/js/lib/orders.tsx`): thêm `prepare_block_reason?: string | null` vào interface `Order`.

2. **Tooltip trên thẻ trạng thái** (`OrdersPage.tsx`, cột Trạng thái): bọc `StatusTag` khi có lý do (con trỏ `help` để biết hover được):

```tsx
{ title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 140,
  render: (v, o) => o.prepare_block_reason
    ? <Tooltip title={o.prepare_block_reason}>
        <span style={{ cursor: 'help' }}><StatusTag status={v} label={o.status_label} rawStatus={o.raw_status} /></span>
      </Tooltip>
    : <StatusTag status={v} label={o.status_label} rawStatus={o.raw_status} /> }
```

Giữ nguyên thẻ trạng thái chuẩn (đúng lựa chọn "chỉ tooltip khi di chuột"), không thêm chữ/icon hiển thị sẵn.

3. **Khóa nút "Chuẩn bị hàng"** cho đơn bị chặn (đúng quy ước toolbar validate-by-disable): loại đơn có `prepare_block_reason` khỏi `eliPrepare`:

```tsx
const eliPrepare = selectedOrders.filter(
  (o) => !o.shipment && PREPARABLE_STATUSES.includes(o.status) && !o.out_of_stock && !o.prepare_block_reason,
);
```

Đơn bị chặn vẫn nằm trong danh sách (không ẩn) nhưng bị skip — người dùng hiểu lý do qua tooltip trạng thái.

---

## Kiểm thử

- **PHP (chính):**
  - Test bảng ánh xạ `prepareBlockReason` cho từng connector (TikTok/Lazada/Shopee/Manual) — table-driven, đối chiếu bảng doc ở B.2.
  - Feature test `OrderResource`: (a) `prepare_block_reason` đúng/null theo trạng thái; (b) Part A — đơn terminal `has_label=false` → field cho FE đúng (qua `is_terminal`/`shipment.has_label`).
  - Test `assertChannelOrderFulfillable` ném đúng thông báo cụ thể theo `prepareBlockReason` (thay thông báo chung chung cũ).
- **Frontend:** `npm run lint && npm run typecheck && npm run build` (repo không có JS test runner — theo baseline đã biết; 7 test GHN/fulfillment fail sẵn trên main không liên quan).
- **Quality gate:** `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` (filter các test mới).

## Phạm vi KHÔNG làm (YAGNI)

- Không gọi API sàn để lấy lý do (chỉ map từ raw_status đã đồng bộ về).
- Không xử lý nuance cấp package của Shopee (COD `LOGISTICS_NOT_START`) ở bản này — chỉ map theo `order.raw_status`; có thể bổ sung sau nếu cần.
- Không đổi luồng arrange/label/print hiện hữu.
- Không thêm cột mới trên bảng đơn; chỉ thêm 1 field read-only ở resource + UI tooltip/tag.

## Tệp dự kiến đụng tới

| Loại | Tệp |
|---|---|
| Mới | `app/app/Support/Enums/PrepareBlockReason.php` |
| Sửa contract | `app/app/Integrations/Channels/Contracts/ChannelConnector.php` |
| Sửa connector | `Integrations/Channels/{TikTok,Lazada,Shopee,Manual}/*Connector.php` |
| Sửa service | `app/app/Modules/Fulfillment/Services/ShipmentService.php` (`assertChannelOrderFulfillable`) |
| Sửa resource | `app/app/Modules/Orders/Http/Resources/OrderResource.php` |
| Sửa FE | `app/resources/js/pages/OrdersPage.tsx`, `app/resources/js/lib/orders.tsx` |
| Test | `tests/...` (connector mapping + OrderResource) |
