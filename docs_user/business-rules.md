# Quy tắc nghiệp vụ & Logic (Business Rules)

> Mọi quy tắc dưới đây trích từ enum/spec/code thực tế. Tiền = **số nguyên VND**. Nhãn người dùng = tiếng Việt.

---

## 1. Máy trạng thái đơn hàng (Order Status)

Mỗi đơn lưu `status` (mã chuẩn tiếng Anh) + `raw_status` (chuỗi gốc của sàn). Mỗi lần đổi trạng thái ghi 1 dòng `order_status_history`.

### Các trạng thái chuẩn

| Mã | Nhãn | Ý nghĩa |
|---|---|---|
| `unpaid` | Chờ thanh toán | Đã tạo, chưa thanh toán (đơn online chờ trả tiền) |
| `pending` | Chờ xử lý | Đã thanh toán / xác nhận COD, **chưa in/sắp tem**. Bấm "Chuẩn bị hàng" để lấy tem |
| `processing` | Đang xử lý | **Đã tạo/in vận đơn** — đang đóng gói + quét nội bộ |
| `ready_to_ship` | Chờ bàn giao | **Đã gói + quét** — chỉ đến được qua **thao tác nội bộ** (`markPacked`), không từ raw_status sàn |
| `shipped` | Đang vận chuyển | Đã bàn giao ĐVVC / đang vận chuyển |
| `delivered` | Đã giao | Đã giao tới người nhận |
| `completed` | Hoàn tất | Qua hạn khiếu nại / đã đối soát. **Kết thúc** |
| `delivery_failed` | Giao thất bại | Giao hỏng, chờ giao lại |
| `returning` | Đang trả/hoàn | Đang xử lý trả/hoàn |
| `returned_refunded` | Đã trả/hoàn | Đã hoàn tiền và/hoặc nhận lại hàng. **Kết thúc** |
| `cancelled` | Đã huỷ | Huỷ trước khi giao. **Kết thúc** |

Cờ phụ ngoài chuỗi: `payment_status` (`unpaid/paid/refunded/partial_refund`), `is_split` (đơn tách nhiều kiện), `has_issue` (đơn có vấn đề).

### Chuyển trạng thái hợp lệ (người dùng)

```
unpaid        → pending, cancelled
pending       → processing, ready_to_ship, cancelled
processing    → ready_to_ship, cancelled
ready_to_ship → shipped, cancelled
shipped       → delivered, delivery_failed, returning
delivery_failed → shipped, returning, cancelled
delivered     → completed, returning
completed     → returning
returning     → returned_refunded
returned_refunded → (kết thúc)     cancelled → (kết thúc)
```

### Quy tắc

- **Dữ liệu sàn là nguồn sự thật**: cập nhật từ sàn **không** bị chặn bởi luật chuyển; luôn được ghi nhận. Nếu sàn báo lùi bất thường (lùi ≥2 bậc, hoặc lùi khỏi trạng thái kết thúc, vd `completed → processing`) → set `has_issue`.
- **Chuyển do người dùng** phải theo cạnh hợp lệ; sai → từ chối. Người dùng **không** tự đổi trạng thái lõi của đơn sàn (chỉ tag/note), trừ các bước sàn cho phép (xác nhận đơn, tạo vận đơn) — các bước này gọi API sàn trước.
- **Idempotent**: đặt lại đúng trạng thái cũ = không làm gì (không thêm history).
- `pending → processing` khi bấm **"Chuẩn bị hàng"** (tạo vận đơn / lấy tem) — **bị chặn nếu có SKU âm kho** (`∑on_hand − ∑reserved < 0`).
- `processing → ready_to_ship` chỉ qua thao tác nội bộ "đã gói & quét đơn". `→ shipped` (trừ tồn) khi bàn giao thực / ĐVVC lấy hàng.
- `completed` chỉ set khi sàn báo hoàn tất (hoặc theo ngưỡng thời gian) — không tự nhảy từ `delivered`.
- Mỗi connector sàn có `XStatusMap` riêng — **nơi duy nhất** chứa chuỗi raw_status của sàn đó (vd TikTok `AWAITING_SHIPMENT → pending`, `AWAITING_COLLECTION → processing`, `IN_TRANSIT → shipped`).

---

## 2. Tồn kho

**Bất biến cốt lõi: SKU gốc là nguồn sự thật duy nhất của tồn.** Listing sàn chỉ "soi" SKU gốc đã ghép. Mỗi thay đổi tồn ghi 1 dòng `inventory_movements` bất biến. Đẩy tồn lên sàn là hệ quả tự động (debounce + lock).

### Khái niệm

- **SKU gốc** (`skus`): đơn vị tồn nhỏ nhất, `sku_code` duy nhất theo tenant.
- **Tồn theo kho** (`inventory_levels`, khoá `(sku_id, warehouse_id)`): `on_hand`, `reserved`, `safety_stock`; **`available = max(0, on_hand − reserved − safety_stock)`** — đây là số đẩy lên sàn.
- **Ghép SKU** (`sku_mappings`): nối `channel_listing` ↔ một/nhiều SKU gốc với `quantity` + `type`:
  - `single`: 1 listing ↔ 1 SKU (qty thường 1, N cho "lốc N").
  - `bundle`/combo: 1 listing ↔ nhiều SKU; tồn listing = `min(floor(available(sku_i)/quantity_i))`.
  - Một SKU gốc có thể đứng sau nhiều listing ⇒ đồng bộ chéo sàn.

### Tự khớp & chưa ghép

- Listing đồng bộ về mà chưa ghép → "Chưa ghép SKU".
- **Tự khớp**: nếu `channel_listing.seller_sku` = `skus.sku_code` (chuẩn hoá: trim/hoa/bỏ space) ⇒ gợi ý `single × 1`.
- **Listing chưa ghép KHÔNG đẩy tồn**; đơn của listing chưa ghép có `order_item.sku_id = null` và bị `has_issue` ("đơn có SKU chưa ghép").

### Vòng đời biến động tồn

| Sự kiện | Tác động (mỗi dòng đơn có sku_id) | Loại |
|---|---|---|
| Đơn vào `pending`/`processing` | `reserved += qty` | `order_reserve` |
| `cancelled`/`returned_refunded` **trước** `shipped` | `reserved −= qty` | `order_release` |
| Đơn vào `shipped` | `reserved −= qty`, `on_hand −= qty` | `order_ship` |
| `returned_refunded` **sau** `shipped`, hàng về | `on_hand += qty` | `return_in` |
| Nhận hàng PO | `on_hand += qty` (+ tạo `cost_layers`) | `goods_receipt` |
| Chuyển kho | `−q` ở A, `+q` ở B | `transfer_out`/`transfer_in` |
| Kiểm kê lệch | điều chỉnh về thực tế | `stocktake_adjust` |
| Điều chỉnh tay | theo người dùng | `manual_adjust` |

- **Chống bán âm**: đọc-sửa-ghi trong transaction + khoá dòng (hoặc lock phân tán theo `sku_id`). Nếu thiếu khi đặt giữ (oversold ở sàn), đơn vẫn được giữ (đơn thật), tồn có thể âm tạm thời, cảnh báo "âm kho", và **tồn đẩy lên sàn = 0**.
- **Combo**: đặt giữ/xuất ảnh hưởng **mọi** SKU thành phần × quantity.
- Mỗi biến động ghi `balance_after` để audit.

### Đẩy tồn lên sàn

`InventoryChanged` → debounce `PushStockForSku` (~5–15s) → tính `channel_stock` mong muốn (single = `floor(available/qty)`; bundle = min thành phần) → nếu khác thì `PushStockToListing` (throttle theo provider+shop) gọi `connector.updateStock`. Trừ safety_stock trước khi đẩy; listing có thể "ghim" (không tự đẩy); reverse-sync định kỳ so tồn thực với sàn và **cảnh báo + đẩy lại nhưng không ghi đè SKU gốc**.

### Giá vốn FIFO (COGS)

- Mỗi `goods_receipt` xác nhận tạo 1 `cost_layers` (idempotent).
- Khi `order_ship`, tiêu thụ lớp FIFO (cũ nhất trước) và ghi 1 dòng `order_costs` **bất biến** (1-1 với `order_item`) gồm `cogs_total`, `cogs_unit_avg`, `layers_used`. Thiếu lớp FIFO ⇒ lớp tổng hợp dùng `Sku::effectiveCost()` (`average|latest`), gắn cờ `synthetic=true`.
- Lợi nhuận đọc COGS từ `order_costs` cho đơn đã ship (`cost_source: fifo`); đơn chưa ship dùng ước tính (`cost_source: estimate`).

---

## 3. Gói thuê bao (Billing)

### 4 gói (giá VND; yearly = 10× monthly)

| Gói | Tháng | Năm | Số gian hàng | Tính năng |
|---|---|---|---|---|
| `trial` (Dùng thử) | 0 | 0 | 2 | 14 ngày, như starter |
| `starter` | 99.000 | 990.000 | 2 | cơ bản |
| `pro` | 199.000 | 1.990.000 | 5 | + procurement, fifo_cogs, profit_reports, finance_settlements, demand_planning, accounting_basic, messaging_inbox |
| `business` | 399.000 | 3.990.000 | 10 | + mass_listing, automation_rules, accounting_advanced, messaging_ai, priority_support |

`max_channel_accounts = -1` ⇒ không giới hạn. **Không giới hạn số đơn** — gói chỉ khác ở số gian hàng + tính năng nâng cao. Enterprise (>10 shop) do admin tạo tay.

### Máy trạng thái subscription

```
trialing → active (đã trả) | expired (hết hạn dùng thử chưa trả)
active   → past_due (hết kỳ chưa trả) | cancelled (user huỷ) | active (gia hạn)
past_due → active (đã trả) | expired (sau 7 ngày grace)
cancelled → expired (sau cancel_at)
expired  → kết thúc — tự tạo subscription trial MỚI, active, vĩnh viễn
```

- Tenant mới ⇒ `TenantCreated` ⇒ tạo `trialing`, `trial_ends_at = +14 ngày`.
- **Grace = 7 ngày.** `past_due` quá 7 ngày ⇒ `expired` + tự rơi về trial miễn phí vĩnh viễn. **Dữ liệu không bao giờ bị khoá** — chỉ mất tính năng nâng cao + gian hàng dư.
- **Huỷ**: `cancel_at = hết kỳ hiện tại` (dùng hết kỳ, không hoàn tiền).
- **Nâng cấp giữa kỳ**: v1 không proration — hoá đơn full mới, sub cũ `cancel_at`. Hạ xuống gói trả phí thấp hơn ⇒ `422 DOWNGRADE_NOT_ALLOWED`. Đang ở gói cao hơn cùng chu kỳ ⇒ `422 ALREADY_ON_PLAN`.

### Hạn mức & gating

- **Giới hạn gian hàng**: middleware chặn `POST .../connect` khi đủ hạn ⇒ `402 PLAN_LIMIT_REACHED`.
- **Gating tính năng**: middleware trên route nâng cao ⇒ `402 PLAN_FEATURE_LOCKED`.
- **Over-quota lock**: nếu vượt hạn gian hàng (vd hạ Pro→trial mà còn 5 shop), không tự ngắt; chỉ chặn kết nối mới + banner cảnh báo. Sau **2 ngày** vẫn vượt ⇒ `plan.over_quota_lock` chặn mọi POST/PATCH/DELETE (trừ `/billing`, `/auth`, và xoá gian hàng để thoát).

### Thanh toán

Cổng: SePay (chuyển khoản qua webhook sao kê), VNPay (redirect + IPN HMAC-SHA512), MoMo (skeleton, ném `UnsupportedOperation`), manual. Idempotency: `payments` duy nhất `(gateway, external_ref)`; webhook khớp hoá đơn theo `reference = invoice.code`; trả thiếu ⇒ payment `succeeded` nhưng invoice vẫn `pending`; trả dư ⇒ ghi `payments.meta.overpay` (không tự hoàn). `InvoicePaid` ⇒ `ActivateSubscription`.

---

## 4. Hoàn & Hủy (Returns / After-sales)

Yêu cầu sau bán là **resource riêng có trạng thái riêng**, không phải chuỗi trạng thái đơn. Lưu ở `order_returns`.

- **Trạng thái** (`AfterSalesStatus`): `requested` · `approved` · `rejected` · `processing` · `refunded` · `cancelled_request` · `closed`. `kind` ∈ `cancel | return | refund`.
- **Đồng bộ kép**: poll (~15 phút, trạng thái mở, lookback mặc định 90 ngày) + webhook. **Webhook không bao giờ đủ** — luôn fetch lại chi tiết trước khi lưu.
- **Dedupe**: duy nhất `(source, channel_account_id, external_return_id)`; bỏ qua nếu `source_updated_at` cũ hơn.
- **Liên kết đơn**: khớp `order_id` theo `(source, channel_account_id, external_order_id)`; cho phép `order_id = null` nếu đơn gốc chưa đồng bộ (điền sau).
- **Không** đụng tồn/tài chính trong spec này — chỉ lưu `refund_amount` để hiển thị và set cờ `has_return`/`has_issue`. Mặc định không đổi trạng thái đơn.
- **Quyền**: xem = ai có quyền xem đơn; **Duyệt/Từ chối** = Owner/Admin/StaffOrder.

---

## 5. Fulfillment (vận đơn, in, đóng gói)

### Trạng thái vận đơn (`shipments.status`)

`pending → created (có tracking + label) → packed (đã gói) → picked_up (đã bàn giao) → in_transit → delivered | failed | returned | cancelled`.

Đồng bộ ngược về đơn: `created`/`packed` ⇒ đơn `ready_to_ship`; `picked_up`/`in_transit` ⇒ `shipped`; `delivered` ⇒ `delivered`; `failed` ⇒ `delivery_failed`. **`packed` KHÔNG trừ tồn** (hàng còn trong kho) — trừ tồn ở bàn giao (`picked_up`, đơn `shipped`). Màn xử lý chia 3 chặng: `prepare` → `pack` → `handover`.

### Hai luồng giao hàng

- **A — Logistics sàn**: `getShippingOptions` → `arrangeShipment` → `getShippingDocument` (PDF tem → MinIO). Một đơn có thể tách nhiều kiện ⇒ nhiều shipment, `order.is_split = true`.
- **B — ĐVVC riêng** (`CarrierConnector`): GHN, GHTK, J&T; đơn thủ công dùng luồng này.

### In ấn

- **Tem**: không bao giờ vẽ lại tem của ĐVVC — dùng đúng PDF của họ (giữ nguyên barcode). In hàng loạt gộp PDF sắp theo carrier→đơn. A6 (nhiệt) / A4 (4-up).
- **Phiếu lấy hàng** (gom **theo SKU** qua nhiều đơn) / **Phiếu đóng gói** (1 phiếu/đơn): tự render HTML → **Gotenberg** → PDF.
- `print_count`/`last_printed_at` theo dõi in lại; từ lần in thứ 2 có popup xác nhận. File in giữ ~90 ngày, in lại trả **cùng file** qua signed URL.

### Quét đóng gói / quét bàn giao

Quét barcode vận đơn → tìm shipment/đơn **trong tenant** → (tuỳ chọn) quét từng barcode SKU → đánh dấu `packed`/`picked_up`, đơn `→ shipped`, trừ tồm. Chặn nhầm tenant + quét trùng (quét lần 2 = no-op + thông báo). Điểm trừ tồn mặc định = khi `shipped`.

### Cấu hình đáng chú ý

- **Lazada `auto_rts_after_print`** (mỗi shop, mặc định tắt): khi đánh dấu in, tự gọi `markPacked` (đẩy `/order/rts`) → đơn sang `ready_to_ship` không cần bấm tay. Chỉ Lazada.

---

## 6. Đối soát & Lợi nhuận (Finance)

Thay % phí ước tính bằng **phí thực theo đơn** từ sao kê đối soát của sàn.

- **10 loại phí chuẩn**: `revenue, commission, payment_fee, shipping_fee, shipping_subsidy, voucher_seller, voucher_platform, adjustment, refund, other`. Mỗi connector map mã phí thô của sàn về nhóm chuẩn.
- **Quy ước dấu (góc người bán)**: dương = thu (revenue, shipping_subsidy, voucher_platform); âm = chi (commission, payment_fee, shipping_fee người bán chịu, voucher_seller).
- **Kéo dữ liệu**: `fetchSettlements` (gated capability `finance.settlements`, feature-flag mặc định tắt; tắt ⇒ `422`). Upsert `settlements` + `settlement_lines`. **Idempotent**; `settlement_lines` bất biến.
- **Đối soát**: khớp `settlement_lines.external_order_id → orders.external_order_id` trong cùng `(tenant, channel_account)` ⇒ điền `order_id`; settlement `reconciled` khi mọi dòng khớp.
- **Lợi nhuận**: `grand_total − COGS(FIFO) − Σ phí đối soát − ship thực người bán chịu − giảm giá người bán − phí khác`. Đã đối soát dùng phí thực (`fee_source: settlement`); chưa thì ước tính. Gộp vào `profit_snapshots` (đơn/SKU/shop/ngày/tháng).
- **Quyền**: `finance.view` + `finance.reconcile` = Owner/Admin/Accountant.

---

## 7. Kế toán (Accounting — TT133)

Sổ cái kép, hệ thống TK Việt Nam (DN nhỏ & vừa), **chỉ VND**, năm tài chính = năm dương lịch.

- **Bất biến kép**: mỗi `journal_entries` có `Σ Nợ = Σ Có`, ≥2 dòng (ràng buộc DB); mỗi `journal_line` chỉ có 1 trong Nợ/Có > 0. Bút toán/dòng **bất biến** (không sửa/xoá — luật kế toán lưu 10 năm). Sửa = đảo + ghi lại.
- **Hệ thống TK**: seed theo `ChartAccountsTT133Seeder` (~80 TK: 111/112, 131, 156, 331, 333/3331/33311, 511, 632, 642...). Tenant sửa tên/sắp xếp/active, thêm con; **không xoá** TK đã có phát sinh (`409 ACCOUNTING_ACCOUNT_IN_USE`).
- **Khoá kỳ** (`fiscal_periods.status`): `open` → `closed` (ghi vào ⇒ `422 PERIOD_CLOSED`; đảo bút toán kỳ đã đóng nhảy sang kỳ mở kế) → `locked` (đã nộp/ký — không mở lại). Đóng kỳ chốt `account_balances` (số dư cuối → số dư đầu kỳ sau).
- **Tự định khoản (listener)**: nhận hàng → Nợ 156/Có 331; chuyển kho → Nợ 156(đến)/Có 156(đi); kiểm kê thừa → Nợ 156/Có 711, thiếu → Nợ 811/Có 156. Quy tắc ở `accounting_post_rules` (tenant sửa được); đổi không ảnh hưởng bút toán đã ghi. **Idempotency** qua `idempotency_key`.
- **Quyền**: `accounting.view`/`post`/`close_period`/`config`/`export`. Gói `accounting_basic` (Pro/Business).

---

## 8. Tin nhắn (Messaging)

Hộp thư hợp nhất tin khách từ TikTok/Shopee/Lazada/Facebook. Inbound = webhook + polling dự phòng (~≤10s webhook / ≤5 phút poll).

### Auto-reply (4 trigger)

- `schedule` (vd 22:00–08:00, tz Asia/Ho_Chi_Minh), `order_status` (vd `delivered` → cảm ơn, có delay), `away_no_response` (NV chưa trả lời sau N phút), `first_message` (chào, 1 lần/hội thoại).
- **Chống spam**: `cooldown_seconds`/hội thoại (mặc định 3600); không auto-reply tin do AI sinh; `first_message` chỉ khi `message_count === 1`; `away_no_response` bỏ qua nếu schedule đã trả lời. Idempotency: `auto_reply_runs` duy nhất `(rule_id, conversation_id, window_key)`.

### AI assistant — gating & rào chắn

- Mặc định **chế độ gợi ý** (NV duyệt nháp); **auto-mode** opt-in theo tenant.
- Auto-mode: `IntentClassifier` chạy trước; intent ∈ `{complaint, refund, urgent, legal_threat, abuse}` ⇒ **không gửi**, chỉ báo người thật.
- AI **không bao giờ** gửi ngoài `MessageSendService` (luôn qua audit + window guard). Prompt gửi LLM đều qua `PiiRedactor` (SĐT/email/STK → placeholder). Token log vào `ai_assistant_runs`.
- Gating: gói `messaging_inbox` (Pro+), `messaging_ai` (Business). AI provider do **super-admin** thêm; tenant chọn 1.

### Facebook — cửa sổ 24h & message tag

- Nếu tin khách cuối > 24h, chỉ tin có `message_tag` (CONFIRMED_EVENT_UPDATE / POST_PURCHASE_UPDATE / ACCOUNT_UPDATE / HUMAN_AGENT) được gửi; vi phạm ⇒ `422 OUTBOUND_WINDOW_CLOSED`.

### Facebook comment — nhắn riêng 1 lần (SPEC-0027)

- Facebook chỉ cho **nhắn riêng 1 lần/comment** (qua bất kỳ công cụ nào). Xử lý **idempotent**: lỗi `(#10900) Activity already replied to` được coi là "đã nhắn", **không** ném 500. Các mã `[10900, 10, 200, 551]` + subcode `2018278` (cửa sổ đóng/bị chặn) → coi như best-effort, không ném; lỗi khác (token, rate-limit) vẫn ném.
- 1 message = text **HOẶC** 1 đính kèm. Modal nhắn riêng nhiều phần: phần đầu qua `recipient:{comment_id}` (lấy PSID), phần sau qua `recipient:{id:PSID}` + `MESSAGE_TAG(HUMAN_AGENT)` (best-effort — FE báo "đã gửi X/Y phần").
- Hành động per-comment (chỉ Facebook): **Thích** (`POST /{comment_id}/likes`, cần `pages_manage_engagement`), **Nhắn riêng** (modal), **Xoá**. Lõi kiểm `instanceof CommentEngagementConnector` (tên năng lực, không phải tên sàn) — connector sàn không bị đụng.
- Xoá comment **gốc** ⇒ `status=spam`; xoá comment **con** truyền `comment_id`.

### Trạng thái hội thoại & liên kết

- Hội thoại: `open → snoozed → open`; `open → resolved` (tin mới mở lại); `open → spam`. Tin: `pending → sent → delivered → read`, hoặc `pending → failed`.
- Inbound: liên kết `customers` (theo phone hash) + đoán đơn gần đây (30 ngày, ưu tiên processing/shipped). Không ghi đè `customer_id` có sẵn.

---

## 9. Bất biến chung & Validation

### Bất biến chung
- **Cô lập tenant**: mọi bảng có `tenant_id`, global scope tự set; không truy vấn xuyên tenant.
- **Tiền = số nguyên VND**; pennies dư từ phân bổ % dồn vào dòng cuối.
- **Thời gian** API = ISO-8601 UTC; FE hiển thị Asia/Ho_Chi_Minh.
- **Trạng thái** trả `code` + `status_label` + `raw_status`.
- **Webhook không tin tưởng** ⇒ verify chữ ký (sai ⇒ 401, không lưu) + luôn có polling; luôn fetch lại chi tiết trước khi lưu.
- **Job idempotent** — dedupe khoá duy nhất: đơn `(source, channel_account_id, external_order_id)`, tin `(conversation_id, external_message_id)`, payment `(gateway, external_ref)`, return `(source, channel_account_id, external_return_id)`, bút toán `(tenant_id, idempotency_key)`.

### Validation đáng chú ý
- **Tạo đơn thủ công**: `status` (nếu gửi) ∈ `pending, processing`; `items` 1–200 dòng; mỗi dòng phải có `sku_id` **hoặc** `name` (hàng nhanh); `quantity` 1–99.999; `unit_price`/`discount` 0–999.999.999 (VND). Khách tuỳ chọn; chỉ tạo `customers` khi có cả tên + SĐT. Dòng "hàng nhanh" (không SKU) không theo dõi tồn và **không** bị cờ "chưa ghép SKU".
- **Sửa đơn thủ công**: chỉnh mọi thứ khi chưa `shipped`; gửi `items[]` thay toàn bộ dòng (xoá+chèn) → tồn cân bằng lại.
- **Chống đơn trùng**: cảnh báo nếu cùng SĐT + cùng SKU trong khoảng thời gian ngắn.
- **SKU**: `sku_code` duy nhất/tenant; xoá SKU còn tồn ⇒ `409`.
- **Bút toán tay**: `Σ Nợ = Σ Có`, ≥2 dòng, TK hạch toán được, kỳ mở ⇒ else `422`.
- **Đính kèm tin nhắn**: ảnh ≤25MB, video ≤100MB, file ≤25MB, MIME whitelist ⇒ else `422 ATTACHMENT_INVALID`.

> Xem mã lỗi & API trong [api-reference.md](api-reference.md), cách xử lý lỗi trong [troubleshooting.md](troubleshooting.md).
