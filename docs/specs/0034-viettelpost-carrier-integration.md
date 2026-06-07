# SPEC 0034: Tích hợp Viettel Post (VTP) làm Đơn vị vận chuyển

- **Trạng thái:** Reviewed
- **Phase:** 5.5 (Fulfillment ĐVVC tự vận chuyển — mở rộng sau GHN/GHTK)
- **Module backend liên quan:** Fulfillment + Integration layer (`Integrations/Carriers/ViettelPost`)
- **Tác giả / Ngày:** lilyrisa · 2026-06-07
- **Liên quan:** ADR-0004 (Connector + Registry), `01-architecture/extensibility-rules.md`, `03-domain/fulfillment-and-printing.md`, SPEC-0006, SPEC-0021 (GHN), thiết kế GHTK 2026-06-03.

## 1. Vấn đề & mục tiêu

App đã hỗ trợ GHN + GHTK cho đơn tự vận chuyển. Người dùng cần thêm **Viettel Post** qua Open API mới (`partner.viettelpost.vn`, tài liệu `partner2.viettelpost.vn/document`). Mục tiêu: thêm 1 connector VTP đầy đủ (tạo vận đơn, in tem, hủy, tính cước, webhook trạng thái) **không sửa core** — đúng luật vàng "core không biết tên carrier".

## 2. Trong / ngoài phạm vi

- **Trong:** Connector VTP (createShipment/getLabel/cancel/quote/verifyCredentials/parseWebhook), client HTTP + quản lý token (2 cách auth), resolver địa chỉ → ID VTP (hỗ trợ địa chỉ 2 cấp mới + 3 cấp cũ), status map, đăng ký registry + config, proxy master-data cho form FE, bật VTP trên trang ĐVVC, webhook secret verify (generic), tests.
- **Ngoài (làm sau):** Pull trạng thái đơn (Open API set này **không có** endpoint tra cứu trạng thái — phụ thuộc webhook); polling backup thật; **sửa đơn đẩy ngược lên VTP** (xem §4); dịch vụ cộng thêm nâng cao (XMG/PTTX) chỉ truyền pass-through.

## 3. Luồng chính

1. **Thêm tài khoản** (Cài đặt → Đơn vị vận chuyển → Viettel Post): nhập **Username + Password** *hoặc* dán **token bí mật** tạo trên viettelpost.vn; chọn **địa chỉ kho** (cascading Tỉnh → Phường theo đơn vị HC mới). Lưu → auto `verifyCredentials` (đăng nhập lấy token).
2. **Tạo vận đơn** ("Chuẩn bị hàng"): `ShipmentService` → `ViettelPostConnector::createShipment` → resolve địa chỉ người nhận sang ID VTP → `POST /v2/order/createOrder` → lưu `tracking_no = ORDER_NUMBER`, `fee = MONEY_TOTAL`.
3. **In tem:** `getLabel` → `POST /v2/order/printing-code` lấy printCode → dựng link `digitalize.viettelpost.vn/.../report.do?type=2&bill={code}` → tải bytes → (HTML thì Gotenberg→PDF) lưu R2.
4. **Sẵn sàng bàn giao:** shipment → `awaiting_pickup` (VTP tới lấy hàng — capability `awaiting_pickup_flow`).
5. **Webhook:** VTP POST tới `/webhook/carriers/viettelpost` mỗi lần đổi trạng thái → cập nhật shipment + đồng bộ order.
6. **Hủy:** `cancel` → `POST /v2/order/UpdateOrder {TYPE:4}` (chỉ khi `ORDER_STATUS < 200`).

## 4. Hành vi & quy tắc nghiệp vụ

- **Xác thực (2 cách):** username/password → `POST /v2/user/Login` → `POST /v2/user/ownerconnect` (token dài hạn ~1 năm); hoặc token web → `POST /v2/user/LoginVTP`. Partner token cache theo `vtp.token.<hash>`, TTL lấy từ claim `exp` của JWT (fallback ~330 ngày). Gửi kèm header `Token` ở mọi call.
- **Địa chỉ ID:** `createOrder` dùng `SENDER_PROVINCE/DISTRICT/WARD` + `RECEIVER_PROVINCE/DISTRICT/WARD` (ID). `ViettelPostAddressResolver` map TÊN → ID qua `/v3/categories/listProvinceNew` + `/v3/categories/listWardsNew` (mới, 2 cấp) và fallback `/v2/categories/list{Province,District,Wards}` (cũ, 3 cấp). Địa chỉ 2 cấp ⇒ `SENDER/RECEIVER_DISTRICT` có thể null.
- **ORDER_PAYMENT:** `cod > 0 ? 3 (thu hộ tiền hàng) : 1 (không thu hộ)`.
- **Sửa đơn (ràng buộc người dùng):** Sau khi đã `createShipment` (đẩy lên VTP), **sửa thông tin đơn trong app chỉ tác động ở app — KHÔNG gọi `/v2/order/edit`**. Connector VTP không expose chức năng sửa đơn lên ĐVVC. Muốn đổi thông tin đã đẩy ⇒ hủy vận đơn rồi tạo lại (hoặc thao tác trực tiếp trên VTP).
- **Idempotency:** webhook dedupe theo `(shipment_id, code, occurred_at)` (đã có ở `CarrierWebhookController`). Tạo đơn `CHECK_UNIQUE=true` chống trùng `ORDER_NUMBER`.
- **Trạng thái cuối** (101/107/201/503/501/504/104): không phát sinh thêm — controller vẫn ack 200.
- **Phân quyền:** `fulfillment.carriers` (cấu hình tài khoản), `fulfillment.view` (xem). Như GHN/GHTK.

## 5. Dữ liệu

- Không thêm bảng. Dùng `carrier_accounts` sẵn có: `credentials` (encrypted) lưu `{username,password}` *hoặc* `{token}` + tùy chọn `webhook_secret`; `meta.from_address` lưu `{name,phone,address, province_id,ward_id,district_id?, province_name,ward_name,district_name?}`.
- Shipment dùng nguyên model + status machine hiện có.

## 6. API & UI

- **CarrierConnector** dùng: `createShipment/getLabel/cancel/quote/services/verifyCredentials/parseWebhook/webhookAuthMode`. KHÔNG `getTracking` (không có API pull).
- **Endpoint mới (proxy form FE):** `POST /api/v1/carrier-accounts/viettelpost/master-data` `{level:'provinces'|'districts'|'wards', province_id?, district_id?, username?, password?, token?}` → danh mục để cascading. Cập nhật `05-api/endpoints.md`.
- **Webhook:** `POST /webhook/carriers/viettelpost` (route generic sẵn có). Verify: body `TOKEN` khớp `credentials.webhook_secret` (nếu cấu hình) — mở rộng generic của `resolveByTrackingLookup`, không hard-code tên carrier.
- **FE:** trang `CarrierAccountsPage` — bỏ VTP khỏi `COMING_SOON`, thêm `CRED_FIELDS.viettelpost` + section địa chỉ kho cascading (v3). `lib/fulfillment.tsx` thêm hook master-data.

## 7. Edge case & lỗi

- Token hết hạn → connector tự đăng nhập lại (cache miss). Sai user/pass/token → `verifyCredentials` trả `invalid_credentials`, auto `is_active=false` khi tạo.
- Resolver không map được tỉnh/phường → `createShipment` ném lỗi tiếng Việt rõ ("Không nhận diện được Tỉnh/Phường VTP …").
- Webhook đến khi shipment chưa tồn tại / thiếu ORDER_NUMBER → ack 200 (tránh retry storm), log.
- Webhook secret sai → 401 (VTP retry ≤5).
- `printing-code` link trả HTML → Gotenberg hoá; trả PDF → lưu thẳng.

## 8. Bảo mật & PII

- `credentials` (username/password/token/webhook_secret) `encrypted:array` — không log. Token partner chỉ ở Cache (server). Địa chỉ/SĐT người nhận là PII đơn hàng, theo chính sách hiện hành.

## 9. Kiểm thử

- **Unit:** `ViettelPostStatusMapTest` (code → shipment status, isFinal).
- **Feature:** `ManualOrderViettelPostFulfillmentTest` (`Http::fake` Login/ownerconnect + categories + createOrder + printing-code + UpdateOrder) — assert payload ID, tracking_no, in tem, awaiting_pickup, hủy; webhook khớp/không khớp secret + idempotent.
- **Contract:** fixtures theo sample tài liệu (response envelope `{status,error,message,data}`).

## 10. Acceptance criteria

- [ ] Thêm/xác thực tài khoản VTP (2 cách) hoạt động; chọn địa chỉ kho cascading.
- [ ] Tạo vận đơn ID-based → có tracking + phí; in tem ra PDF; hủy được; tính cước (quote) trả dịch vụ.
- [ ] Webhook cập nhật trạng thái + đồng bộ order, idempotent, verify secret.
- [ ] Không sửa core theo tên carrier; pint/phpstan/test filter xanh; FE lint/typecheck/build xanh.
- [ ] Docs cập nhật (spec này, `05-api/endpoints.md`, ghi chú `03-domain/fulfillment-and-printing.md`).

## 11. Câu hỏi mở

- Polling backup trạng thái: cần VTP cấp endpoint tra cứu (chưa có trong Open API set này) — follow-up.
- Nhãn in qua URL có asset ngoài: nếu Gotenberg HTML thiếu asset, chuyển sang url-render — follow-up.
