# SPEC: Tuỳ chọn giao hàng cho đơn tự tạo/tự giao + "Giao thất bại – Thu tiền" + luồng hoàn

- **Trạng thái:** Draft
- **Phase:** 5.5 (Fulfillment ĐVVC tự vận chuyển — mở rộng sau GHN/GHTK/VTP)
- **Module backend liên quan:** Fulfillment + Orders + Integration layer (`Integrations/Carriers/{Ghn,Ghtk,ViettelPost}`)
- **Tác giả / Ngày:** lilyrisa · 2026-07-07
- **Liên quan:** ADR-0004 (Connector + Registry), `01-architecture/extensibility-rules.md`, `03-domain/fulfillment-and-printing.md`, `03-domain/order-status-state-machine.md`, SPEC-0021 (GHN), SPEC-0034 (VTP), thiết kế GHTK 2026-06-03.

## 1. Vấn đề & mục tiêu

Với **đơn tự tạo + tự giao** (manual order đẩy ĐVVC — KHÔNG phải đơn sàn), người bán cần điều khiển các tuỳ chọn giao hàng theo **từng đơn lúc tạo**, mà hiện đang bị hard-code hoặc rớt:

1. **Ghi chú giao hàng** người bán nhập lúc tạo đơn **không được đồng bộ sang GHN** (connector GHN không gửi `note`; GHTK/VTP đã gửi).
2. **Chế độ xem hàng** (`required_note`: cho thử / cho xem không thử / không cho xem) đang **cứng** `KHONGCHOXEMHANG`.
3. **Ai trả phí ship** (shop trả / người nhận trả — cộng vào COD) đang **cứng** (`payment_type_id = cod>0?2:1`).
4. **"Giao thất bại – Thu tiền"**: khi khách từ chối nhận, GHN cho thu một khoản (thường = phí ship) rồi chuyển hoàn luôn (khỏi giao lại 3 lần). VTP có nghiệp vụ tương đương (dịch vụ **XMG** + `EXTRA_MONEY`). App **chưa hỗ trợ** truyền/khai báo khoản này.
5. **Luồng hoàn**: hệ thống **không ghi nhận** kết quả tài chính khi đơn hoàn về — không biết đã thu được COD/khoản-thất-bại hay chưa, phí hoàn bao nhiêu; và order **kẹt ở `Returning`**, không tự lên `ReturnedRefunded` khi hàng đã về kho.

Mục tiêu: thêm nhóm tuỳ chọn giao hàng per-đơn (có default cấp shop), map đúng field từng ĐVVC theo luật "core không biết tên carrier", và **hoàn thiện luồng trạng thái/tài chính khi đơn hoàn về** — phân biệt rõ 2 nhánh: *hoàn – đã thu khoản thất bại* vs *hoàn – khách từ chối cả khoản thất bại*.

## 2. Trong / ngoài phạm vi

- **Trong:**
  - 4 tuỳ chọn per-đơn (ghi chú, chế độ xem hàng, ai trả phí ship, giao-thất-bại-thu-tiền) + default cấp shop (Cài đặt → ĐVVC).
  - Lưu options trên `orders`; default trên `tenant.settings.shipping.*`.
  - `ShipmentService` truyền options xuống connector; mapping ở **từng connector** (GHN/GHTK/VTP) + capability `failed_delivery_collect`.
  - Ghi nhận **kết quả hoàn** trên `shipments`: `cod_collected`, `failed_collect_collected`, `return_fee`; đọc từ webhook/callback.
  - Sửa state machine: tách `returning` (đang hoàn) vs `returned` (đã về kho) → order `Returning` vs `ReturnedRefunded`.
  - FE form tạo đơn (`CreateOrderPage`) + trang Cài đặt ĐVVC (default) + hiển thị kết quả hoàn ở chi tiết đơn.
  - Tests: unit (mapping từng connector, status map), feature (tạo đơn → payload đúng; webhook hoàn → cột outcome + order status).
- **Ngoài (làm sau):**
  - Đối soát P&L đầy đủ khoản thu-thất-bại vào `OrderProfitService` (spec này chỉ **lưu + hiển thị**; hook profit là follow-up).
  - Áp dụng cho **đơn sàn** (đơn sàn dùng AWB/nghiệp vụ của sàn — ngoài phạm vi).
  - Nghiệp vụ "giao 1 phần" / đổi-trả.

## 3. Luồng chính

1. **Cài đặt shop** (Cài đặt → ĐVVC → "Tuỳ chọn giao hàng mặc định"): đặt default cho chế độ xem hàng, ai trả phí ship, và **số tiền "giao thất bại – thu tiền"** (vd 30.000đ, bật/tắt). Lưu `tenant.settings.shipping`.
2. **Tạo đơn** (`CreateOrderPage`, đơn manual): mục "Tuỳ chọn giao hàng" **prefill từ default shop**, sửa được theo đơn:
   - Ghi chú giao hàng (text).
   - Chế độ xem hàng (Radio): Không cho xem / Cho xem không thử / Cho thử.
   - Ai trả phí ship (Radio): Shop trả (freeship) / Người nhận trả (cộng COD).
   - Giao thất bại – thu tiền (Switch + InputNumber VND) — **chỉ hiện khi ĐVVC đã chọn hỗ trợ** (GHN, VTP); GHTK ẩn + chú thích.
   Lưu vào `orders`.
3. **Chuẩn bị hàng / đẩy ĐVVC**: `ShipmentService::buildShipmentPayload` đọc options từ order → truyền xuống `CarrierConnector::createShipment` → connector map field sàn tương ứng.
4. **Giao thất bại – thu tiền** (nhánh nghiệp vụ):
   - Khách xem hàng rồi **đồng ý trả khoản thất bại (30k)** → ĐVVC thu 30k, đơn chuyển hoàn. Webhook trả trạng thái hoàn + số đã thu → shipment `failed_collect_collected=30000`, order `Returning` → `ReturnedRefunded` khi về kho.
   - Khách **từ chối cả 30k** → giao thất bại thường, hàng hoàn, `failed_collect_collected=0`.
5. **Đơn hoàn về kho**: webhook trạng thái `returned`/tương đương → shipment `STATUS_RETURNED` + `return_fee` → order `ReturnedRefunded`. Chi tiết đơn hiển thị: "Đã hoàn — đã thu 30.000đ" hoặc "Đã hoàn — không thu được".

## 4. Hành vi & quy tắc nghiệp vụ

- **Chỉ áp cho đơn manual** (`channel_account_id === null`). Đơn sàn: bỏ qua các option này (dùng nghiệp vụ sàn).
- **Chuẩn hoá option (core, không theo tên sàn):** `ShipmentService` truyền xuống connector các khoá chuẩn:
  - `delivery_note` (string), `inspection` (`none|view|trial`), `fee_payer` (`shop|recipient`), `failed_collect_amount` (int VND, 0 = tắt).
- **Mapping từng connector (không đụng core):**
  | Khoá chuẩn | GHN | GHTK | VTP |
  |---|---|---|---|
  | delivery_note | `note` | `note` (đã có) | `ORDER_NOTE` |
  | inspection none/view/trial | `required_note` = KHONGCHOXEMHANG / CHOXEMHANGKHONGTHU / CHOTHUHANG | cờ tương ứng (nếu có) | dịch vụ đính kèm |
  | fee_payer shop/recipient | `payment_type_id` 1/2 | `is_freeship` 1/0 | `ORDER_PAYMENT`: shop→(COD?4:1), recipient→(COD?3:2) |
  | failed_collect_amount | `cod_failed_amount` | **không hỗ trợ** → bỏ qua | dịch vụ **XMG** + `EXTRA_MONEY` |
- **Capability map:** thêm `failed_delivery_collect` vào capability của connector (GHN=true, VTP=true, GHTK=false, Manual=false). `ShipmentService` chỉ truyền `failed_collect_amount` khi `supports('failed_delivery_collect')`; connector không hỗ trợ → **im lặng bỏ qua** (không throw, không hard-code tên carrier ở core). FE ẩn field khi capability=false.
- **Ràng buộc VTP:** `EXTRA_MONEY` ≤ 2× tổng cước (theo tài liệu VTP) → connector clamp; cần kèm dịch vụ XMG.
- **GHN `cod_failed_amount`:** field không nằm trong bảng public id=123 → **verify tên/định dạng thật khi tích hợp** (có thể phải bật tính năng trên tài khoản GHN). Nếu GHN từ chối field → connector nuốt lỗi mềm + log (không chặn tạo đơn).
- **Luồng hoàn (sửa state machine):**
  - Thêm shipment status `STATUS_RETURNING` (đang hoàn, hàng đang về) tách khỏi `STATUS_RETURNED` (đã về kho người gửi).
  - GHN: `delivery_fail`, `waiting_to_return` → `FAILED`; `return`,`returning`,`return_transporting`,`return_sorting` → `RETURNING`; `returned` → `RETURNED`. (GHTK/VTP map tương tự trong status map của chúng.)
  - `syncOrderStatus`: `FAILED`→`DeliveryFailed`, `RETURNING`→`Returning`, `RETURNED`→`ReturnedRefunded`.
- **Ghi nhận tài chính khi hoàn:** connector `parseWebhook` trả thêm (khi payload có): `cod_collected`, `failed_collect_collected`, `return_fee`. Controller ghi vào shipment (chỉ ghi đè khi giá trị mới không null). Idempotent theo `(shipment_id, code, occurred_at)` như hiện tại.
- **Idempotency:** không đổi — dedupe shipment_events sẵn có.
- **Phân quyền:** như hiện tại — `fulfillment.prepare` (đẩy ĐVVC), `tenant.settings` (default shop), `fulfillment.view` (xem kết quả).

## 5. Dữ liệu

- **`orders`** (migration thêm cột, nullable, reversible):
  - `delivery_note` (text, null)
  - `delivery_inspection` (string 16, null) — `none|view|trial`
  - `delivery_fee_payer` (string 12, null) — `shop|recipient`
  - `failed_collect_amount` (unsigned int, null) — VND, null/0 = tắt
- **`shipments`** (migration thêm cột, nullable):
  - `cod_collected` (unsigned int, null) — COD thực thu (từ callback)
  - `failed_collect_collected` (unsigned int, null) — khoản "giao thất bại" thực thu
  - `return_fee` (unsigned int, null) — phí hoàn ĐVVC tính
  - hằng status mới `STATUS_RETURNING = 'returning'`
- **`tenant.settings.shipping`** (JSON, không cần migration): `{ default_inspection, default_fee_payer, failed_collect_enabled, failed_collect_amount }`.
- **Domain event:** tái dùng event cập nhật order status hiện có; không thêm bảng.

## 6. API & UI

- **Tạo/cập nhật đơn** (`OrderController` store/update, đơn manual): FormRequest nhận thêm 4 khoá option → lưu cột `orders`. Cập nhật `05-api/endpoints.md`.
- **Cài đặt ĐVVC** (endpoint settings sẵn có): thêm nhóm `shipping` vào payload settings (whitelist).
- **`CarrierConnector`**: dùng method `createShipment` sẵn có — **chỉ mở rộng payload đọc thêm 4 khoá chuẩn**; thêm capability `failed_delivery_collect` vào capability map của từng connector. Không thêm method mới; **không** thêm nhánh theo tên sàn ở core.
- **FE:**
  - `CreateOrderPage`: mục "Tuỳ chọn giao hàng" (Radio theo `ui-avoid-select-prefer-radio`, icon @ant-design/icons). Field "giao thất bại thu tiền" hiện/ẩn theo capability ĐVVC đã chọn (lấy từ config capability phơi qua API carriers).
  - `SettingsCarrier*`: form default shop.
  - `OrderDetailBody`: hiển thị kết quả hoàn (đã thu COD / đã thu khoản thất bại / phí hoàn).
- **Job:** không thêm job mới; polling backup trạng thái tái dùng cơ chế hiện có (đọc thêm field outcome nếu poll trả).

## 7. Edge case & lỗi

- ĐVVC không hỗ trợ `failed_delivery_collect` (GHTK) mà order lỡ có `failed_collect_amount` > 0 → connector bỏ qua, FE đã ẩn field; log debug.
- GHN từ chối `cod_failed_amount` (tài khoản chưa bật) → nuốt lỗi field đó, vẫn tạo đơn; surface cảnh báo mềm.
- Webhook hoàn đến **out-of-order / trùng** → dedupe sẵn có; chỉ ghi outcome khi field mới non-null (không xoá giá trị đã có).
- Đơn manual chưa đẩy ĐVVC mà đổi option → chỉ đổi ở app; lần đẩy sau dùng giá trị mới.
- `failed_collect_amount` vượt trần VTP (2× cước) → clamp + log.
- Địa chỉ gộp tỉnh/thiếu mã (đã có xử lý ở resolver) — không thuộc spec này.

## 8. Bảo mật & dữ liệu cá nhân

- Ghi chú giao hàng là text tự do — không log nguyên văn ở mức info; escape khi render tem/PDF (đã có ở label renderer).
- Không thêm PII mới. Số tiền = integer VND (không float), đúng convention.

## 9. Kiểm thử

- **Unit (mapping):** mỗi connector (GHN/GHTK/VTP) — cho input 4 khoá chuẩn, khẳng định payload sàn đúng field (GHN `note`/`required_note`/`payment_type_id`/`cod_failed_amount`; GHTK `note`/`is_freeship`, KHÔNG có failed-collect; VTP `ORDER_NOTE`/`ORDER_PAYMENT`/XMG+`EXTRA_MONEY` clamp).
- **Unit (status map):** GHN/GHTK/VTP raw → `RETURNING` vs `RETURNED` đúng; `syncOrderStatus` → `Returning` vs `ReturnedRefunded`.
- **Unit (capability):** `supports('failed_delivery_collect')` đúng theo carrier.
- **Feature:** tạo đơn manual với options → cột `orders` đúng; đẩy ĐVVC (connector fake) → payload chứa field mong đợi. Webhook hoàn (2 nhánh: thu 30k / từ chối) → `shipments.failed_collect_collected` + order status đúng.
- **FE:** field failed-collect ẩn khi chọn GHTK; prefill từ default shop.

## 10. Tiêu chí hoàn thành

- [ ] 4 option per-đơn lưu trên `orders`; default cấp shop hoạt động (prefill + sửa được).
- [ ] Ghi chú đồng bộ sang cả 3 ĐVVC (fix GHN đang rớt).
- [ ] `failed_collect_amount` map đúng GHN (`cod_failed_amount`) + VTP (XMG/`EXTRA_MONEY`); GHTK ẩn/bỏ qua.
- [ ] Capability `failed_delivery_collect` + FE ẩn field theo carrier.
- [ ] State machine: `RETURNING` vs `RETURNED` → order `Returning` vs `ReturnedRefunded`.
- [ ] Webhook ghi `cod_collected`/`failed_collect_collected`/`return_fee`; phân biệt 2 nhánh hoàn.
- [ ] Chi tiết đơn hiển thị kết quả hoàn.
- [ ] Tests unit + feature xanh; docs cập nhật (`endpoints.md`, fulfillment domain, capability).

## 11. Câu hỏi mở

- Tên/định dạng chính xác `cod_failed_amount` trên GHN API v2 (verify sandbox; có thể cần bật tính năng tài khoản).
- Có cần default **per-ĐVVC** (mỗi carrier 1 số tiền) thay vì 1 default chung? (Mặc định spec: 1 default chung, đủ dùng — YAGNI.)
- Hook đối soát P&L khoản thu-thất-bại vào `OrderProfitService`: làm ngay hay tách spec sau?
