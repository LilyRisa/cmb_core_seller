# SPEC 0025: Đồng bộ đơn Hoàn & Hủy (Returns / Cancellations) — 3 sàn

- **Trạng thái:** Draft
- **Phase:** 7 (After-sales)
- **Module backend liên quan:** Channels, Orders (After-sales), Fulfillment (liên kết shipment), Finance (refund)
- **Tác giả / Ngày:** lilyrisa · 2026-05-25
- **Liên quan:** SPEC 0001 (order sync), SPEC 0013/0014 (fulfillment 3 tab), `03-domain/order-sync-pipeline.md`, ADR-0007 (webhook+polling), `extensibility-rules.md`

## 1. Vấn đề & mục tiêu

Pipeline hiện tại **chỉ đồng bộ trạng thái đơn (order status)**. Hoàn/Hủy (cancel/return/refund) trên sàn là **luồng after-sales riêng**, có **resource & trạng thái riêng** (không nằm trong order status), nên đang **bị thiếu**:

- Webhook return (`type 2/13`) chỉ được **ghi log** (`ProcessWebhookEvent` → `webhook.deferred`), không tạo bản ghi.
- Không poll Search Return / Search Cancel; `returns.fetch = false` ở cả 3 connector.
- Buyer cancel-request (chờ seller duyệt) không hiển thị ở đâu.
- Đơn đã giao/`COMPLETED` bị buyer hoàn → app không biết.

**Mục tiêu:** đồng bộ đầy đủ Hoàn & Hủy cho **TikTok + Shopee + Lazada**, có **tab/màn hình "Hoàn & Hủy" riêng** + thao tác **Duyệt/Từ chối** yêu cầu.

Tài liệu sàn xác nhận đây là resource riêng, có 2 cách lấy: **webhook + poll** (TikTok `return-refund-and-cancel-api-overview`; Shopee `227-return-refund-management` + push 16; Lazada `/reverse/getreverseordersforseller`).

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Connector: thêm `fetchReturns`, `fetchCancellations`, `decideReturn`, `decideCancellation` + capability `returns.fetch`/`returns.manage` cho **cả 3 sàn**.
  - DB `order_returns` + `ReturnUpsertService` (idempotent), liên kết `orders` khi có.
  - Webhook: nối `return_update`/`order_cancel` → fetch detail → upsert (thay vì chỉ log).
  - Polling job `SyncReturnsForShop` (poll theo trạng thái mở) + scheduler.
  - FE: tab/màn hình "Hoàn & Hủy" + API list/detail + Duyệt/Từ chối.
  - Enum after-sales status chuẩn hoá + bảng map per-sàn (giống `status_map`).
- **Ngoài (làm sau):**
  - **Tự động** đẩy hoàn tiền/đối soát refund vào Finance (chỉ lưu `refund_amount` để hiển thị; reconcile để SPEC Finance).
  - Tự động nhả/đẩy tồn khi hàng hoàn về kho (chỉ gắn cờ; nhập kho thủ công như hiện tại) — đề xuất tách spec Inventory.
  - Tạo return/refund chủ động từ phía seller (chỉ làm **duyệt/từ chối** yêu cầu của buyer trước).

## 3. Luồng chính

1. **Pull (poll)** — `SyncReturnsForShop` (mỗi ~15') gọi `connector.fetchReturns`/`fetchCancellations` theo trạng thái **đang mở** (pending/processing), upsert vào `order_returns`, gắn `order_id` nếu khớp `external_order_id`.
2. **Push (webhook)** — sàn đẩy `RETURN_STATUS_CHANGE`/`CANCELLATION_STATUS_CHANGE` → `ProcessWebhookEvent` fetch detail return/cancel → upsert. (Webhook là tín hiệu; poll là nguồn an toàn — đúng ADR-0007.)
3. **Hiển thị** — tab "Hoàn & Hủy": list các bản ghi, badge trạng thái chuẩn hoá, link sang đơn gốc.
4. **Thao tác** — seller bấm **Duyệt**/**Từ chối** → `connector.decideReturn|decideCancellation` → sàn cập nhật → re-fetch → upsert.

## 4. Hành vi & quy tắc

- **Khóa chống trùng:** `order_returns(source, channel_account_id, external_return_id)` unique (partial `WHERE deleted_at IS NULL` — theo bài học SPEC order sync §4.1). Upsert idempotent; bỏ qua nếu `source_updated_at <=` bản hiện có.
- **Liên kết đơn:** resolve `order_id` qua `(source, channel_account_id, external_order_id)`; cho phép `order_id = null` nếu đơn gốc chưa có (poll đơn sẽ bù sau — không chặn).
- **Không tự đụng tồn/tài chính** ở spec này (chỉ lưu + gắn cờ `orders.has_return`/`has_issue` để nổi bật).
- **Webhook không bao giờ là nguồn đủ** — luôn re-fetch detail trước khi upsert (giống order).
- **Phân quyền:** xem = mọi role có quyền đơn; **Duyệt/Từ chối** = Owner/Admin/StaffOrder (RBAC như thao tác đơn).
- **Không hard-code tên sàn ở core** — mọi khác biệt nằm trong connector + config map (`extensibility-rules.md`).

## 5. Dữ liệu

**Bảng mới `order_returns`:**

| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| tenant_id | FK index | |
| channel_account_id | FK nullable index | |
| order_id | FK nullable index | đơn gốc (nếu khớp) |
| source | string | tiktok/shopee/lazada/manual |
| external_return_id | string | id return/cancel của sàn |
| external_order_id | string | để resolve order_id |
| kind | string | `cancel` \| `return` \| `refund` |
| status | string | canonical (mục dưới) |
| raw_status | string | trạng thái gốc sàn |
| reason | string nullable | lý do buyer/seller |
| refund_amount | bigint | VND đồng |
| currency | string(3) | |
| items | json nullable | line items hoàn (sku, qty) |
| requested_at / decided_at | timestamp nullable | |
| source_updated_at | timestamp | out-of-order guard |
| raw | json | payload gốc |
| timestamps + softDeletes | | |

- Unique: `(source, channel_account_id, external_return_id) WHERE deleted_at IS NULL`. Index `(tenant_id, status)`, `(order_id)`.
- **Canonical after-sales status** (enum mới `AfterSalesStatus`): `requested` (chờ seller duyệt) · `approved` · `rejected` · `processing` (đang trả hàng) · `refunded` (đã hoàn tiền/`returned_refunded`) · `cancelled_request` (buyer hủy yêu cầu) · `closed`.
- **Order**: thêm cột cờ `has_return` (bool) để list đơn highlight; (tùy chọn) cập nhật `orders.status` sang `returning`/`returned_refunded` khi return tiến triển — **chỉ** khi nghiệp vụ xác nhận, mặc định KHÔNG đụng order status để tránh nhiễu.
- **Domain events:** `ReturnUpserted`, `ReturnStatusChanged` (Finance/Inventory lắng nghe ở spec sau).

## 6. API & UI

**ChannelConnector (method mới — default `UnsupportedOperation` ở `ManualConnector`):**
- `fetchReturns(AuthContext, array $query): Page<ReturnDTO>`
- `fetchCancellations(AuthContext, array $query): Page<ReturnDTO>` (kind=cancel)
- `decideReturn(AuthContext, string $externalReturnId, string $action /*approve|reject*/, array $params): array`
- `decideCancellation(AuthContext, string $externalCancelId, string $action, array $params): array`
- Capability: `returns.fetch`, `returns.manage`.
- DTO chuẩn `ReturnDTO` (mọi field §5) — connector map field sàn → DTO (chỗ DUY NHẤT tên field sàn xuất hiện).

**Endpoint sàn dùng (đối chiếu SDK/doc, để config-able path):**
- **TikTok** (`returnRefundV202309Api`): `ReturnsSearchPost` (poll return), `CancellationsSearchPost` (poll cancel), `ReturnsReturnIdRecordsGet` (detail), `ReturnsReturnIdApprovePost`/`RejectPost`, `CancellationsCancelIdApprovePost`/`RejectPost`. Webhook `type 2/13` (return), `12` (cancel) — đã map ở config.
- **Shopee** (`227-return-refund-management`): `get_return_list` / `get_return_detail` + xử lý cancel qua `handle_buyer_cancellation`. Push code **16** (Return) — thêm vào `shopee.webhook_event_types`.
- **Lazada**: `/reverse/getreverseordersforseller` (poll), `/order/reverse/return/detail/list` + `/history/list` (detail), `/order/reverse/cancel/seller/decide` + `/order/reverse/onlyrefund/seller/decide` (duyệt/từ chối), `/order/reverse/reason/list`.

**API app (REST, theo `05-api/conventions.md`):**
- `GET /api/v1/returns` — list (filter status/source/shop/kind, search theo order), phân trang.
- `GET /api/v1/returns/{id}` — detail.
- `POST /api/v1/returns/{id}/approve` · `POST /api/v1/returns/{id}/reject` — RBAC Owner/Admin/StaffOrder.
- `GET /api/v1/returns/stats` — đếm theo trạng thái (cho badge tab).

**FE:** tab "Hoàn & Hủy" trong khu Đơn hàng (cạnh 3 tab xử lý), bảng + bộ lọc + nút Duyệt/Từ chối (popup tiến trình như bulk action hiện có); badge số "yêu cầu chờ duyệt".

**Job (theo `07-infra/queues-and-scheduler.md`):** `SyncReturnsForShop` queue `orders-sync`, dispatch mỗi 15' cho mỗi shop active; poll theo trạng thái mở (lookback `returns_lookback_days`, mặc định 90). `ProcessWebhookEvent` mở rộng nhánh return/cancel.

## 7. Edge case & lỗi

- Đơn gốc chưa sync → `order_id=null`, vẫn lưu; job poll đơn / lần fetch sau bù `order_id` (resolve lại khi upsert).
- Token hết hạn / rate-limit → tái dùng `maybeRefreshToken` + backoff như order sync.
- Return nhiều lần cho 1 đơn (multi-time refund của TikTok) → mỗi return là 1 `external_return_id` riêng → nhiều bản ghi/đơn (đúng).
- Webhook đến trước khi đơn tồn tại → fetch detail tạo bản ghi return; order_id bù sau.
- Đến trễ / trùng → dedupe theo `external_return_id` + `source_updated_at` (giống order; **kèm raw_status** như fix dedupe webhook order).
- Sàn trả thiếu refund_amount → để 0, không chặn.

## 8. Bảo mật & dữ liệu cá nhân

- `raw` có thể chứa địa chỉ/PII hoàn hàng → áp cùng quy tắc mask/anonymize như order (SPEC 0002 §8); xóa theo data-deletion webhook.
- Không log payload return đầy đủ; chỉ log id + status.

## 9. Kiểm thử

- **Unit:** map status per sàn (return+cancel → canonical); money parse refund_amount.
- **Feature:** `SyncReturnsForShop` poll + upsert idempotent; webhook return/cancel → upsert; approve/reject gọi đúng endpoint sàn (Http::fake) + cập nhật bản ghi; resolve `order_id`; out-of-order skip.
- **Contract (mỗi connector):** fixtures return/cancel list + detail → assert `ReturnDTO`; decide gọi đúng path + method.
- **FE:** render list, badge chờ duyệt, nút Duyệt/Từ chối gọi API.
- Lưu ý baseline: GHN/fulfillment có 5 test fail sẵn trên main — không gộp vào tiêu chí spec này.

## 10. Tiêu chí hoàn thành

- [ ] `order_returns` + migration (unique partial, index) + `AfterSalesStatus` enum.
- [ ] 4 method connector + capability cho TikTok, Shopee, Lazada (+ `ManualConnector` throw Unsupported).
- [ ] `ReturnUpsertService` idempotent + events.
- [ ] `SyncReturnsForShop` job + scheduler (15') + lookback config.
- [ ] `ProcessWebhookEvent` xử lý return_update/order_cancel (TikTok 2/12/13, Shopee 16, Lazada reverse) → upsert.
- [ ] API list/detail/approve/reject/stats + RBAC.
- [ ] FE tab "Hoàn & Hủy" + thao tác duyệt/từ chối.
- [ ] Tests xanh (unit + feature + contract 3 sàn + FE).
- [ ] Cập nhật `05-api/endpoints.md`, `07-infra/queues-and-scheduler.md`, `04-channels/*`, roadmap.

## 11. Đề xuất chia PR

1. **PR1 — Nền tảng:** enum + `order_returns` migration + `ReturnDTO` + `ReturnUpsertService` + contract method skeleton (capability=false) + tests upsert.
2. **PR2 — TikTok:** `fetchReturns/fetchCancellations/decide*` (returnRefundV202309) + webhook 2/12/13 + poll job + contract tests. (Sàn ưu tiên.)
3. **PR3 — Shopee:** module 227 + push 16.
4. **PR4 — Lazada:** reverse endpoints + decide.
5. **PR5 — FE:** tab "Hoàn & Hủy" + API + RBAC + duyệt/từ chối.

## 12. Câu hỏi mở

- Khi return `refunded`, có tự set `orders.status = returned_refunded` không, hay giữ order status nguyên và chỉ hiển thị ở tab Hoàn/Hủy? (đề xuất: chỉ gắn cờ `has_return`, không đụng order status — chốt khi làm PR1).
- Hoàn hàng về kho có tự tạo phiếu nhập/nhả tồn không? (đề xuất tách spec Inventory after-sales).
- Shopee: dùng `get_return_list` cho return, còn cancel lấy từ order status `IN_CANCEL` hay có API cancel-list riêng? (xác minh trên sandbox khi làm PR3).
