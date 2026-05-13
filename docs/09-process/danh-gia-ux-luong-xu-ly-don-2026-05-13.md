# Đánh giá UX — Luồng xử lý đơn / "Chuẩn bị hàng" / Fulfillment TikTok Shop

- **Ngày:** 2026-05-13
- **Phạm vi rà soát:** toàn bộ `docs/` (trọng tâm `specs/0006`, `specs/0009`, `specs/0013`, `04-channels/tiktok-shop.md`, `04-channels/order-processing.md`), `sdk_tiktok_seller` (generation `202309`, các API fulfillment), và code thực tế:
  - Backend: `Modules/Fulfillment/Services/{ShipmentService,OrderStatusSync,PrintService,PrintTemplates}.php`, `Modules/Fulfillment/Http/Controllers/{ShipmentController,PrintJobController}.php`, `Modules/Orders/Services/OrderStateMachine.php`, `Modules/Orders/Http/Controllers/OrderController.php`, `Integrations/Channels/TikTok/TikTokConnector.php`, `config/integrations.php`, `config/fulfillment.php`.
  - Frontend: `pages/OrdersPage.tsx`, `components/OrderProcessing.tsx`, `components/OrderDetailBody.tsx`, `lib/{orders,fulfillment,format}.tsx`, `pages/SettingsPrintPage.tsx`, `pages/CarrierAccountsPage.tsx`.
- **Câu hỏi đánh giá:** Logic có chặt và đúng không? Hiển thị (tab, nút, trạng thái, badge) có rõ ràng không? Thông báo (message/Modal/Alert/Tooltip/lỗi API) có dễ hiểu, dễ tiếp cận với người bán Việt Nam không?
- **Kết luận ngắn:** Luồng nghiệp vụ **đúng và an toàn** (idempotent, không chặn cứng khi sàn lỗi, chặn in phiếu khi âm tồn). Hiển thị **tốt ở mức khá** nhưng còn một số chỗ thuật ngữ kỹ thuật lọt ra UI, một vài trạng thái "ngầm" người dùng không thấy được nguyên nhân, và thông báo lỗi đôi khi để lộ chuỗi exception thô. Danh sách việc nên sửa ở §6.

---

## 1. Đánh giá LOGIC nghiệp vụ

### 1.1 Điểm tốt

| Khía cạnh | Đánh giá |
|---|---|
| **Idempotent toàn tuyến** | `createForOrder` (đã có vận đơn open ⇒ trả lại), `markPacked`/`handover`/`scan-*` (đã ở trạng thái sau ⇒ no-op + `409`), `OrderStatusSync::apply` (from == to ⇒ bỏ qua; ngoài `onlyFrom` ⇒ chỉ re-fire để tồn kho bắt kịp). Quét đơn 2 lần không gây sai lệch. **Đạt.** |
| **Không chặn khi sàn lỗi** | TikTok `arrangeShipment` lỗi (API down, chưa có `package_id`, `UnsupportedOperation`) ⇒ `ShipmentService::arrangeOnChannel` bắt, bọc lại, `prepareChannelOrder` gắn `has_issue` + `issue_reason` (≤240 ký tự) — **vẫn tạo vận đơn cục bộ, vẫn đẩy đơn `pending → processing`, vẫn queue phiếu giao hàng tự tạo**. Người bán không bị kẹt. **Đạt.** |
| **Chặn in phiếu khi âm tồn** | `isOutOfStock()` = có dòng `sku_id ≠ null` mà `∑ on_hand − ∑ reserved < 0` ⇒ `RuntimeException` ⇒ controller trả `422` key `order`; UI ẩn nút, hiện "Hết hàng" disable + checkbox bị khoá. Đúng mục tiêu SPEC 0013 (tránh ĐVVC tới lấy hụt → sàn phạt). **Đạt.** |
| **Tách 3 bước rõ ràng** | `pending` (chưa arrange/in) → `processing` (đã arrange/in, đang gói+quét) → `ready_to_ship` (đã gói+quét xong) → `shipped` (bàn giao, trừ tồn). `ready_to_ship` chuẩn **chỉ đạt được bằng thao tác nội bộ** (`markPacked`), không từ raw status sàn — đúng thiết kế. **Đạt.** |
| **Trừ tồn đúng chỗ** | Reserve ở `pending`/`processing`, trừ thật (`order_ship`) ở `shipped` (bàn giao thật / `scan-handover` / sàn báo `SHIPPED`), idempotent. **Đạt.** |
| **`getShippingDocument` lấy tem thật** | Không tự vẽ lại tem sàn; chỉ khi không kéo được tem thật mới render "phiếu giao hàng tự tạo" theo khổ cài đặt. Đúng domain doc §9.1. **Đạt.** |
| **Bám SDK chính thức** | Endpoint fulfillment khớp `sdk_tiktok_seller` 202309: `POST /fulfillment/202309/packages/{id}/ship` (body `handover_method`), `GET .../packages/{id}`, `GET .../packages/{id}/shipping_documents`, `GET .../packages/{id}/handover_time_slots`. `chooseHandover()` tự chọn DROP_OFF vs PICKUP+slot, có fallback DROP_OFF, và **dung thứ lỗi chính tả `avaliable` của TikTok** (`data_get($s,'avaliable', data_get($s,'available',true))`). Cẩn thận, đúng. **Đạt.** |

### 1.2 Vấn đề / rủi ro logic

- **L1 — `pack`/`handover` bulk nuốt lỗi im lặng.** `ShipmentController::pack()` và `handover()` lặp qua từng vận đơn, `catch (\Throwable)` rồi **chỉ đếm số thành công** — không trả `errors[]`. Khác với `bulkCreate`/`bulkRefetchSlip` (trả `errors[{order_id,message}]`). UI hiện "Đã đóng gói 3 đơn" trong khi chọn 5 đơn → người dùng **không biết 2 đơn nào lỗi vì sao**. → Nên trả `errors[]` và hiện `Modal.warning` như bulk-prepare. (Mức: trung bình.)

- **L2 — Phiếu giao hàng tự tạo lỗi ⇒ đơn kẹt tab "Đang tải lại" vô thời hạn.** `queueDeliverySlip()` bọc `try/catch` chỉ `Log::warning` — nếu Gotenberg/queue lỗi, đơn có vận đơn open **không `label_path`, không `has_issue`** ⇒ rơi vào filter `slip=loading` mãi mãi (không tự retry, không cảnh báo). Lỗi render thực sự (nếu job có chạy) nằm ở `print_jobs.error`, chỉ lộ khi bấm tải xuống → `409 'Tệp in chưa sẵn sàng (lỗi: …)'`. → Nên: job render `delivery` thất bại sau hết `tries` ⇒ set `order.has_issue` + `issue_reason` để đơn nhảy sang tab "Nhận phiếu giao hàng" (failed) thay vì "Đang tải lại". (Mức: trung bình.)

- **L3 — Mọi `RuntimeException` của `ShipmentService` đều thành `422` key `order`.** OK với pre-check (terminal/âm tồn) và lỗi ĐVVC đơn manual, nhưng key cố định `order` khiến FE chỉ có một thông điệp chung. `errorMessage(e)` ở FE có rút được message này không thì cần kiểm — nếu chỉ hiện "Có lỗi" thì người dùng mất thông tin "vì âm tồn" / "ĐVVC chưa bật". → Xác nhận `errorMessage()` đọc được `errors.order[0]`. (Mức: thấp–trung bình.)

- **L4 — `getShippingDocument` nuốt HTTP status của `doc_url`.** Khi tải PDF từ `doc_url` mà non-200 (link hết hạn 24h, R2 chặn…), code chỉ `$resp->successful() ? body : ''` rồi ném `'TikTok không trả về tệp tem cho package …'`. Status/body gốc mất → khó chẩn đoán khi hỗ trợ khách. → Log kèm `$resp->status()`. (Mức: thấp.)

- **L5 — `issue_reason` cắt 240 ký tự ở `prepareChannelOrder` nhưng 240 ở chỗ khác?** `arrangeOnChannel` đã `Str::limit(..., 150)` phần class+msg, rồi `prepareChannelOrder` lại `Str::limit(..., 240)`. Cột đã widen sang `text` (migration `2026_05_20_100002`) — nếu cột là `text` thì việc cắt 240 chỉ để UI gọn, hợp lý; nhưng nên thống nhất 1 hằng số. (Mức: rất thấp / dọn dẹp.)

- **L6 — Code chết.** `lib/fulfillment.tsx` còn `useProcessingBoard`/`useProcessingCounts`/`useReadyOrders` + `STAGE_LABEL` (SPEC 0009) nhưng `OrdersPage.tsx` đã chuyển sang tab theo trạng thái; `/fulfillment` chỉ redirect. Backend `ShipmentController::processing/ready/processingCounts` + `applyStageScope` vẫn còn. Không sai nhưng dễ gây nhầm cho người đọc code/tài liệu. → Xoá hoặc đánh dấu `@deprecated`. (Mức: thấp.)

---

## 2. Đánh giá HIỂN THỊ (UI)

### 2.1 Điểm tốt

- **Tab trạng thái nhất quán với cột "Trạng thái".** `ORDER_STATUS_TABS` (Tất cả / Chờ xử lý / Đang xử lý / Chờ bàn giao / Đang giao / Đã giao / Hoàn tất / Trả/hoàn / Đã huỷ) + extras (Có vấn đề, Hết hàng, Vận đơn) — đều có `Badge` đếm số. Người bán quen BigSeller/sàn sẽ thấy quen.
- **Cột "Thao tác" hiện nút theo trạng thái vận đơn**, không phải "menu 10 nút luôn hiện" — đúng nguyên tắc progressive disclosure. Chưa có vận đơn ⇒ "Chuẩn bị hàng (lấy phiếu)"; `created` ⇒ "In phiếu giao hàng" / "Đã gói & sẵn sàng bàn giao" / "Nhận phiếu giao hàng lại" (đỏ nếu `has_issue`); `packed` ⇒ "Bàn giao ĐVVC" / "In lại phiếu"; luôn ⇒ "In hoá đơn".
- **Badge số lần in** (`PrintCountBadge`) màu xanh khi in 1 lần, cam khi >1, tooltip "Đã in N lần · gần nhất …" — giúp tránh in trùng/quên in.
- **Lợi nhuận ước tính gộp vào cột "Tổng tiền"** (dòng "LN: ±X ₫" + tooltip phân rã phí sàn / phí vận chuyển / giá vốn, ⚠ nếu thiếu giá vốn SKU) — gọn, không thêm cột. Tốt.
- **Tag "Chưa liên kết SKU — Liên kết"** bấm được ⇒ mở modal liên kết ngay; alert đầu trang "Có N đơn chưa liên kết SKU — chưa thể trừ tồn" + nút "Liên kết hàng loạt". Dẫn dắt hành động rõ.
- **Sub-tab "Tình trạng phiếu giao hàng"** (Tất cả / Có thể in / Đang tải lại / Nhận phiếu giao hàng) chỉ hiện khi có ≥1 đơn lỗi phiếu, dùng `Radio.Group` button — đúng memory "tránh `<Select>` cho tập nhỏ".
- **Icon dùng `@ant-design/icons`**, không emoji — đúng memory dự án.
- **`PrintJobBar`**: poll job → `Result` "đã sẵn sàng (N đơn)" → nút "Mở để in" (mở PDF tab mới) → bước "Đánh dấu đã in" với `InputNumber` số bản — luồng in 2 bước rõ, có nhắc "bỏ qua N đơn không có tem/phiếu".
- **`SettingsPrintPage`**: 5 khổ (A6 / 100×150mm / 80mm cuộn nhiệt / A5 / A4) với hint cụ thể; giải thích "tem thật của sàn/ĐVVC luôn giữ khổ gốc". Người bán hiểu được.

### 2.2 Vấn đề hiển thị

- **D1 — Thuật ngữ kỹ thuật lọt ra UI / message.**
  - `message.success('Đã chuẩn bị hàng — đang đẩy trạng thái lên sàn & lấy phiếu giao hàng')` và bulk `'... đang đẩy trạng thái lên sàn & lấy phiếu'` — "đẩy trạng thái lên sàn" là ngôn ngữ developer. Người bán hiểu hơn nếu: *"Đã chuẩn bị hàng — đang lấy phiếu giao hàng của sàn"* hoặc *"… đang đồng bộ với sàn"*.
  - `issue_reason` mặc định khi connector chưa hỗ trợ: `'Chưa lấy được mã vận đơn từ sàn — bật đồng bộ fulfillment ("luồng A") hoặc "Sắp xếp vận chuyển" trên app sàn rồi "Đồng bộ đơn".'` — chuỗi `"luồng A"`, `fulfillment` là nội bộ. Người bán chỉ cần: *"Chưa lấy được mã vận đơn từ sàn. Hãy vào app TikTok Shop bấm 'Sắp xếp vận chuyển' cho đơn này rồi bấm 'Đồng bộ đơn'."*
  - `UnsupportedOperation::for('tiktok', 'arrangeShipment (đặt INTEGRATIONS_TIKTOK_FULFILLMENT=true để bật "luồng A")')` — nếu chuỗi này có khả năng hiển thị cho người bán (qua `issue_reason` chẳng hạn) thì để lộ tên biến môi trường là không ổn. Nên có map chuyển sang câu thân thiện trước khi lưu vào `issue_reason`.
  - `'Không cập nhật được trạng thái "đã in đơn" lên sàn (TikTokApiException: …)'` — để nguyên `class_basename` (`TikTokApiException`) trong ngoặc. Người bán không cần thấy tên class; chỉ cần: *"Không cập nhật được đơn lên TikTok Shop (mã lỗi sàn: …)"* hoặc rút gọn message gốc của API.
  - (Mức: trung bình — đây là phần "dễ hiểu/dễ tiếp cận" mà câu hỏi nhắm tới.)

- **D2 — Phân biệt "tem sàn" vs "phiếu giao hàng" dễ rối.** Trong `OrderActions` có đồng thời: "In phiếu giao hàng", "In tem sàn", "In lại phiếu", "In hoá đơn"; `PRINT_TYPE_LABEL` map `label → 'tem sàn'`, `delivery → 'phiếu giao hàng'`, `packing/picking/invoice` … Với người bán, "tem sàn" và "phiếu giao hàng" nghe gần như nhau. → Cân nhắc: chỉ hiện **một** nút "In phiếu/tem giao hàng" — hệ thống tự chọn `label` (nếu có tem thật) hay `delivery` (tự tạo); ghi rõ trong tooltip "Tem thật của TikTok Shop" vs "Phiếu giao hàng tự tạo (chưa có tem sàn)". (Mức: trung bình.)

- **D3 — Trạng thái "Đang tải lại" không nói lý do và không có hành động.** Sub-tab `loading` = đơn có vận đơn nhưng chưa có `label_path`, `has_issue=false`. Người dùng nhìn vào không biết: hệ thống đang render? đang đợi sàn? bị treo? → Thêm dòng giải thích trong tab này: *"Hệ thống đang lấy/ tạo phiếu giao hàng. Nếu quá 1–2 phút chưa có, bấm 'Làm mới' hoặc chuyển sang tab 'Nhận phiếu giao hàng' để yêu cầu lại."* và cân nhắc auto-poll. (Mức: trung bình.)

- **D4 — Nhãn trạng thái vận đơn vs trạng thái đơn dùng từ khác nhau cho cùng khái niệm.** `SHIPMENT_STATUS_LABEL`: `picked_up → 'Đã bàn giao ĐVVC'`, `in_transit → 'Đang vận chuyển'`. `ORDER_STATUS_LABEL`: `shipped → 'Đang vận chuyển'`, `ready_to_ship → 'Chờ bàn giao'`. `STAGE_LABEL` (code chết): `prepare → 'Cần xử lý'` trong khi tab dùng `'Chờ xử lý'`. Không sai nhưng nên rà cho thống nhất một bộ từ vựng (đặc biệt nếu giữ lại STAGE_LABEL). (Mức: thấp.)

- **D5 — "Hết hàng" disable nhưng không chỉ rõ SKU nào.** Tooltip "Đơn có SKU âm tồn — không thể chuẩn bị hàng / lấy phiếu giao hàng. Hãy nhập thêm hàng." — đúng nhưng người bán phải tự mở chi tiết đơn → đối chiếu tồn từng SKU. → Tooltip/chi tiết đơn nên liệt kê SKU nào âm và thiếu bao nhiêu (đã có `netStockForSku`, chỉ là chưa surface). (Mức: thấp–trung bình.)

- **D6 — Empty state ổn nhưng "Vận đơn" tab thiếu hướng dẫn.** "Chưa có vận đơn nào." — không nói làm sao có (phải "Chuẩn bị hàng" ở tab đơn). So với tab đơn ("Chưa có đơn hàng. Kết nối gian hàng … hoặc bấm 'Đồng bộ đơn'.") thì empty state này nghèo hơn. (Mức: rất thấp.)

- **D7 — Modal cảnh báo lợi nhuận âm: chữ "Để xem lại" cho nút Cancel hơi lạ.** `cancelText: 'Để xem lại'` — đọc cụt. → `'Để tôi xem lại'` hoặc đơn giản `'Huỷ'`. `okText: 'Vẫn chuẩn bị'` thì ổn. (Mức: rất thấp.)

---

## 3. Đánh giá THÔNG BÁO (message / Modal / Alert / lỗi)

### 3.1 Điểm tốt

- **Phân tầng đúng:** thành công nhẹ → `message.success`; lỗi từng-phần (bulk) → `Modal.warning` liệt kê `Đơn #id: lý do`; lỗi chặn → `message.error(errorMessage(e))`; cảnh báo cần quyết định (lợi nhuận âm) → `Modal.confirm` với nút nguy hiểm.
- **403 có thông điệp tiếng Việt rõ ràng theo từng quyền:** "Bạn không có quyền tạo vận đơn." / "… in tem." / "… đóng gói." / "… bàn giao." / "… quét đóng gói." — tốt, không phải "Forbidden".
- **409 chống thao tác trùng nói rõ:** "Đơn này đã được đóng gói trước đó." / "… bàn giao trước đó." / "Vận đơn đã huỷ." — người quét đơn hiểu ngay.
- **404 quét sai mã:** "Không tìm thấy vận đơn hoặc đơn ứng với mã đã quét." — rõ.
- **Event log của vận đơn bằng tiếng Việt:** "Đã chuẩn bị hàng — mã vận đơn của sàn: …", "Đã đóng gói & quét đơn", "Đã bàn giao ĐVVC", "Đã huỷ vận đơn" — đọc được trong timeline chi tiết đơn.
- **`ScanTab`** ghi log từng dòng kết quả/lỗi trong phiên, có empty "Chưa quét gì trong phiên này." — phù hợp thao tác kho liên tục.

### 3.2 Vấn đề thông báo

- **N1 — Lỗi render phiếu in lộ exception thô.** `PrintJobController::download` → `409 'Tệp in chưa sẵn sàng (lỗi: '.$job->error.')'` với `$job->error` = message exception gốc (có thể là lỗi Gotenberg dài dòng tiếng Anh, hoặc `'Không có vận đơn nào có tem để in.'`). `PrintJobBar` cũng `message.error('Tạo phiếu in lỗi: '.job.error)`. → Map các lỗi phổ biến sang câu thân thiện; lỗi lạ thì hiện "Tạo phiếu in lỗi, vui lòng thử lại hoặc liên hệ hỗ trợ" + log chi tiết. (Mức: trung bình.)

- **N2 — `bulkRefetchSlip` trả message "Đơn chưa "Chuẩn bị hàng" (chưa có vận đơn)."** — ngoặc kép lồng nhau hiển thị xấu trong `<li>`. Nội dung OK, format nên bỏ ngoặc kép: *"Đơn chưa được chuẩn bị hàng (chưa có vận đơn)."* (Mức: thấp.)

- **N3 — Thiếu phản hồi tiến trình nền cho người dùng.** "Chuẩn bị hàng" trả về ngay sau khi tạo `Shipment` + queue delivery slip; việc kéo tem thật / render PDF chạy nền. `message.success` nói "đang lấy phiếu" nhưng **không có nơi nào người dùng theo dõi được "đang lấy" → "xong"** ngoài việc tự bấm "Làm mới" và đọc badge phiếu in. → Hoặc auto-refresh danh sách sau N giây, hoặc một toast/thông báo khi `PrintJobCompleted`/label đã về. (Liên quan D3, L2.) (Mức: trung bình.)

- **N4 — "Đồng bộ đơn" và "Nhận phiếu giao hàng lại" dễ nhầm vai trò.** Người bán gặp đơn lỗi phiếu có 2 lựa chọn gần giống: "Đồng bộ đơn" (header, kéo đơn mới + cập nhật trạng thái) và "Nhận phiếu giao hàng lại" (retry arrange + kéo tem cho đơn đã chuẩn bị). → Trong tab "Nhận phiếu giao hàng" (failed), nên có một dòng hướng dẫn ngắn: *"Các đơn dưới đây đã chuẩn bị hàng nhưng chưa lấy được phiếu của sàn. Bấm 'Nhận phiếu giao hàng' để thử lại; nếu vẫn lỗi, vào app TikTok Shop kiểm tra đơn."* (Mức: thấp–trung bình.)

- **N5 — Thông báo thành công không nêu hệ quả trạng thái.** Ví dụ "Đã chuẩn bị hàng …" — không nói "đơn đã chuyển sang tab 'Đang xử lý'". Với người mới, mất dấu đơn vừa thao tác. → Thêm: *"… — đơn chuyển sang 'Đang xử lý'."* (Tham khảo: "Đánh dấu gói xong — chờ bàn giao ĐVVC" đã làm đúng kiểu này.) (Mức: thấp.)

---

## 4. Đối chiếu tài liệu ↔ code

- **SPEC 0013 mô tả đúng code hiện tại** (3 bước, chặn âm tồn, `prepareChannelOrder`, `arrangeOnChannel`, phiếu giao hàng tự tạo, `out_of_stock`, filter `stage`/`slip`, `INTEGRATIONS_TIKTOK_FULFILLMENT`). Đây là spec "Implemented" và bám sát thực tế — tốt.
- **Phần "Ngoài phạm vi" của SPEC 0013 nói "luồng A là follow-up, hiện chỉ sinh phiếu tự tạo"** — nhưng commit `09fec9a`/`5b94e5f`/`1b7c88a` đã **wire luồng A cho TikTok** và bật mặc định (`fulfillment_enabled = true`). → Cập nhật SPEC 0013 (hoặc viết SPEC 0014) ghi nhận luồng A TikTok đã Implemented; nếu không, tài liệu sẽ mâu thuẫn với code. **(Việc cần làm.)**
- `docs/05-api/endpoints.md`, `docs/03-domain/order-status-state-machine.md`, `docs/04-channels/order-processing.md` — SPEC 0013 §6 yêu cầu cập nhật; cần kiểm xem đã cập nhật endpoint `bulk-refetch-slip`, `print-jobs/{id}/mark-printed`, `type=delivery` chưa.
- **SDK `sdk_tiktok_seller`**: code dùng đúng paths/params của generation `202309` cho fulfillment. Một điểm cần đối chiếu sandbox: response key `tracking_number` vs `package.tracking_number`, `shipping_provider_name` vs `package.shipping_provider_name` — code đã thử cả hai (`data_get(... ) ?: data_get(...)`), an toàn. Endpoint `handover_time_slots` field `can_drop_off` / `can_pickup` / `pickup_slots[].avaliable` (typo) — khớp với tài liệu SDK; giữ comment giải thích typo là tốt cho người bảo trì.

---

## 5. Khả năng tiếp cận (accessibility) — quan sát nhanh

- Toàn bộ chuỗi đã Việt hoá, dùng `toLocaleString('vi-VN')` cho số/ngày, tiền tệ `₫`. **Tốt cho người dùng VN.**
- Dùng `Tooltip` cho hầu hết tag/icon-only button — nhưng **tooltip không tiếp cận được bằng bàn phím/đọc màn hình** trên một số nút icon-only (`<Button icon={...} />` không có `aria-label`/`title`). Với người vận hành kho dùng bàn phím + máy quét, nên thêm `title`/text nhãn cạnh icon ở các nút chính ("Làm mới", "In phiếu", "Đồng bộ").
- Phân biệt trạng thái chủ yếu bằng **màu** (Tag `error` đỏ, badge xanh/cam, link đỏ `#cf1322`) — nên kèm icon (đã có `WarningOutlined`/`PrinterOutlined` ở nhiều chỗ) để không phụ thuộc màu (mù màu).
- `Modal.confirm` lợi nhuận âm dùng `okButtonProps:{danger:true}` (đỏ) — kèm chữ "Vẫn chuẩn bị" nên không phụ thuộc màu. OK.
- Bảng đơn dài + nhiều cột: cân nhắc cho ẩn/hiện cột hoặc responsive — không phải accessibility thuần nhưng ảnh hưởng dùng trên màn nhỏ.

---

## 6. Việc nên làm (đề xuất, theo ưu tiên)

**Cao (ảnh hưởng trực tiếp "dễ hiểu / dễ tiếp cận"):**
1. **Việt hoá & "phi kỹ thuật hoá" mọi chuỗi có thể tới mắt người bán** — `issue_reason` mặc định, message "đẩy trạng thái lên sàn", `'(TikTokApiException: …)'`, `UnsupportedOperation` có tên biến env. Tạo một hàm map exception → câu thân thiện trước khi lưu `issue_reason`/trả về FE. *(D1, N1, L3)*
2. **`pack`/`handover` bulk trả `errors[{order_id,message}]`** và FE hiện `Modal.warning` như bulk-prepare. *(L1)*
3. **Job render `delivery` thất bại ⇒ set `order.has_issue` + `issue_reason`** để đơn nhảy sang tab "Nhận phiếu giao hàng" thay vì kẹt "Đang tải lại"; thêm dòng giải thích + (tuỳ chọn) auto-poll cho tab "Đang tải lại". *(L2, D3, N3)*

**Trung bình:**
4. Gộp "In tem sàn" / "In phiếu giao hàng" thành **một nút**, hệ thống tự chọn nguồn, tooltip nói rõ "tem thật của sàn" hay "phiếu tự tạo". *(D2)*
5. Tab "Nhận phiếu giao hàng" (failed) và "Đang tải lại": thêm 1–2 dòng hướng dẫn hành động (bấm gì, khi nào vào app TikTok). *(N4, D3)*
6. Thông báo thành công nêu hệ quả trạng thái ("… — đơn chuyển sang 'Đang xử lý'"). *(N5)*
7. "Hết hàng" — liệt kê SKU âm tồn + số thiếu trong tooltip/chi tiết đơn. *(D5)*

**Thấp / dọn dẹp:**
8. Cập nhật SPEC 0013 (hoặc thêm SPEC 0014) ghi nhận **luồng A TikTok đã Implemented**; rà `docs/05-api/endpoints.md` & `docs/04-channels/order-processing.md`. *(§4)*
9. Xoá / `@deprecated` code chết: `useProcessingBoard`/`useProcessingCounts`/`useReadyOrders`/`STAGE_LABEL` FE + `ShipmentController::processing/ready/processingCounts` BE nếu không dùng. *(L6)*
10. Sửa chuỗi vặt: `cancelText:'Để xem lại'` → `'Huỷ'`; bỏ ngoặc kép lồng trong `'Đơn chưa "Chuẩn bị hàng" (…)'`; empty state tab "Vận đơn" thêm hướng dẫn. *(D7, N2, D6)*
11. `getShippingDocument` log kèm `$resp->status()` khi tải `doc_url` lỗi; thống nhất hằng số cắt `issue_reason`. *(L4, L5)*
12. Thêm `aria-label`/`title` cho các nút icon-only chính; kèm icon cho mọi trạng thái phân biệt bằng màu. *(§5)*

---

## 7. Tổng kết

| Tiêu chí | Điểm | Nhận xét |
|---|---|---|
| Logic nghiệp vụ | 8.5/10 | Đúng, an toàn, idempotent; trừ điểm ở bulk pack/handover nuốt lỗi và phiếu tự tạo lỗi không gắn cờ. |
| Hiển thị (tab/nút/trạng thái) | 7.5/10 | Cấu trúc tốt, progressive disclosure đúng; trừ điểm ở thuật ngữ kỹ thuật, "tem vs phiếu", trạng thái "đang tải lại" mù mờ. |
| Thông báo / lỗi | 7/10 | Phân tầng đúng, 403/409/404 rõ; trừ điểm ở exception thô lọt UI, thiếu phản hồi tiến trình nền, vài chuỗi format xấu. |
| Khả năng tiếp cận | 7/10 | Việt hoá tốt; trừ điểm ở phụ thuộc màu, tooltip-only, nút icon-only thiếu nhãn. |

**Hành động ưu tiên #1:** rà toàn bộ chuỗi hiển thị/`issue_reason`/lỗi API và viết lại bằng ngôn ngữ người bán (không có "luồng A", "fulfillment", tên class, tên biến env), kèm hướng dẫn hành động cụ thể. Đây chính là phần "dễ hiểu, dễ tiếp cận với người dùng" mà rà soát này nhắm tới.
