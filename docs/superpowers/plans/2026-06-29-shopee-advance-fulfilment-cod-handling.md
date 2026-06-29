# Shopee: phân biệt Advance Fulfilment vs COD-chờ-duyệt (bỏ "lỗi chung chung") — Implementation Plan

> REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Một task cohesive (BE+FE), TDD ở tầng connector.

**Goal:** 2 đơn Shopee kẹt "lỗi chung chung" do gói `LOGISTICS_NOT_START`. Phân biệt & báo rõ:
- **Type 1 — Advance Fulfilment** (`advance_package=true`, lỗi `logistics.error_booking_order`): SPX tự xử lý → **terminal**, dừng retry, đánh dấu "không cần in tem", **KHÔNG** badge đỏ.
- **Type 2 — COD chờ Shopee duyệt** (`LOGISTICS_NOT_START`, lỗi `get_shipping_parameter "ready to be shipped"` / `logistics.package_can_not_print`): tạm thời → thông báo "đang chờ duyệt, sẽ tự thử lại", **KHÔNG** badge đỏ, giữ retry nhẹ.

**Cơ chế tái dùng (đã có sẵn):** `ShippingDocumentUnavailable(message, terminal, reasonCode)` + `::terminal()`/`::transient()`; `fetchAndStoreChannelLabel()` bắt `terminal` → `markLabelUnavailable()` (ghi `shipment.raw.label_unavailable`, dừng retry); `FetchChannelLabel` job đã early-return khi thấy `label_unavailable`; FE `OrderProcessing.tsx` đã hiện "Sàn không cấp tem" (warning, không đỏ) cho `label_unavailable`.

## Global Constraints
- Lệnh từ `app/`. Verify: `php artisan test --filter=...`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` (file đổi), `npm run lint && npm run typecheck && npm run build`.
- Core không biết tên sàn ngoài connector — mọi chuỗi lỗi Shopee chỉ ở `ShopeeConnector`.
- Chuỗi VN; identifier EN. Không migration (dùng `shipment.raw` JSON sẵn có).
- `git add` đúng các file liệt kê; không đụng file working-tree khác.

---

### Task D1: Shopee AF/COD distinct handling (1 task, BE+FE)

**Files:**
- `app/app/Integrations/Channels/Shopee/ShopeeConnector.php`
- `app/app/Modules/Fulfillment/Services/ShipmentService.php`
- `app/app/Modules/Orders/Http/Resources/OrderResource.php`
- `app/resources/js/lib/orders.tsx`
- `app/resources/js/components/OrderProcessing.tsx`
- Test: `app/tests/Unit/Integrations/Channels/ShopeeDocumentStateTest.php` (hoặc Feature nếu cần client fake)

**Bước (TDD trước ở connector):**

- [ ] **Step 1 — Failing test (connector detection).** Viết test: dựng `ShopeeConnector` + giả lập `create_shipping_document` trả lỗi (dùng ShopeeFx/fake client như các test Shopee hiện có — xem `tests/.../Shopee*` để biết cách fake `ShopeeApiException`/response). Assert:
  - response chứa `error_booking_order` ⇒ `getShippingDocument()` ném `ShippingDocumentUnavailable` với `->terminal === true` và `->reasonCode === 'shopee_advance_fulfilment'`.
  - response chứa `package_can_not_print` ⇒ ném `ShippingDocumentUnavailable` với `->terminal === false` và `->reasonCode === 'shopee_cod_screening'`.
  - `arrangeShipment()` khi `get_shipping_parameter` lỗi chứa "ready to be shipped" ⇒ ném `ShippingDocumentUnavailable::transient('shopee_cod_screening', ...)`.
  (Nếu fake client quá khó cho arrange, tối thiểu test nhánh getShippingDocument; ghi rõ trong report.)
  Run → FAIL.

- [ ] **Step 2 — ShopeeConnector.** Thêm `use CMBcoreSeller\Integrations\Channels\Exceptions\ShippingDocumentUnavailable;`.
  - `arrangeShipment()`: bọc lời gọi `get_shipping_parameter` bằng try/catch `ShopeeApiException`; nếu message (lowercase) chứa `ready to be shipped` ⇒ `throw ShippingDocumentUnavailable::transient('shopee_cod_screening', 'Đơn COD đang chờ Shopee phê duyệt — hệ thống sẽ tự thử lại khi sàn sẵn sàng.');` còn lại `throw $e;`.
  - `getShippingDocument()`: sau khi `$reason = batchFailReason(...)`:
    - `str_contains($reason,'error_booking_order')` ⇒ `throw ShippingDocumentUnavailable::terminal('shopee_advance_fulfilment', 'Đơn Advance Fulfilment Shopee — SPX xử lý trực tiếp, không cần & không thể in tem thủ công.');`
    - `str_contains($reason,'package_can_not_print') || str_contains($reason,'package can not print')` ⇒ `throw ShippingDocumentUnavailable::transient('shopee_cod_screening', 'Đơn COD đang chờ Shopee phê duyệt — hệ thống sẽ tự thử lại khi sàn cấp phép.');`
    - còn lại: giữ nguyên `throw new ShopeeApiException(...)`.
  Run connector test → PASS.

- [ ] **Step 3 — ShipmentService.** Thêm `use ...ShippingDocumentUnavailable;` nếu chưa có.
  - `arrangeOnChannel()`: thêm `catch (ShippingDocumentUnavailable $e) { Log::info('shipment.arrange_on_channel_document_unavailable', [...]); throw $e; }` TRƯỚC `catch (\Throwable $e)` (giữ wrap generic cho lỗi khác).
  - `prepareChannelOrder()`: khởi tạo `$pendingReason = null;` trước try arrange. Thêm `catch (ShippingDocumentUnavailable $e)` TRƯỚC `catch (\Throwable)`:
    - `$arrangedOk = false;`
    - nếu `$e->terminal`: `$issue = $e->getMessage();` (sẽ thành has_issue qua luồng cũ — nhưng AF thường lộ ở document path nên markLabelUnavailable lo; ở arrange path hiếm).
    - nếu KHÔNG terminal: `$issue = null;` (KHÔNG has_issue) và `$pendingReason = ['reason_code'=>$e->reasonCode,'message'=>$e->getMessage(),'at'=>now()->toIso8601String()];`
    - Thêm `$pendingReason` vào `use(...)` của closure transaction (hoặc set sau transaction). SAU `DB::transaction(...)`: nếu `$pendingReason !== null` ⇒ `$raw=(array)$shipment->raw; $raw['pending_reason']=$pendingReason; $shipment->forceFill(['raw'=>$raw])->save();`
    - **KHÔNG dùng sentinel chuỗi `__pending__`** — dùng biến `$pendingReason` cho sạch.
  - `markLabelUnavailable()`: `$infoOnly = in_array($e->reasonCode, ['shopee_advance_fulfilment'], true);` → `has_issue => ! $infoOnly`, `issue_reason => $infoOnly ? null : Str::limit($e->getMessage(),240)`. (Vẫn ghi `label_unavailable` như cũ.)

- [ ] **Step 4 — OrderResource.** Trong block shipment, sau `'label_unavailable' => ...`, thêm `'pending_reason' => data_get($s->raw, 'pending_reason.message') ?: null,`.

- [ ] **Step 5 — FE orders.tsx.** Thêm `pending_reason?: string | null;` vào type `shipment` của interface `Order` (cạnh `label_unavailable`).

- [ ] **Step 6 — FE OrderProcessing.tsx.** Thêm import `InfoCircleOutlined` (@ant-design/icons). Sau nhánh `if (sh!.label_unavailable) {...}` thêm `else if (sh!.pending_reason) { ... }`: hiện `<Tooltip title={sh!.pending_reason}><Typography.Text type="secondary"><InfoCircleOutlined/> Đang chờ Shopee xử lý</Typography.Text></Tooltip>` + (nếu `canShip`) link "Thử lại" gọi `getSlip()`. KHÔNG hiện badge đỏ.
  (Nếu badge "Lỗi" do `has_issue` vẫn hiện ở OrdersPage cho Type 2, kiểm: Type 2 giờ `has_issue=false` nên không còn đỏ — đúng mục tiêu.)

- [ ] **Step 7 — Verify.** `php artisan test --filter=ShopeeDocumentStateTest` (+ chạy `ChannelOrderFulfillmentTest` xác nhận không vỡ), `pint --test` + `phpstan` file đổi, `npm run lint && typecheck && build`.

- [ ] **Step 8 — Commit** (1 commit): `git add` 5 file code + test → `feat(shopee): phân biệt Advance Fulfilment (terminal) vs COD chờ duyệt (tạm) — bỏ lỗi chung chung khi lấy tem`.

## Self-Review
- Type 1 → terminal → label_unavailable (FE "Sàn không cấp tem", không đỏ), retry dừng. Type 2 → transient → pending_reason (FE "Đang chờ Shopee xử lý" + Thử lại, không đỏ), retry nhẹ. ✅
- Chuỗi lỗi Shopee chỉ ở ShopeeConnector (golden rule). ✅
- Không migration. ✅
- Rủi ro: fake Shopee client cho test arrange có thể khó → cho phép test tối thiểu nhánh document + ghi chú.
