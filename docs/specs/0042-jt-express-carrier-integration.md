# SPEC 0042: Tích hợp J&T Express làm Đơn vị vận chuyển

- **Trạng thái:** Draft
- **Phase:** 5.5 (Fulfillment ĐVVC tự vận chuyển — mở rộng sau GHN/GHTK/Viettel Post/Ahamove)
- **Module backend liên quan:** Fulfillment + Integration layer (`Integrations/Carriers/Jt`)
- **Tác giả / Ngày:** lilyrisa · 2026-07-17
- **Liên quan:** ADR-0004 (Connector + Registry), `01-architecture/extensibility-rules.md`, `03-domain/fulfillment-and-printing.md`, SPEC-0006, SPEC-0021 (GHN), SPEC-0034 (Viettel Post), SPEC-0041 (Ahamove). Tài liệu nguồn: `docs/superpowers/research/2026-07-17-jt-express-api-reference.md` (crawl đầy đủ `open.jtexpress.vn/apiDoc`, English).

## 1. Vấn đề & mục tiêu

App đã hỗ trợ GHN, GHTK, Viettel Post (mô hình "bưu cục", tenant tự có token) và Ahamove (giao tức thời, 2 tầng xác thực). **J&T Express** là ĐVVC phổ biến thứ 3 tại VN sau GHN/GHTK, mô hình giống GHN/VTP (bưu cục, có tem in, có COD) nhưng xác thực **2 tầng như Ahamove** (app-level `apiAccount`/`privateKey` + merchant-level `customerCode`/`password`).

Mục tiêu: thêm 1 connector J&T **không sửa core** (luật vàng "core không biết tên carrier"), khớp interface `CarrierConnector` hiện có (mã carrier `'jt'` đã được dự trù sẵn — comment mẫu trong `CarrierConnector.php` và `COMING_SOON` ở `CarrierAccountsPage.tsx`).

Team **chưa có** `apiAccount`/`privateKey` Production hay tài khoản UAT thật của J&T ⇒ spec này thiết kế connector ở trạng thái **trơ (inert)** — code đầy đủ, đăng ký vào registry, nhưng không hoạt động thật cho tới khi có `JT_API_ACCOUNT`/`JT_PRIVATE_KEY` thật trong `.env`. Cùng mẫu Ahamove/Zalo OA/MISA trước khi có env.

**Rủi ro đã biết cần ghi nhận:** tài liệu J&T công khai **không nêu rõ** (1) cách nối `privateKey` vào chuỗi JSON trước khi MD5 để tính `digest`, (2) cách encode `password` trong `bizContent` (plaintext hay hash?). Xem §4 và §11 — thiết kế cô lập phần ký (`JtSigner`) để có thể sửa/verify 1 chỗ duy nhất khi có tài khoản UAT thật, không phải sửa lan ra `JtClient`/`JtConnector`.

## 2. Trong / ngoài phạm vi

- **Trong:** Connector J&T đầy đủ 6 capability: `createShipment` (addOrder), `cancel` (cancelOrder), `quote` (getComCost), `getLabel` (printOrder — base64, 1 đơn/lần), `getTracking` (logistics/trace — polling backup, tối đa 30 mã/lần), `webhook` (statusFeedback push). Đăng ký registry + config (trơ tới khi có env). UI: mở J&T khỏi `COMING_SOON`, thêm `CRED_FIELDS.jt` (customerCode/password) + Radio chọn `pay_type` (PP_CASH/PP_PM) trong modal thêm/sửa tài khoản. Chỉ hỗ trợ địa chỉ hành chính quốc gia mới (`selfAddress=1`, 2 cấp) — tái dùng nguồn dữ liệu địa chỉ đã có cho GHN, không xin danh mục riêng của J&T. Tests (unit + feature, theo field đã crawl thật).
- **Ngoài (làm sau / spec khác):** **Batch printing** (`printOrders`, in hàng loạt ≤200 đơn, trả link PDF thay vì base64) — chưa có tính năng "in hàng loạt" chung ở FE để gắn vào, thêm khi có nhu cầu thật. Hỗ trợ `selfAddress=0` (danh mục địa chỉ riêng J&T) — J&T không có API public tra cứu tỉnh/quận/phường của họ, để ngoài phạm vi trừ khi J&T cấp riêng. "Cho xem/thử hàng" — J&T `addOrder` không có field tương ứng, connector bỏ qua nếu order có set (không lỗi, không map), giống Ahamove. Dịch vụ đổi/trả hàng nâng cao (`isExchange`/`isCallBeforeReturn`/`stayWarehouseDays`/`courierReceipt` — các field chỉ có bản tài liệu tiếng Trung, chưa rõ UI cần gì) — connector không set, để mặc định J&T.

## 3. Câu chuyện người dùng / luồng chính

1. **Thêm tài khoản** (Cài đặt → Đơn vị vận chuyển → J&T Express): nhập **Mã khách hàng (customerCode)** + **Mật khẩu (password)** do J&T cấp khi ký hợp đồng, chọn **Cách trả cước** (Radio: "Trả trước tiền mặt" `PP_CASH`, mặc định | "Đối soát theo tháng" `PP_PM`, chỉ chọn được nếu tenant có hợp đồng riêng — ghi chú tiếng Việt rõ). Lưu → tự `verifyCredentials`.
2. **Ước tính phí** ("Tạo đơn thủ công" / xem trước phí): `ShipmentService::quoteAll` → `JtConnector::quote()` → `POST /api/spmComCost/getComCost`.
3. **Tạo vận đơn** ("Chuẩn bị hàng"): `ShipmentService` → `JtConnector::createShipment` → `POST /api/order/addOrder` → lưu `tracking_no = billCode`, `fee` từ response.
4. **In tem:** `getLabel` → `POST /api/order/printOrder` → giải base64 → lưu R2 (giả định PDF — xem §7 xử lý khi sai định dạng).
5. **Theo dõi:** `getTracking()` → `POST /api/logistics/trace` (polling backup, tự động được `SyncShipmentTracking` gọi vì carrier-agnostic — không cần sửa job). Webhook: J&T POST tới URL do mình cung cấp cho support J&T đăng ký thủ công (**không tự cấu hình qua console** — khác GHN/VTP) → `/webhook/carriers/jt` → cập nhật shipment + đồng bộ order.
6. **Hủy:** `cancel` → `POST /api/order/cancelOrder` (theo mô tả "trước khi lấy hàng" — chưa có bảng ràng buộc trạng thái công khai, để J&T tự trả lỗi `999010010` nếu không hợp lệ).

## 4. Hành vi & quy tắc nghiệp vụ

- **Xác thực — 2 tầng (giống Ahamove, khác VTP):**
  - **Cấp ứng dụng:** `apiAccount` (Number) + `privateKey` — **1 cặp cho cả platform CMBcoreSeller**, cấp trong J&T Console App Management, từ `config('integrations.jt.api_account'/'private_key')`. Gửi kèm `timestamp` (ms, UTC+7) ở mọi request.
  - **Cấp merchant:** `customerCode` + `password` — **per-tenant**, nằm trong `bizContent`, lưu `CarrierAccount.credentials` (encrypted).
  - **Chữ ký (`JtSigner`, cô lập riêng):** `digest = base64(md5(JSON(bizContent) + privateKey))`. Vì tài liệu không xác nhận cách nối `privateKey` (cuối chuỗi JSON hay field riêng) và cách encode `password`, `JtSigner` implement đúng công thức literal đã tài liệu hoá (`json_encode($bizContent) . $privateKey`, MD5 ra byte rồi base64), đánh dấu rõ bằng comment "CHƯA VERIFY với tài khoản UAT thật" — sửa 1 chỗ này khi có tài khoản test.
- **Trơ tới khi có env:** `config('integrations.jt.api_account')` hoặc `private_key` rỗng ⇒ `verifyCredentials()` trả `ok=false, error_code='invalid_credentials', message='J&T Express chưa được cấu hình ở hệ thống — thiếu JT_API_ACCOUNT/JT_PRIVATE_KEY.'` — tái dùng cơ chế `runVerifyAndPersist` sẵn có (tự set `is_active=false`), không sửa controller.
- **Địa chỉ:** chỉ hỗ trợ `selfAddress=1` (danh mục hành chính quốc gia mới, 2 cấp). `sender`/`receiver` gửi `prov` + `area` (Phường/Xã) + `address` chi tiết; **bỏ trống `city`** (đúng ghi chú J&T "địa chỉ mới không cần điền trường này"). Tái dùng nguồn dữ liệu địa chỉ quốc gia đã dùng cho GHN — không cần `JtAddressResolver` riêng.
- **`pay_type` theo tài khoản:** `CarrierAccount.meta.pay_type` (`'PP_CASH'` mặc định | `'PP_PM'`) — field cố định của tài khoản (như Ahamove `city_id`/`default_service`), KHÔNG dùng bag `meta.defaults` (dành cho field override-được-theo-đơn). `codMoney` map thẳng từ `shipment.cod_amount`, độc lập với `pay_type`.
- **Loại hàng/dịch vụ:** `goodsType` mặc định `bm000010` (Goods/hàng hóa) trừ khi order đánh dấu tài liệu/hàng tươi sống (chưa có field tương ứng ở app hiện tại — để mặc định, follow-up nếu cần). `productType` mặc định `EXPRESS`. `serviceType` mặc định `1` (Pickup — J&T tới lấy hàng, khớp hành vi GHN/VTP hiện tại). `deliveryType` mặc định `1` (Normal delivery).
- **Không map "cho xem/thử hàng":** J&T `addOrder` không có field `required_note`/`allow_inspection` tương đương — connector bỏ qua nếu order có set, không lỗi, không map (giống Ahamove §4 SPEC-0041).
- **Idempotency:** webhook dedupe theo `(shipment_id, code, occurred_at)` (đã có ở `CarrierWebhookController`, không đổi). `txlogisticId` = mã đơn nội bộ, dùng làm chống trùng phía J&T theo tài liệu ("No Duplicate") — nếu trùng, J&T trả lỗi `145003204 Duplicate order please update customer order number`, connector surface nguyên văn.
- **Hủy đơn:** `reason` bắt buộc theo API — connector tự sinh lý do mặc định "Người bán huỷ đơn qua CMBcore Seller" nếu UI chưa có ô nhập, giống Ahamove/VTP.
- **Phân quyền:** `fulfillment.carriers` (cấu hình tài khoản), `fulfillment.view` (xem) — như các carrier khác.

## 5. Dữ liệu

- Không thêm bảng. Dùng `carrier_accounts` sẵn có:
  - `credentials` (encrypted): `{ customerCode, password, webhook_secret? }`.
  - `meta`: `{ pay_type, last_verified_at, last_verify_ok, last_verify_error }` (mẫu chung có sẵn).
- Shipment dùng nguyên model + state machine hiện có. `Shipment.raw` mang thêm `sortLine` (mã tuyến phân loại nội bộ J&T, không dùng để hiển thị nhưng lưu lại tham khảo khi cần đối soát).

## 6. API & UI

- **CarrierConnector** dùng: `createShipment/cancel/quote/getLabel/getTracking/verifyCredentials/parseWebhook/webhookAuthMode`. `capabilities()`: `['createShipment', 'cancel', 'quote', 'getLabel', 'getTracking', 'webhook']` — không `awaiting_pickup_flow` (chưa xác nhận J&T có trạng thái "chờ lấy hàng" tách biệt qua API — `serviceType=1` Pickup chỉ chọn hành vi lấy hàng, không phải capability riêng).
- **Webhook:** `POST /webhook/carriers/jt` (route generic sẵn có, không đổi `CarrierWebhookController`). `webhookAuthMode() = 'tracking_lookup'`: khớp tenant theo `tracking_no = billCode`; verify secret qua `credentials.webhook_secret` (optional — J&T không công bố cơ chế secret rõ ràng nào, seller có thể tự thoả thuận 1 giá trị với support J&T nếu được) — tái dùng cơ chế generic có sẵn của `resolveByTrackingLookup`, rỗng cả 2 bên thì chấp nhận + log cảnh báo, không sửa core.
- **FE:** trang `CarrierAccountsPage` — bỏ J&T khỏi `COMING_SOON`, thêm `CRED_FIELDS.jt` (`customerCode`, `password`) + Radio.Group `pay_type` (theo quy ước UI hiện có — không Select).
- Cập nhật `05-api/endpoints.md` (không có endpoint proxy master-data mới vì không hỗ trợ `selfAddress=0`).

## 7. Edge case & lỗi

- Thiếu `JT_API_ACCOUNT`/`JT_PRIVATE_KEY` ở env → mọi `verifyCredentials`/thao tác trả lỗi rõ tiếng Việt, `is_active=false` tự động (xem §4).
- `digest` sai (do công thức ký chưa verify) → J&T trả `145003030 headers signature verification failed` — connector surface nguyên văn để dễ debug khi test UAT thật lần đầu.
- `customerCode`/`password` sai → `999001030 customerCode or password is wrong` → surface nguyên văn, gợi ý kiểm tra lại thông tin J&T cấp.
- Trùng `txlogisticId` → `145003204` → surface nguyên văn (đề xuất seller không tự tạo lại đơn cùng mã).
- Ngoài vùng phủ / thiếu tỉnh nhận-gửi → `999009010`/`999009020`/`1450033315` → thông báo rõ "Vui lòng kiểm tra lại địa chỉ" kèm nguyên văn lỗi J&T.
- `getLabel` trả base64 không phải PDF (`%PDF` header không khớp) → log cảnh báo `jt.label.unexpected_format`, vẫn lưu file nguyên trạng (không chặn luồng) để không làm gãy "Chuẩn bị hàng" — cần verify thật khi có tài khoản UAT.
- Webhook đến khi shipment chưa tồn tại/thiếu `billCode` → ack `200` (tránh J&T retry storm), log — giống GHTK/VTP/Ahamove.
- Webhook secret sai (khi seller đã tự cấu hình `webhook_secret`) → `401`; secret rỗng cả 2 bên (mặc định, vì J&T không có cơ chế chuẩn) → chấp nhận + log cảnh báo (hạn chế đã biết — giống VTP/GHTK).
- `cancelOrder` sau khi đã pickup → khả năng cao trả `999010010 order status can not be cancel` (suy từ bảng lỗi, chưa có tài liệu ràng buộc trạng thái rõ) → surface nguyên văn.

## 8. Bảo mật & dữ liệu cá nhân

- `credentials` (`customerCode`, `password`, `webhook_secret`) `encrypted:array` — không log nguyên văn. `apiAccount`/`privateKey` cấp ứng dụng chỉ ở `config`/`.env` server, không lộ ra FE/API response. Địa chỉ/SĐT người nhận là PII đơn hàng, theo chính sách hiện hành (`08-security-and-privacy.md`).

## 9. Kiểm thử

- **Unit:** `JtSignerTest` — test tính chất của `digest` (deterministic, base64 hợp lệ, đổi input → đổi output) chứ **không** test khớp giá trị kỳ vọng từ J&T thật (chưa có tài khoản để lấy expected value — xem §11). `JtStatusMapTest` — cover cả bảng công bố (103/104/105/106/109/110/112/113/116/117/118/120/121) **và** các `scanTypeCode` thực tế quan sát được trong ví dụ response thật của tài liệu (10, 50, 92, 94, 100), code lạ → `null`.
- **Feature:** `JtClientTest` (`Http::fake` đúng field đã crawl: `addOrder`/`cancelOrder`/`getComCost`/`printOrder`/`trace`). `ManualOrderJtFulfillmentTest` (full flow qua HTTP thật của app — mirror `ManualOrderViettelPostFulfillmentTest`): tạo đơn đúng payload, `tracking_no`/`fee` lưu đúng, in tem base64→lưu file, hủy được, quote trả đúng phí, webhook idempotent + ack 200 khi thiếu tracking + verify secret khi có cấu hình.
- **INERT check:** test xác nhận `verifyCredentials()` trả lỗi rõ khi `JT_API_ACCOUNT`/`JT_PRIVATE_KEY` rỗng (mirror `AhamoveInertConfigTest`).
- **Contract:** fixtures theo response mẫu lấy trực tiếp từ `docs/superpowers/research/2026-07-17-jt-express-api-reference.md`.

## 10. Tiêu chí hoàn thành (Acceptance criteria)

- [ ] Connector J&T đăng ký ở `IntegrationsServiceProvider`/`config/integrations.php`, ở trạng thái trơ (không lỗi 500, chỉ báo "chưa cấu hình") khi thiếu `JT_API_ACCOUNT`/`JT_PRIVATE_KEY`.
- [ ] Thêm tài khoản J&T (customerCode/password + pay_type) hoạt động khi có env thật (test bằng `Http::fake`, chưa cần verify sandbox thật của J&T).
- [ ] Tạo vận đơn → có `tracking_no`; in tem base64 → lưu file; hủy được; quote trả phí.
- [ ] `getTracking()` hoạt động, tự động được `SyncShipmentTracking` gọi (carrier-agnostic, không sửa job).
- [ ] Webhook cập nhật trạng thái + đồng bộ order, idempotent, verify secret generic (chấp nhận rỗng + log cảnh báo).
- [ ] Không sửa core theo tên carrier; không sửa `CarrierWebhookController`/`CarrierConnector` interface. pint/phpstan/test filter xanh; FE lint/typecheck/build xanh.
- [ ] Docs cập nhật (spec này, `05-api/endpoints.md`, ghi chú `03-domain/fulfillment-and-printing.md`).

## 11. Câu hỏi mở

- **Công thức `digest` chính xác** (cách nối `privateKey`) và **cách encode `password`** trong `bizContent` — tài liệu J&T không nêu rõ, ví dụ không nhất quán giữa các trang. **Chặn việc verify thật** (không chặn việc code) — cần tài khoản UAT thật + có thể cần hỏi support J&T hoặc dò qua Postman/SDK mẫu nếu J&T cung cấp. Đây là rủi ro lớn nhất của spec này.
- **Cơ chế bảo mật webhook** — J&T không công bố secret/signature header nào; cần hỏi support J&T khi đăng ký URL webhook xem có cách nào verify được không, tránh webhook giả mạo.
- **Định dạng file thật** trong `base64EncodeContent` của `printOrder` (PDF/PNG/HTML?) — cần giải mã thử khi có tài khoản UAT.
- **Ràng buộc trạng thái hủy đơn** — chỉ suy đoán từ error code `999010010`, chưa có bảng trạng thái nào cho phép/không cho phép hủy công khai.
- Khi nào có `apiAccount`/`privateKey` Production thật từ J&T (cần hoàn tất đăng ký + UAT trước) — theo dõi riêng, không chặn việc code connector.
