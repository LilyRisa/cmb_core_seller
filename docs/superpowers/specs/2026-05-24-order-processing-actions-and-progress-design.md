# Thiết kế: Nhóm thao tác đơn hàng, popup tiến trình & Lazada auto-RTS

**Status:** Draft · **Ngày:** 2026-05-24 · **Nhánh:** `feat/order-bulk-actions-progress`

Liên quan: SPEC 0006 (fulfillment & printing), SPEC 0009 (màn xử lý đơn), SPEC 0013 (luồng fulfillment & âm tồn), `docs/03-domain/order-status-state-machine.md`, `docs/04-channels/order-processing.md`, `docs/09-process/danh-gia-ux-luong-xu-ly-don-2026-05-13.md` (đánh giá UX).

## 1. Bối cảnh & vấn đề

Màn xử lý đơn (`OrdersPage.tsx` + `OrderProcessing.tsx`) hiện bật/tắt nút theo trạng thái đơn/vận đơn. Hệ quả:

- **Nút bật/tắt theo trạng thái gây rối**, logic điều kiện **lặp ở 3 nơi** (`OrderActions` per-row, thanh bulk `OrdersPage`, `ShipmentsTab`) → sửa một chỗ dễ quên chỗ khác.
- **Không in lại được tem/hoá đơn cho đơn đã giao/huỷ/đang giao** (nút in chỉ hiện khi vận đơn còn "mở").
- **Bulk pack/handover nuốt lỗi** (`ShipmentController::pack` dòng 238, `handover` dòng 256 dùng `catch (\Throwable) {}` rỗng, chỉ trả `{packed:N}`/`{handed_over:N}`). Người dùng không biết đơn nào lỗi/vì sao (bug L1 trong đánh giá UX).
- **Không có popup tiến trình theo từng đơn** — chỉ có `Modal.warning` liệt kê lỗi sau khi chạy xong.
- **Lỗi kỹ thuật lộ ra UI** (tên class exception, mã nội bộ — D1/N1).
- Lazada bắt buộc 3 trạng thái trước khi giao ĐVVC (`pending/topack → packed → ready_to_ship`); RTS (`/order/rts`) là bước tách riêng. Người bán muốn tuỳ chọn **tự RTS sau khi in** để rút thao tác.

## 2. Mục tiêu

1. Chuyển từ "disable nút theo trạng thái" sang **"nút luôn bấm được (nếu có quyền) + validate từng đơn lúc thực thi, bỏ qua đơn không hợp lệ kèm lý do"**.
2. **Popup tiến trình thống nhất** hiển thị danh sách đơn + trạng thái từng đơn (Đang xử lý / Thành công / Bỏ qua + lý do / Lỗi + lý do), realtime, có "Thử lại đơn lỗi".
3. **Nhóm IN status-agnostic**: in lại được cho đơn đã giao/huỷ/đang giao; đơn không có tài liệu phù hợp thì bỏ qua.
4. Sửa bug nuốt lỗi: bulk pack/handover trả **kết quả per-đơn**.
5. **Tuỳ chọn per-shop Lazada**: "Tự động gửi đơn cho ĐVVC sau khi in" (auto-RTS-after-print), mặc định TẮT.
6. **Công tắc Admin** bật/tắt hiển thị chi tiết lỗi kỹ thuật (prod tắt → chỉ còn câu tiếng Việt thân thiện).
7. Giữ **can thiệp tối thiểu**: không sửa state machine, không sửa lõi service guards, mọi hành vi mới mặc định không đổi behaviour cũ.

## 3. Ngoài phạm vi (Non-goals)

- Không đổi order/shipment **state machine**, không đổi thời điểm **trừ tồn** (`picked_up`).
- Không viết lại lõi `ShipmentService::createForOrder/markPacked/handover` hay connector pack/RTS — chỉ gọi qua interface sẵn có.
- Không bỏ check trạng thái cho thao tác **đổi trạng thái** (chuẩn bị/đóng gói/RTS/bàn giao) — các thao tác này vẫn validate per-đơn để **không đẩy lệnh sai thứ tự lên sàn**.
- Không gỡ code chết (`useProcessingBoard`, `STAGE_LABEL`, `/fulfillment/processing*`) trong phạm vi này (giữ tách biệt, giảm rủi ro) — chỉ ghi chú để dọn sau.

## 4. Thiết kế chi tiết

### 4.A — Hai nhóm thao tác

**Nhóm IN** (status-agnostic): In tem/phiếu giao hàng · In hoá đơn · In picking list · In packing list.
- Nút **luôn bấm được** nếu có quyền `fulfillment.print`, kể cả đơn `delivered`/`cancelled`/`in_transit`.
- Validate per-đơn lúc thực thi: đơn **không có tài liệu phù hợp loại in** → bỏ qua kèm lý do.
- **In tem/phiếu**: chỉ in khi các đơn chọn **cùng 1 nền tảng + cùng 1 ĐVVC**. Trộn lẫn → **báo lỗi và KHÔNG thực thi** (FE pre-check trước khi gọi backend; backend giữ ràng buộc `PrintService::assertSinglePlatformAndCarrier` làm lớp phòng vệ — không đổi).
- In hoá đơn/picking/packing: giữ hành vi hiện tại (hoá đơn chỉ cho đơn manual).

**Nhóm XỬ LÝ** (validate-và-bỏ-qua): Chuẩn bị hàng · Nhận phiếu giao hàng · Đã gói & sẵn sàng bàn giao · Bàn giao ĐVVC.
- Nút **luôn bấm được** nếu có quyền `fulfillment.ship`.
- Mỗi đơn được phân loại **server-side** thành `ok` / `skipped(reason)` / `error(reason)`; đơn không hợp lệ bị **bỏ qua**, không ném lỗi chặn cả lô.
- Guard lõi trong service giữ nguyên (bảo vệ lệnh lên sàn).

Phân quyền: UI ẩn nút bằng `useCan('fulfillment.print'|'fulfillment.ship')`; backend Policy/permission là nguồn xác thực (không tin client).

### 4.B — Engine thực thi hàng loạt + Popup tiến trình (FE)

Tạo **một** engine dùng chung, thay logic disable đang lặp:

- `useBulkAction` (hook, `app/resources/js/lib/`): nhận `{ items, chunkSize, run(chunk) }`, chạy lần lượt theo **chunk**, cập nhật state per-item khi mỗi chunk trả về. Trả `{ progress, results, retryErrors() }`.
- `<BulkProgressModal>` (component, `app/resources/js/components/`): hiển thị thanh tiến độ tổng + danh sách đơn với trạng thái:
  - `running` ⟳ (LoadingOutlined) · `ok` ✓ (CheckCircleOutlined, success) · `skipped` ⊘ (MinusCircleOutlined, warning) · `error` ✕ (CloseCircleOutlined, danger).
  - Mỗi dòng: `#order_number · nền tảng · trạng thái · lý do`.
  - Footer: tổng "Thành công N · Bỏ qua N · Lỗi N", nút **"Thử lại đơn lỗi"** (chỉ chạy lại item `error`, idempotent), nút "Đóng".
  - Icon dùng `@ant-design/icons` (không emoji — theo chuẩn UI dự án).
- Áp dụng cho mọi thao tác bulk của Nhóm XỬ LÝ. Nhóm IN dùng `PrintJobBar` sẵn có (đã có polling); bổ sung hiển thị "đã bỏ qua N đơn (không có tài liệu)".
- Chunk size mặc định: 25 (cân bằng giữa tiến trình realtime và số request). Có thể chỉnh hằng số.
- **Gom logic**: chuyển điều kiện hành động về một nguồn (helper phân loại + engine), bỏ các điều kiện `disabled` theo status ở `OrderActions`/thanh bulk/`ShipmentsTab`.

```
┌─ Xử lý: Bàn giao ĐVVC ───────────────────────────── 12/15 ─┐
│ ████████████████████░░░░░  80%                              │
├─────────────────────────────────────────────────────────────┤
│ #10231  Shopee   ✓ Thành công                                │
│ #10233  TikTok   ⊘ Bỏ qua — đơn đã bàn giao trước đó          │
│ #10234  Lazada   ⊘ Bỏ qua — đơn đã huỷ                        │
│ #10235  Shopee   ✕ Lỗi — ĐVVC chưa phản hồi, thử lại sau      │
│ #10236  Lazada   ⟳ Đang xử lý…                               │
├─────────────────────────────────────────────────────────────┤
│ Thành công 10 · Bỏ qua 3 · Lỗi 2     [Thử lại đơn lỗi] [Đóng]│
└─────────────────────────────────────────────────────────────┘
```

### 4.C — Backend: kết quả per-đơn + validate-và-bỏ-qua

Sửa `ShipmentController::pack` & `handover` (và bổ sung nếu cần cho prepare đã có sẵn): thay `catch (\Throwable) {}` rỗng bằng phân loại kết quả per-đơn.

**Contract trả về** (đồng nhất cho mọi bulk thao tác đổi trạng thái):
```json
{ "data": { "results": [
  { "id": 10231, "status": "ok" },
  { "id": 10233, "status": "skipped", "reason": "Đơn đã bàn giao trước đó" },
  { "id": 10235, "status": "error", "reason": "ĐVVC chưa phản hồi, thử lại sau",
    "technical": "GhnException: timeout (#10235)" }
] } }
```
- `technical` **chỉ xuất hiện khi** `fulfillment.expose_technical_errors` bật (xem 4.E-admin). Khi tắt: bỏ trường này.

**Quy tắc phân loại** (server-side, là nguồn xác thực):

| Thao tác | `skipped` (bỏ qua) | `error` | `ok` |
|---|---|---|---|
| Chuẩn bị hàng (`bulk-create`) | đã có vận đơn mở ("đã có phiếu"); đơn terminal/huỷ/returning | âm tồn; ĐVVC chưa bật; lỗi sàn | tạo được vận đơn |
| Nhận phiếu (`bulk-refetch-slip`) | đã có tem thật (`has_label`) | đơn chưa chuẩn bị; sàn chưa cấp phiếu | kéo được/đã xếp render |
| Đã gói/RTS (`pack`) | đã packed/handed; không có vận đơn mở | huỷ; lỗi sàn RTS | flip packed (+ Lazada RTS) |
| Bàn giao (`handover`) | đã bàn giao; không có vận đơn mở | huỷ | flip picked_up, đơn shipped |

Cách phân loại trong controller: service `markPacked/handover` trả `false` (no-op idempotent) → `skipped`; ném exception → map sang `skipped` (huỷ/không hợp lệ) hoặc `error` (lỗi vận hành) tùy loại; trả `true` → `ok`. **Lõi service không đổi.**

`bulk-create` & `bulk-refetch-slip` đã trả `errors[]` — chuẩn hoá thêm về cùng contract `results[]` (giữ tương thích bằng cách bổ sung, không phá field cũ trong cùng PR nếu FE còn dùng; FE sẽ chuyển sang `results[]`).

**Map lỗi kỹ thuật → tiếng Việt**: thêm một helper `FulfillmentErrorMapper` ánh xạ exception/mã sàn phổ biến → câu thân thiện (vd timeout, rate-limit, Lazada 50008 buyer-cancel, âm tồn). Câu thân thiện **luôn** trả ở `reason`; chi tiết kỹ thuật để ở `technical` (gated).

### 4.D — In tem nhiều nền tảng/ĐVVC

FE pre-check selection: nếu các đơn chọn để in tem/phiếu thuộc **>1 nền tảng hoặc >1 ĐVVC** → hiện `Modal.warning`/`message.error` rõ ràng ("Chỉ có thể in tem cho cùng một nền tảng và một ĐVVC. Vui lòng chọn lại.") và **không gọi backend**. Backend giữ `assertSinglePlatformAndCarrier` (422) làm lớp phòng vệ — không đổi.

### 4.E — Lazada auto-RTS-after-print (per-shop) + Admin error toggle

**Per-shop toggle (Gian hàng):**
- Cột mới `channel_accounts.auto_rts_after_print` (boolean, `default(false)`), migration reversible — nhân bản pattern `messaging_enabled`.
- Hiển thị trong `ChannelAccountResource` (`auto_rts_after_print` + cờ `auto_rts_available` = `provider==='lazada'`).
- Endpoint toggle ở `ChannelAccountController` (mirror `messaging` toggle): validate boolean → `forceFill` → `AuditLog::record('fulfillment.channel.auto_rts.toggle', …)` → Resource. Gated `channels.manage`. Chỉ cho bật khi `provider==='lazada'` (abort 422 nếu khác).
- FE `ShopCard` (`ChannelsPage.tsx`): khi `provider==='lazada'` & `canManage` → `Switch` "Tự động gửi đơn cho ĐVVC sau khi in" + tooltip giải thích (in tem xong tự chuyển *Sẵn sàng giao* trên Lazada). Mutation `useSetChannelAutoRts` mirror `useSetChannelMessaging`.

**Cơ chế trigger:** khi print job **loại `label`** được **"Đánh dấu đã in"** (`PrintService::markPrinted` / endpoint `mark-printed`):
- Với mỗi đơn Lazada trong job, nếu shop bật `auto_rts_after_print` & vận đơn đủ điều kiện (đã `packed`, có tracking) → gọi lại **đúng path `markPacked`** (path này vốn làm Lazada `/order/rts` + chuyển trạng thái nội bộ sang *Chờ bàn giao*). **Không viết RTS mới.**
- Lỗi RTS không chặn việc đánh dấu in — set `has_issue` như cơ chế hiện có.
- Mặc định TẮT ⇒ shop hiện tại không đổi behaviour.

**Admin toggle hiển thị lỗi kỹ thuật:**
- Thêm key catalog `fulfillment.expose_technical_errors` (group `fulfillment`, type `bool`, env `FULFILLMENT_EXPOSE_TECHNICAL_ERRORS`) vào `SystemSettingsCatalog` → tự xuất hiện trong UI `/admin/settings` (nhóm Fulfillment) dưới dạng switch.
- Đọc runtime: `system_setting('fulfillment.expose_technical_errors', (bool) config('app.debug'))` — chưa set thì theo `APP_DEBUG` (dev hiện lỗi, **prod ẩn** mặc định an toàn); admin có thể đè.
- Khi BẬT: response lỗi & popup kèm trường `technical`. Khi TẮT: chỉ `reason` tiếng Việt.

### 4.F — Đánh giá rủi ro & ranh giới can thiệp

**File dự kiến đụng (additive):**
- BE: `ShipmentController` (pack/handover trả `results[]`), `FulfillmentErrorMapper` (mới), `PrintService::markPrinted` (+ hook auto-RTS Lazada), `ChannelAccountController` (+ toggle auto-RTS), `ChannelAccount` model + Resource, migration thêm cột, `SystemSettingsCatalog` (+1 key), `routes/api.php` (+1 route toggle), `.env.example`.
- FE: `lib/useBulkAction` (mới), `components/BulkProgressModal` (mới), `OrdersPage.tsx` + `OrderProcessing.tsx` (gom 2 nhóm nút, bỏ disable theo status, gắn engine), `ChannelsPage.tsx` + `lib/channels.tsx` (+ Switch & mutation Lazada), `lib/fulfillment.tsx` (kiểu `results[]`).

**KHÔNG đụng:** order/shipment state machine, lõi `createForOrder/markPacked/handover`, cơ chế trừ tồn, connector Lazada pack/RTS internals, `assertSinglePlatformAndCarrier`.

**Giảm rủi ro:**
- Mọi hành vi mới mặc định TẮT/giữ nguyên (`auto_rts_after_print=false`; `expose_technical_errors` theo APP_DEBUG, prod off).
- Thay đổi gating là **UI + tầng controller bulk**, không phải lõi nghiệp vụ.
- "Thử lại" idempotent (chỉ chạy lại item `error`; service đã idempotent với đơn đã xử lý → no-op `skipped`).
- Migration reversible (`down()` drop cột).

## 5. Kiểm thử & nghiệm thu

- **BE (Pest):** test `ShipmentController::pack/handover` trả `results[]` đúng phân loại (ok/skipped/error) với đơn huỷ, đơn đã xử lý, đơn lỗi giả lập; test `expose_technical_errors` bật/tắt → có/không trường `technical`; test toggle `auto_rts_after_print` (chỉ Lazada, permission, audit); test hook markPrinted → markPacked được gọi cho đơn Lazada bật cờ, không gọi khi tắt/không phải Lazada.
- **FE:** không có test runner JS (theo ghi chú baseline) → **verify thủ công**: chọn lô đơn hỗn hợp trạng thái cho từng thao tác, kiểm popup phân loại đúng; in tem trộn nền tảng → bị chặn có thông báo; in lại đơn đã giao → được; bật/tắt Switch Lazada; bật/tắt admin toggle → ẩn/hiện chi tiết kỹ thuật.
- Baseline: 7 test GHN/fulfillment fail có sẵn trên `main` — không claim "xanh toàn cục"; chỉ xác nhận không thêm fail mới ở vùng đụng tới.
- Cập nhật doc theo DoD: `docs/04-channels/order-processing.md`, `docs/05-api/endpoints.md` (route toggle), `docs/03-domain/fulfillment-and-printing.md` (auto-RTS hook).

## 6. Mở rộng tương lai (ghi chú, ngoài phạm vi)

- Dọn code chết `useProcessingBoard`/`STAGE_LABEL`/`/fulfillment/processing*`.
- Cân nhắc auto-RTS-after-print cho thời điểm "render xong" (thay vì "đánh dấu đã in") nếu người dùng yêu cầu.
