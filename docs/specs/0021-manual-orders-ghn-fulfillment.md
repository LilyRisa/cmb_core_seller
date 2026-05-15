# SPEC 0021: Tích hợp Giao Hàng Nhanh (GHN) cho đơn tự tạo + capability `defer_dispatch` để mở rộng ĐVVC khác

- **Trạng thái:** Draft (2026-05-16)
- **Phase:** 3 (mở rộng SPEC 0006 / 0013)
- **Module backend liên quan:** Fulfillment (ShipmentService, Shipment status mới), Integrations/Carriers/Ghn (extend capability + webhook), Orders (no changes — state machine giữ nguyên)
- **Tác giả / Ngày:** Team · 2026-05-16
- **Liên quan:** SPEC 0006 (Fulfillment lõi), SPEC 0009 (Order processing screen), SPEC 0013 (3-tab flow), `01-architecture/extensibility-rules.md` §3 + §6 (CarrierConnector), `03-domain/fulfillment-and-printing.md` §3 (luồng B ĐVVC riêng).

## 1. Vấn đề & mục tiêu

Đơn tự tạo (manual) cần dùng GHN làm ĐVVC. Yêu cầu nghiệp vụ:

1. **"Chuẩn bị hàng"** → gọi GHN `createOrder` **ngay** → nhận mã vận đơn từ GHN → render phiếu giao hàng (theo khổ giấy `tenant.settings.print.label_size`, mặc định A6) **có sẵn mã vận đơn GHN trên phiếu**. Đơn: `pending` → `processing`. **Lý do**: shipper GHN không thể nhận hàng từ phiếu tự tạo không có mã vận đơn — phiếu phải khớp với hệ thống GHN.
2. **"Sẵn sàng bàn giao"** → KHÔNG gọi thêm API GHN (đơn đã trong hệ thống GHN từ bước 1, đang ở trạng thái `ready_to_pick` bên GHN). Chỉ flip shipment local: `created` → `awaiting_pickup` ("Chờ lấy hàng"). Đơn: `processing` → `ready_to_ship`.
3. **GHN shipper tới lấy** → GHN webhook/polling đẩy về → shipment `awaiting_pickup` → `picked_up` → đơn → `shipped`. Tiếp tục `in_transit` → `delivered`.

**Mục tiêu:**
1. Thêm shipment status mới `awaiting_pickup` + nhãn "Chờ lấy hàng".
2. `markPacked` cho carrier có capability `awaiting_pickup_flow` (GHN; sau thêm GHTK/J&T) đặt shipment ở `awaiting_pickup` thay vì `packed`. Core không hard-code `if ($carrier === 'ghn')` — chỉ hỏi capability.
3. GHN connector validate `from_address` + `district_id`/`ward_code` của buyer trước khi gọi API → fail-fast với message tiếng Việt.
4. GHN `parseWebhook` + controller `CarrierWebhookController` chung (route `/webhook/carriers/{carrier}`) verify bằng `Token` header so với `carrier_accounts.credentials.token`.
5. `GhnStatusMap`: `ready_to_pick`/`picking` → `awaiting_pickup` (không còn map về `created`).
6. FE: `CarrierBadge` (màu chuẩn theo carrier) + chip "Chờ lấy hàng" + nhãn tiếng Việt `Shipment::statusLabel()`.
7. `/settings/carriers`: form GHN có phần "Địa chỉ kho hàng" (`meta.from_address.{name, phone, address, ward_name, district_name, province_name, district_id, ward_code}`).

## 2. Trong / ngoài phạm vi

**Trong (SPEC này):**
- Thêm `Shipment::STATUS_AWAITING_PICKUP` constant + thêm vào `OPEN_STATUSES`. Thêm helper `Shipment::statusLabel(string)` trả nhãn tiếng Việt cho mọi status.
- Thêm capability `awaiting_pickup_flow` cho `CarrierConnector`; GHN khai báo true, manual không. `ShipmentService::markPacked` đọc cap này để chọn `awaiting_pickup` vs `packed`.
- Thêm capability `webhook` cho `CarrierConnector`; GHN khai báo true. `CarrierWebhookController` chỉ chấp nhận carrier có cap này.
- `GhnConnector::createShipment` validate fail-fast (`validateShipmentPayload`): thiếu `district_id`/`ward_code` của buyer → "Đơn thiếu mã quận của GHN…"; thiếu `from_address.{name,phone,address,district_id}` → "Cài đặt GHN chưa có…".
- `GhnConnector::createShipment` payload mở rộng: truyền `from_name`/`from_phone`/`from_address`/`from_ward_name`/`from_district_name`/`from_province_name` từ `carrier_account.meta.from_address`.
- `GhnConnector::parseWebhook` — parse body `{OrderCode, Status, Time}` → DTO chuẩn `{tracking_no, raw_status, status, occurred_at, raw}`.
- `GhnStatusMap`: `ready_to_pick`/`picking`/`money_collect_picking` → `STATUS_AWAITING_PICKUP` (hiện đang map `STATUS_CREATED`); các status khác giữ nguyên.
- `CarrierWebhookController` chung (route `POST /webhook/carriers/{carrier}`): kiểm cap webhook → verify `Token` header so với mọi `carrier_accounts.credentials.token` → resolve tenant → parse via connector → idempotent dedupe qua `(shipment_id, code, occurred_at)` → cập nhật shipment + sync order status. Trả 200 ack kể cả không match shipment (tránh GHN retry storm).
- `ShipmentService::syncTracking` thêm `awaiting_pickup` vào `$known` + auto-set `picked_up_at` khi shipment chuyển sang `picked_up`.
- `OrderResource.shipment` thêm field `status_label`.
- FE: `components/CarrierBadge.tsx` (bảng `CARRIER_META = {ghn, ghtk, jt, viettelpost, ninjavan, spx, vnpost, ahamove, manual}` + icon CarOutlined). Áp vào `OrdersPage` cột ĐVVC + chip phụ "Chờ lấy hàng" khi `shipment.status='awaiting_pickup'`. Cập nhật `SHIPMENT_STATUS_LABEL` + `ShipmentStatusTag` thêm `awaiting_pickup`.
- FE `CarrierAccountsPage`: với GHN, modal "Thêm ĐVVC" hiện thêm phần "Địa chỉ kho hàng" (8 trường) → submit lưu vào `meta.from_address`. `district_id` parse int.
- Test: feature `ManualOrderGhnFulfillmentTest` (full flow), `CarrierWebhookGhnTest` (webhook verify + idempotent).

**Ngoài (làm sau / spec khác):**
- **Free-text address → GHN district_id/ward_code lookup** (v1 yêu cầu shop nhập `district_id`/`ward_code` ở `order.shipping_address` hoặc dùng địa chỉ buyer đã có chứa các field này — auto-resolve là follow-up; xem SPEC 0006 §3 ghi chú).
- GHTK / J&T / ViettelPost connector — chỉ tạo skeleton (chưa code).
- GHN print AWB A5 (vì FE đã có A6 default, A5 là tuỳ chọn — chỉ truyền format vào `getLabel`).
- Order status mới `awaiting_pickup` ở level ENUM (KHÔNG làm — order vẫn ở `ready_to_ship`; "Chờ lấy hàng" là shipment-level state, FE render label theo shipment.status khi carrier ngoài 'manual').
- Quote/cước phí GHN trước khi tạo đơn (`quote`) — connector chưa override; chấp nhận để follow-up.

## 3. Câu chuyện người dùng / luồng chính

```
T+0  ─── Shop tạo đơn manual qua /orders/new → đơn ở "Chờ xử lý" (pending)
T+1m ─── Shop bấm "Chuẩn bị hàng" (POST /orders/{id}/ship, carrier_account_id = GHN account)
        → ShipmentService::createForOrder
          - Carrier `ghn` có cap `defer_dispatch` → KHÔNG gọi GHN
          - Tạo shipment {carrier:'ghn', tracking_no:null, status:'pending', carrier_account_id:N}
          - Order: pending → processing
          - Queue PrintJob type=delivery (render phiếu giao hàng cục bộ theo khổ giấy
            tenant.settings.print.label_size, mặc định A6 — giữ nguyên logic cũ).
        → FE: badge "GHN" hiện ở đơn; nút "Sẵn sàng bàn giao"

T+2m ─── Shop tải phiếu PDF + in + đóng gói thực tế
T+5m ─── Shop bấm "Sẵn sàng bàn giao" (POST /shipments/pack, shipment_ids:[…])
        → ShipmentService::markPacked
          - Phát hiện shipment.status='pending' + carrier `ghn` có cap `defer_dispatch`
            → gọi connector.createShipment($accountArr, $payload) — GHN trả tracking
            → shipment {tracking_no:'GH123', status:'awaiting_pickup', fee, raw}
            → fetchLabel() best-effort kéo AWB PDF (tách kho `labels` riêng, không đè
              phiếu cục bộ A6 — đây là tem AWB của GHN, lưu ở label_url nếu trước đó null).
            → KHÔNG còn nhánh order = ready_to_ship qua /rts (không phải Lazada) — order
              được cập nhật trực tiếp ở dòng cuối hàm: ready_to_ship.

T+10m─── GHN system: shipper được phân, status `ready_to_pick`
T+30m─── GHN webhook POST /webhook/carriers/ghn  {OrderCode:'GH123', Status:'picking', Token:<carrier_account.token>}
        → CarrierWebhookController::handle('ghn')
          - Verify: token header so với mọi carrier_account.credentials.token (tenant resolution)
          - GhnConnector::parseWebhook(req) → {tracking_no, raw_status, occurred_at, status:'created'}
          - Append shipment_event + cập nhật shipment.status nếu chuyển (vẫn ở awaiting_pickup/created)
T+1h ─── GHN webhook: Status='picked'
        → shipment.status = picked_up, picked_up_at=now, order → shipped (qua syncOrderToShipmentStatus)
        → InventoryChanged → trừ tồn (logic có sẵn ở OrderUpserted listener)
        
T+1d ─── Delivered: webhook Status='delivered'
        → shipment.status = delivered, order → delivered
```

## 4. Hành vi & quy tắc nghiệp vụ

1. **Capability `defer_dispatch`**:
   - `false` (mặc định, `manual`) → giữ behavior cũ: `createForOrder` gọi connector ngay.
   - `true` (`ghn`, sau này GHTK/J&T): `createForOrder` trì hoãn; `markPacked` gọi.
   - Connector quyết định, core check `$connector->supports('defer_dispatch')`. KHÔNG hard-code `if ($carrier === 'ghn')` ở core.

2. **Mapping shipment → order status** (cập nhật):
   - `pending` (no tracking yet) → order = `processing` (giữ nguyên — phiếu giao hàng đã in)
   - `awaiting_pickup` (đã đẩy GHN, chờ shipper) → order = `ready_to_ship` (giữ nguyên enum)
   - `picked_up` → `shipped` (đã có)
   - `in_transit` → `shipped` (đã có)
   - `delivered` → `delivered` (đã có)

3. **Idempotency**:
   - `markPacked` chạy 2 lần → lần 2 thấy `awaiting_pickup` (chứ không `pending`) → no-op.
   - Webhook trùng → `shipment_events` unique `(shipment_id, code, occurred_at)` đã chống.
   - `createShipment` GHN có `client_order_code` = `order.order_number` → GHN side dedupe.

4. **Lỗi gọi GHN ở `markPacked`**:
   - Throw `RuntimeException` với message tiếng Việt → FE toast → user thấy lý do (vd "Địa chỉ không đầy đủ — thiếu `district_id`").
   - Shipment KHÔNG flip sang `awaiting_pickup` (giữ `pending`) → user sửa địa chỉ, gói lại, bấm "Sẵn sàng bàn giao" → retry.
   - Lỗi `from_address` thiếu ⇒ message rõ "Cài đặt ĐVVC chưa có địa chỉ kho hàng — vào /settings/carriers".

5. **Webhook verify**:
   - GHN gửi `Token` header bằng API token của shop (theo doc GHN webhook). Verify = tra `carrier_accounts.credentials.token` của tenant nào trùng → resolve tenant → ingest.
   - Sai token ⇒ `401`, không lưu, không tin payload.
   - Idempotent + ack `200` nhanh + xử lý async qua job nhẹ (v1 xử lý sync trong controller vì payload nhỏ; nâng cấp queue khi có nhu cầu).

6. **In ấn**:
   - "Chuẩn bị hàng" → phiếu giao hàng cục bộ (`type=delivery`, khổ giấy tenant config).
   - Sau `markPacked` (GHN tạo đơn) → tem AWB GHN có thể tải qua `/api/v1/print-jobs type=label` (đã có).
   - Hai phiếu khác nhau, lưu R2 riêng — user có thể in cả hai hoặc chỉ tem AWB.

7. **`from_address` ở `carrier_account.meta`**:
   - Lưu `{name, phone, address, district_id, ward_code}`.
   - GHN bắt buộc `district_id` + `ward_code`. Manual không cần.

8. **Backwards compatibility**:
   - Đơn manual + `carrier=manual` → KHÔNG đổi gì (cap `defer_dispatch`=false).
   - Đơn sàn (channel order) → KHÔNG đổi gì (luồng A vẫn dùng `prepareChannelOrder`).
   - Đơn manual + `carrier=ghn` **trước SPEC này** (đã có tracking_no) → markPacked sẽ thấy `status='created'` thay vì `'pending'` → vẫn vào nhánh cũ, không gọi lại GHN.

## 5. Dữ liệu

### 5.1 Migration
Không cần migration mới — `Shipment.status` đã là `string`, chỉ thêm const enum trong code. Index hiện có trên `(tenant_id, status)` đủ cho query.

### 5.2 Event
Không có event mới. `ShipmentCreated` vẫn phát ở thời điểm createForOrder (xem note ở §3 — sự kiện chỉ ý nghĩa "shipment record được tạo", không phải "carrier đã có đơn").

### 5.3 Config bổ sung
- `config/fulfillment.php`: thêm `'ghn_webhook_log' => bool` (default false) — chỉ debug.

## 6. API & UI

### 6.1 Endpoints

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| POST | `/webhook/carriers/ghn` | webhook signature | Nhận status push của GHN (Token header). Verify token → resolve tenant + carrier_account → parseWebhook → cập nhật shipment_events + shipment.status. Idempotent. Ack 200 nhanh dù lỗi parse (tránh GHN retry storm). |

**Sửa endpoint hiện có:**
- `OrderResource.shipment` thêm `status_label` (vd `"Chờ lấy hàng"` khi `awaiting_pickup`, `"Đã đóng gói"` khi `packed`).
- `CarrierAccountController::store/update` validate `meta.from_address` (nếu carrier='ghn').

### 6.2 FE
- `components/CarrierBadge.tsx` — `<Tag color={CARRIER_META[code].color}>{CARRIER_META[code].name}</Tag>`. Bảng `CARRIER_META = { ghn:{color:'green',name:'GHN'}, ghtk:{color:'orange',name:'GHTK'}, jt:{color:'red',name:'J&T'}, manual:{color:'default',name:'Tự vận chuyển'} }`. Render trong `OrdersPage` cột ĐVVC + chi tiết đơn.
- `pages/CarrierAccountsPage` — thêm phần "Địa chỉ kho hàng" (cho phép nhập `from_address.{name,phone,address,district_id,ward_code}` khi tạo/sửa account GHN). UI dùng `Form.Item` lồng nhau, không thêm `<Select>` (theo memory `ui-avoid-select-prefer-radio`).
- `OrdersPage` cột "ĐVVC" thay `<Tag>` đơn giản bằng `<CarrierBadge>` + nếu `shipment.status='awaiting_pickup'` thì hiện tag phụ `Chờ lấy hàng` màu cyan.

### 6.3 Webhook log
- `webhook_events` đã có bảng — reuse với `provider='carriers.ghn'`.

## 7. Edge case & lỗi

- **Đơn manual có `carrier=manual` nhưng user đổi sang GHN sau đó (hiện chưa có API đổi)** → không trong scope; user phải tạo lại đơn hoặc admin can thiệp.
- **GHN trả lỗi `district_id` không tồn tại** → throw "Mã quận của GHN không hợp lệ" → user sửa địa chỉ.
- **Webhook đến trước khi `markPacked` (race)** → shipment vẫn ở `pending`, không có `tracking_no` → webhook không thấy match (lookup theo `tracking_no`) → log + ignore. Polling sau sẽ tự sync khi có tracking.
- **GHN webhook bị spoof** (sai token) → 401, không lưu.
- **Order address thiếu `district_id`/`ward_code`** → markPacked throw "Đơn thiếu mã quận/phường của GHN — cập nhật trong sửa đơn".
- **GHN cancel/refund qua webhook** → status `cancel` → shipment → cancelled, order → cancelled.

## 8. Bảo mật & dữ liệu cá nhân

- Token GHN lưu mã hoá ở `carrier_accounts.credentials` (đã có encrypted cast).
- Webhook verify trước khi log; payload có PII buyer (tên/SĐT) → KHÔNG log raw, chỉ ghi `webhook_events.raw_type` + `tracking_no` + `status`.
- Webhook rate limit 600/phút/IP (giống GitHub/SePay).

## 9. Kiểm thử

### 9.1 Feature
- `ManualOrderGhnFulfillmentTest`:
  - Tạo manual order → ship (chọn GHN account) → assert shipment.status=`pending`, no tracking.
  - markPacked → assert connector.createShipment được gọi với payload đúng → shipment.status=`awaiting_pickup`, tracking saved.
  - Send GHN webhook fixture `picked` → assert shipment.status=`picked_up`, order=`shipped`.
  - Send `delivered` → order=`delivered`.

### 9.2 Contract
- `GhnConnectorContractTest`:
  - createShipment với `Http::fake` → request body chứa `payment_type_id`, `to_district_id`, `weight`, `client_order_code`.
  - parseWebhook fixture `{OrderCode:'GH1', Status:'picked'}` → trả `{tracking_no:'GH1', status:'picked_up', occurred_at:string}`.

### 9.3 FE
- Render test `CarrierBadge` với `code='ghn'` → "GHN" + color green.

## 10. Tiêu chí hoàn thành

- [ ] `Shipment::STATUS_AWAITING_PICKUP` const + thêm vào `OPEN_STATUSES`.
- [ ] `AbstractCarrierConnector::supports('defer_dispatch')` hoạt động; `GhnConnector` khai báo cap; `ManualCarrierConnector` không.
- [ ] Manual order + GHN account → "Chuẩn bị hàng" KHÔNG gọi GHN, "Sẵn sàng bàn giao" mới gọi.
- [ ] GHN webhook endpoint nhận, verify, parse, cập nhật shipment status.
- [ ] FE `CarrierBadge` hiện ở list đơn; trang Settings/Carriers cho phép cấu hình `from_address` GHN.
- [ ] Test suite mới ≥ 8 test xanh; toàn bộ Fulfillment test xanh.
- [ ] `endpoints.md` + `fulfillment-and-printing.md` cập nhật.

## 11. Câu hỏi mở

- **Q1 Resolve free-text address → GHN code**: backlog. Có thể dùng GHN `/master-data/province/district/ward` API có cache 24h, hoặc chuẩn hoá địa chỉ qua bảng tỉnh/huyện/phường VN nội bộ.
- **Q2 Quote phí GHN khi tạo đơn**: backlog. UI sẽ hiện ước tính khi user chọn dịch vụ.
- **Q3 GHN OAuth-style auth**: hiện GHN dùng API token tĩnh per shop; nếu họ chuyển sang OAuth sẽ cần connector.refreshToken (cap mới).
