# SPEC 0041: Tích hợp Ahamove (giao hàng tức thời) làm Đơn vị vận chuyển

- **Trạng thái:** Draft
- **Phase:** 5.5 (Fulfillment ĐVVC tự vận chuyển — mở rộng sau GHN/GHTK/Viettel Post)
- **Module backend liên quan:** Fulfillment + Integration layer (`Integrations/Carriers/Ahamove`)
- **Tác giả / Ngày:** lilyrisa · 2026-07-16
- **Liên quan:** ADR-0004 (Connector + Registry), `01-architecture/extensibility-rules.md`, `03-domain/fulfillment-and-printing.md`, SPEC-0006, SPEC-0021 (GHN), SPEC-0034 (Viettel Post). Tài liệu nguồn: `developers.ahamove.com/docs` (Ahamove API v3.0.0).

## 1. Vấn đề & mục tiêu

App đã hỗ trợ GHN, GHTK, Viettel Post cho đơn tự vận chuyển — đều là mô hình "bưu cục": tenant tự có tài khoản/token riêng ở carrier, tạo vận đơn có tem in. **Ahamove** là dịch vụ giao hàng tức thời (xe máy/xe tải, tài xế lấy hàng trực tiếp trong ngày, kiểu Grab/Lalamove) — phù hợp cho seller cần giao nhanh nội thành, bổ sung lựa chọn bên cạnh 3 ĐVVC hiện có.

Mục tiêu: thêm 1 connector Ahamove **không sửa core** (luật vàng "core không biết tên carrier"), chấp nhận **model xác thực khác hẳn 3 carrier trước** (xem §4) và **không có tem in** (Ahamove không có API label — driver lấy hàng trực tiếp).

Vì CMBcoreSeller **chưa có `api_key` cấp Đối tác** do Ahamove duyệt (quy trình đăng ký qua form + UAT ở môi trường staging trước khi được cấp key production — xem "Quy trình chung" trong tài liệu Ahamove), spec này chủ động thiết kế **connector ở trạng thái trơ (inert)**: code đầy đủ, đăng ký vào registry, nhưng không hoạt động được cho tới khi ai đó điền `AHAMOVE_API_KEY` thật vào `.env`. Cùng mẫu với Zalo OA / MISA meInvoice trước khi có env.

## 2. Trong / ngoài phạm vi của spec này

- **Trong:** Connector Ahamove (`createShipment`/`cancel`/`getTracking`/`quote`/`verifyCredentials`/`parseWebhook`), client HTTP (xin token theo yêu cầu — không cache, xem §4), status map (bao gồm sub_status), đăng ký registry + config (trơ tới khi có env), UI chọn Thành phố + loại xe khi thêm tài khoản (form render động theo metadata connector — không hard-code ở FE), hiển thị `shared_link` (link theo dõi tài xế trên bản đồ) cho người mua/người bán thay thế cho tem in, webhook (idempotent, verify secret generic — tái dùng `tracking_lookup` mode có sẵn), tests (contract + feature).
- **Ngoài (làm sau / spec khác):** In tem (Ahamove không có — không áp dụng); "cho xem/thử hàng" (Ahamove không có field tương ứng trong API tạo đơn); đăng ký tài khoản con (`child-accounts`, dùng cho sub-user nội bộ Ahamove — không cần cho MVP, 1 `mobile` = 1 CarrierAccount là đủ); route optimize nhiều điểm giao (`group_service_id`/`route_optimized`, chỉ dùng cho giao hàng 1-N); đặt hẹn giờ giao (`idle_until`) — mặc định giao ngay (`order_time=0`); tính năng đánh giá tài xế (`rate-a-supplier`).

## 3. Câu chuyện người dùng / luồng chính

1. **Thêm tài khoản** (Cài đặt → Đơn vị vận chuyển → Ahamove): nhập **Số điện thoại** (mobile) dùng làm định danh Ahamove User, chọn **Thành phố** (dropdown từ `GET /cities`) và **Loại xe mặc định** (dropdown từ `GET /services?city_id=...`, lưu vào `default_service` sẵn có của `CarrierAccount` dưới dạng `group_service_id`, vd `BIKE`). Lưu → tự `verifyCredentials` (thử xin token; nếu mobile chưa có tài khoản Ahamove, tự đăng ký user mới qua `POST /accounts`).
2. **Ước tính phí** ("Tạo đơn thủ công" / xem trước phí): `ShipmentService::quoteAll` → `AhamoveConnector::quote()` → `POST /orders/estimates` với `service_id = city_id + default_service`.
3. **Tạo vận đơn** ("Chuẩn bị hàng"): `ShipmentService` → `AhamoveConnector::createShipment` → `POST /orders` (`path[0]` = kho/sender, `path[1]` = khách/recipient kèm `cod` + `tracking_number` = mã đơn nội bộ) → lưu `tracking_no = order_id` (Ahamove `_id`), `raw.shared_link` = link theo dõi tài xế.
4. **Theo dõi:** không có tem để in — FE hiển thị nút "Xem tài xế trên bản đồ" dùng `shared_link` (lấy lại bất kỳ lúc nào qua `GET /orders/{id}/shared-link` nếu chưa lưu). `getTracking()` gọi `GET /orders/{id}` lấy `status`/`sub_status` map chuẩn.
5. **Webhook:** Ahamove POST tới `/webhook/carriers/ahamove` (URL cấu hình 1 lần trong Ahamove Partner Portal — không phải per-tenant) mỗi lần đổi trạng thái → cập nhật shipment + đồng bộ order, khớp tenant qua tra cứu `tracking_no` (giống GHTK/VTP).
6. **Hủy:** `cancel` → `DELETE /orders/{order_id}` (chỉ hợp lệ khi đơn đang `IDLE/ASSIGNING/ACCEPTED/CONFIRMING/PAYING`; bắt buộc `comment` lý do nếu đã `ACCEPTED`).

## 4. Hành vi & quy tắc nghiệp vụ

- **Xác thực — 2 tầng, KHÔNG cache token:** Ahamove có 1 `api_key` cấp Đối tác (CMBcoreSeller, từ `config('integrations.ahamove.api_key')`, do Ahamove duyệt — KHÁC hẳn GHN/GHTK/VTP nơi mỗi tenant tự dán token riêng). Mỗi tenant chỉ cần 1 `mobile` (số điện thoại). Mỗi thao tác cần gọi API, `AhamoveClient` luôn xin token mới qua `POST /accounts/token {mobile, api_key}` ngay trước khi gọi API thực (Ahamove **vô hiệu hoá token cũ** mỗi lần cấp token mới — cache sẽ gây race condition giữa các tiến trình song song). Nếu `USER_NOT_FOUND` (404) → tự `POST /accounts {mobile, api_key, name, address}` đăng ký user mới rồi thử lại 1 lần. `CarrierAccount.credentials` (encrypted) chỉ lưu `{mobile, name, address}` — không lưu JWT.
- **Trơ tới khi có env:** `config('integrations.ahamove.api_key')` rỗng ⇒ `verifyCredentials()` trả `ok=false, error_code='invalid_credentials', message='Ahamove chưa được cấu hình ở hệ thống — thiếu AHAMOVE_API_KEY.'` — dùng đúng cơ chế `CarrierAccountController::runVerifyAndPersist` sẵn có (tự set `is_active=false`), không cần sửa controller.
- **Mã dịch vụ:** `service_id = city_id + group_service_id` (vd `SGN-BIKE`). `city_id` + `group_service_id` (loại xe) chọn 1 lần khi thêm tài khoản, lưu ở `CarrierAccount.meta.city_id` + `CarrierAccount.default_service`. Không geocode địa chỉ mỗi đơn.
- **Thanh toán:** `payment_method = CASH` cố định (shop/người gửi trả phí giao hàng cho tài xế bằng tiền mặt) — khớp quy ước hiện có "shop luôn trả cước" (xem `carrier-payment-and-inspection-mapping`). Tiền COD (giá trị hàng hoá thu hộ) truyền riêng ở `path[1].cod`, không liên quan `payment_method`.
- **Giới hạn đã biết (không cố lách):** Ahamove không hỗ trợ "cho xem/thử hàng trước khi nhận" như GHN/GHTK/VTP (không có field tương ứng trong `POST /orders`) — nếu `shipment['allow_inspection']` được truyền, connector bỏ qua (không map, không lỗi).
- **Idempotency:** webhook dedupe theo `(shipment_id, code, occurred_at)` (đã có ở `CarrierWebhookController`, không đổi). Tạo đơn không có tham số chống trùng phía Ahamove — dựa vào `tracking_number` (mã đơn nội bộ) để đối chiếu khi cần, nhưng Ahamove không từ chối trùng `tracking_number` theo mặc định.
- **Hủy đơn:** chỉ hợp lệ ở trạng thái `IDLE/ASSIGNING/ACCEPTED/CONFIRMING/PAYING`; `comment` bắt buộc nếu đã `ACCEPTED` — dùng lý do chuẩn hoá theo danh sách Ahamove cung cấp (cột "User"/"Vi"), mặc định "Người bán huỷ đơn".
- **Phân quyền:** `fulfillment.carriers` (cấu hình tài khoản), `fulfillment.view` (xem) — như GHN/GHTK/VTP.

## 5. Dữ liệu

- Không thêm bảng. Dùng `carrier_accounts` sẵn có:
  - `credentials` (encrypted): `{ mobile, name, address }`.
  - `default_service`: `group_service_id` (vd `BIKE`, `ECO`, `TRUCK-500`).
  - `meta`: `{ city_id, last_verified_at, last_verify_ok, last_verify_error }` (mẫu chung có sẵn).
- Shipment dùng nguyên model + state machine hiện có. `Shipment.raw`/response `createShipment` mang thêm `shared_link` (không cần cột mới — đã có `raw` json để lưu payload gốc theo mẫu GHN).

## 6. API & UI

- **CarrierConnector** dùng: `createShipment/cancel/quote/verifyCredentials/parseWebhook/webhookAuthMode/getTracking`. KHÔNG `getLabel` (không override — giữ mặc định `AbstractCarrierConnector` ném `CarrierUnsupportedException`; `ShipmentService` đã gate qua `supports('getLabel')`).
- **Capabilities:** `['createShipment', 'cancel', 'quote', 'getTracking', 'webhook']` — không có `awaiting_pickup_flow` (Ahamove không có khái niệm "chờ lấy hàng" tách biệt theo cách GHN dùng — driver nhận đơn gần như ngay lập tức) và không có `failed_delivery_collect`.
- **Endpoint mới (proxy form FE):** `POST /api/v1/carrier-accounts/ahamove/master-data` `{ level: 'cities'|'services', city_id? }` → danh mục Thành phố/Loại xe cho form thêm tài khoản (mẫu `ghnMasterData`/`viettelpostMasterData` đã có). Cập nhật `05-api/endpoints.md`.
- **Webhook:** `POST /webhook/carriers/ahamove` (route generic sẵn có, không đổi `CarrierWebhookController`). `webhookAuthMode() = 'tracking_lookup'`: khớp tenant theo `tracking_no` = Ahamove `_id`; verify secret qua `event['secret']` (connector tự đọc header Ahamove gửi theo phương thức đã chọn lúc cấu hình Partner Portal — API Key header/Bearer/Basic) so với `credentials.webhook_secret` — tái dùng cơ chế generic có sẵn của `resolveByTrackingLookup`, không sửa core.
- **FE:** trang `CarrierAccountsPage` — bỏ Ahamove khỏi `COMING_SOON`, thêm `CRED_FIELDS.ahamove` (mobile/name/address + dropdown Thành phố/Loại xe), nút "Xem tài xế trên bản đồ" thay cho nút in tem khi `carrier === 'ahamove'`.

## 7. Edge case & lỗi

- Thiếu `AHAMOVE_API_KEY` ở env → mọi `verifyCredentials`/tạo đơn trả lỗi rõ tiếng Việt, `is_active=false` tự động (xem §4).
- Mobile chưa có tài khoản Ahamove → tự đăng ký (`POST /accounts`) trong `verifyCredentials`/lần gọi đầu; đăng ký thất bại (SĐT không hợp lệ, tài khoản đã tồn tại thuộc Đối tác khác) → surface message Ahamove trả về nguyên văn.
- Địa chỉ kho/khách không đúng định dạng Ahamove yêu cầu (số nhà, đường, phường, quận, tỉnh — xem `MISSING_PATH_INFO`/lỗi 406 khu vực) → `createShipment` ném lỗi tiếng Việt kèm gợi ý sửa địa chỉ theo đúng định dạng Ahamove yêu cầu.
- `INVALID_MAX_DISTANCE`/`INVALID_PICKUP_AREA`/`INVALID_DELIVERY_AREA` (ngoài vùng phủ Ahamove) → thông báo rõ "Ahamove chưa phủ khu vực này", để user chọn ĐVVC khác.
- Webhook đến khi shipment chưa tồn tại/thiếu tracking → ack `200` (tránh Ahamove retry storm), log — giống GHTK/VTP.
- Webhook secret sai/thiếu → `401`; secret rỗng (chưa cấu hình) → chấp nhận + log cảnh báo (giống VTP/GHTK, hạn chế đã biết).
- Token Ahamove hết hạn giữa chừng 1 thao tác (hiếm, vì xin mới mỗi lần) → lỗi `401 NOT_AUTHORIZED` từ Ahamove → connector không tự retry ngầm (tránh vòng lặp), ném lỗi rõ để `ShipmentService` xử lý như lỗi carrier thông thường.

## 8. Bảo mật & dữ liệu cá nhân

- `credentials` (`mobile`, `name`, `address`, `webhook_secret`) `encrypted:array` — không log nguyên văn. JWT token của Ahamove **không lưu DB, không log** — chỉ tồn tại trong bộ nhớ khi thực hiện 1 request. `api_key` cấp Đối tác chỉ ở `config`/`.env` server, không lộ ra FE/API response. Địa chỉ/SĐT người nhận là PII đơn hàng, theo chính sách hiện hành (`08-security-and-privacy.md`).

## 9. Kiểm thử

- **Unit:** `AhamoveStatusMapTest` (status + sub_status → shipment status, ví dụ `ACCEPTED`+`BOARDED`→`awaiting_pickup`, `COMPLETED`→`delivered`, path status `FAILED`→`failed`).
- **Feature:** `ManualOrderAhamoveFulfillmentTest` (`Http::fake` cho `/accounts/token`, `/accounts`, `/orders`, `/orders/{id}`, `/orders/{id}/shared-link`, `/orders/{id}` DELETE) — assert: tự xin token mỗi lần gọi (không cache), tự đăng ký user khi `USER_NOT_FOUND`, payload tạo đơn đúng field, `tracking_no`/`shared_link` lưu đúng, hủy được, quote trả đúng phí; webhook khớp/không khớp secret + idempotent + ack 200 khi thiếu tracking.
- **Contract:** fixtures theo response mẫu lấy trực tiếp từ tài liệu Ahamove (`estimate-order-fee`, `create-order`, `cancel-order`, `get-order-detail`, `get-order-tracking-link`).
- **INERT check:** test xác nhận `verifyCredentials()` trả lỗi rõ khi `AHAMOVE_API_KEY` rỗng (config mặc định trong `.env` test).

## 10. Tiêu chí hoàn thành (Acceptance criteria)

- [ ] Connector Ahamove đăng ký ở `IntegrationsServiceProvider`/`config/integrations.php`, ở trạng thái trơ (không lỗi 500, chỉ báo "chưa cấu hình") khi thiếu `AHAMOVE_API_KEY`.
- [ ] Thêm tài khoản Ahamove (mobile + thành phố + loại xe) hoạt động khi có env thật (test bằng `Http::fake`, chưa cần verify sandbox thật của Ahamove).
- [ ] Tạo vận đơn → có `tracking_no` + `shared_link`; hủy được; quote trả phí.
- [ ] Webhook cập nhật trạng thái + đồng bộ order, idempotent, verify secret generic.
- [ ] Không sửa core theo tên carrier; không sửa `CarrierWebhookController`/`CarrierConnector` interface. pint/phpstan/test filter xanh; FE lint/typecheck/build xanh.
- [ ] Docs cập nhật (spec này, `05-api/endpoints.md`, ghi chú `03-domain/fulfillment-and-printing.md`).

## 11. Câu hỏi mở

- Khi nào CMBcoreSeller nhận được `api_key` Production thật từ Ahamove (cần hoàn tất UAT staging trước) — theo dõi riêng, không chặn việc code connector.
- Danh sách lý do hủy đơn chuẩn hoá của Ahamove (Google Sheet trong tài liệu) cần đối chiếu thủ công 1 lần để chọn message tiếng Việt mặc định phù hợp — follow-up khi có account thật để test.
- Có cần hỗ trợ giao hàng 1-N (`group_service_id`/`route_optimized`, nhiều điểm giao 1 vận đơn) cho use-case gộp đơn không? Để ngoài phạm vi MVP, đánh giá lại khi có nhu cầu thực tế.
