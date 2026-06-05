# SPEC 0030: Link tra cứu đơn công khai (đơn tự tạo)

- **Trạng thái:** Reviewed
- **Phase:** 3 (Fulfillment) — mở rộng
- **Module backend liên quan:** Orders (chính), đọc mềm Fulfillment (Shipment/ShipmentEvent)
- **Tác giả / Ngày:** Claude · 2026-06-05
- **Liên quan:** spec 0006 (Fulfillment), 0021 (Manual orders + GHN), `03-domain/order-status-state-machine.md`, `08-security-and-privacy.md`

## 1. Vấn đề & mục tiêu
Seller tạo **đơn tự tạo** (`source='manual'`) và muốn gửi cho khách **một link công khai** để khách tự theo dõi hành trình giao hàng — **không cần đăng nhập, không cần nhập mã vận đơn**. Link dạng **`/tracking?code={mã_đơn}`** (mã đơn = `order_number`). Khách thấy:
- Tiến trình đơn (graph hành trình) theo phía ĐVVC khi đơn đã đẩy GHN/GHTK; hoặc theo mốc trạng thái nội bộ khi seller tự vận chuyển.
- Thông tin người nhận đã **ẩn bớt** (SĐT, địa chỉ chi tiết) để bảo vệ PII khi link bị chia sẻ rộng.
- Số tiền **COD phải trả** (nếu có), **không lộ** các chi phí khác.

## 2. Trong / ngoài phạm vi
- **Trong:** Link công khai `/tracking?code=` cho đơn `source='manual'`; trang React public responsive có logo; endpoint public không-auth có throttle; service dựng payload đã mask; nút "Link tra cứu" (copy URL) ở chi tiết đơn.
- **Ngoài:** Đơn sàn (TikTok/Shopee/Lazada) — không hỗ trợ link công khai. Tra cứu bằng nhập mã vận đơn ĐVVC. Thông báo SMS/email cho khách. Trang public không gọi trực tiếp API ĐVVC lúc render (dùng dữ liệu đã đồng bộ sẵn).

## 3. Luồng chính
1. Seller mở chi tiết một **đơn tự tạo** → bấm **"Link tra cứu"** → FE copy `https://<app>/tracking?code={order_number}` vào clipboard (thuần FE, không gọi BE).
2. Seller gửi link cho khách.
3. Khách mở link → SPA render `PublicTrackingPage`, đọc query `code` → gọi `GET /api/v1/public/track?code={code}`.
4. BE: tìm đơn `source='manual'`, chưa xoá, `order_number = code` (bỏ qua global scope tenant; nếu khớp >1 ⇒ coi như không thấy) → dựng payload đã mask → trả về.
5. Trang hiển thị: header có logo, **Steps** tiến trình lớn, **Timeline** chi tiết, card người nhận (đã mask), tiền COD phải trả, danh sách sản phẩm (tên + SL, không giá).

## 4. Hành vi & quy tắc nghiệp vụ
- **Chỉ đơn `source='manual'`** mới tra được; đơn sàn/không tồn tại ⇒ 404 chung.
- **Code = `order_number`** (vd `M260605-A7K2Q`): có thành phần ngẫu nhiên 5 ký tự ⇒ khó đoán; throttle chống dò; luôn mask PII (xem §8).
- Tra cứu **không tenant context**: query `Order::withoutGlobalScopes()->where('source','manual')->where('order_number',$code)`. Khớp đúng 1 ⇒ hiển thị; 0 hoặc >1 (trùng hiếm giữa tenant) ⇒ 404.
- **Nguồn hành trình** (ưu tiên, không gọi API ĐVVC realtime):
  - Có `Shipment` carrier thật (`carrier ∉ {'', 'manual'}`, status ∉ {pending,cancelled}) ⇒ dùng `ShipmentEvent` (scan ĐVVC đã đồng bộ qua webhook/polling — spec 0006/0021). Đây là "hành trình thực tế theo phía ĐVVC".
  - Ngược lại (tự vận chuyển) ⇒ dùng `OrderStatusHistory` (các mốc đổi trạng thái), nhãn tiếng Việt.
- **Steps** suy ra từ `StandardOrderStatus` của đơn: Chờ xử lý → Đang giao → Đã giao; nhánh lỗi: Giao thất bại / Đang hoàn / Đã hoàn / Đã huỷ.
- **Không tự fetch** ĐVVC ở endpoint public; tận dụng job đồng bộ sẵn có (`SyncShipmentTracking`) + webhook. Trang chỉ đọc DB.
- **Phân quyền:** đọc là public (code là "khoá"); nút copy link chỉ hiện cho đơn manual trong app đã đăng nhập.

## 5. Dữ liệu
- **Không bảng/cột mới.** Tra theo `orders.order_number` (đã có, index theo `search`; thêm đọc `withoutGlobalScopes`). Không phát domain event mới.
- Đọc mềm Fulfillment: `order->shipments->first()->events` (ShipmentEvent) và `order->statusHistory` — theo precedent OrderResource (soft cross-module reference).

## 6. API & UI
- **`GET /api/v1/public/track?code={code}`** (public, throttle `30,1`): trả payload đã mask. Lỗi 404 `NOT_FOUND` (không lộ lý do). Response (envelope `{data}`):
  ```jsonc
  { "data": {
    "order_number": "M260605-A7K2Q",
    "status": "shipped", "status_label": "Đang giao",
    "placed_at": "...", "delivered_at": null,
    "carrier_name": "GHN" | null,
    "cod": { "amount": 150000, "is_cod": true },     // amount=0 + is_cod=false ⇒ "Không thu hộ"
    "recipient": { "name": "Nguyễn Văn ***", "phone": "035****89",
                   "area": "Phường X, Quận Y, TP Z" },   // KHÔNG có số nhà/đường
    "items": [ { "name": "Áo thun", "qty": 2 } ],          // KHÔNG có giá
    "steps": [ { "key": "processing", "label": "Chờ xử lý", "state": "done|process|wait|error" }, ... ],
    "timeline": [ { "at": "...", "label": "Đã lấy hàng", "source": "carrier" }, ... ]
  } }
  ```
- Route đặt trong **`app/app/Modules/Orders/Http/routes.php`** (file mới; provider tự `loadRoutesFrom`) — **không sửa `routes/api.php`** để tránh xung đột merge. Wrap `Route::middleware('api')->prefix('api/v1')`.
- **FE:** route React public `/tracking` (catch-all SPA tự serve; đọc `?code=`). Trang `PublicTrackingPage` standalone (không sidebar), responsive mobile-first, logo `/images/logocmb.png`, theme `#2563EB`, AntD `Steps` + `Timeline`. Nút "Link tra cứu" (copy) ở khu chi tiết đơn — chỉ hiện với đơn manual.
- Không đụng connector ĐVVC; dùng dữ liệu `ShipmentEvent`/`OrderStatusHistory` đã có.

## 7. Edge case & lỗi
- Code sai / không tồn tại / đơn đã xoá / không phải manual / trùng >1 ⇒ 404 chung, trang hiện "Không tìm thấy đơn" lịch sự.
- Đơn manual **chưa đẩy ĐVVC & chưa có lịch sử** ⇒ Steps theo status hiện tại, timeline tối thiểu (mốc tạo đơn).
- `shipping_address` thiếu tỉnh/huyện/xã ⇒ hiển thị phần có, bỏ phần trống.
- SĐT < 5 ký tự ⇒ mask toàn bộ (theo `maskedBuyerPhone`).
- Thiếu/ý query `code` rỗng ⇒ 404. Throttle vượt ngưỡng ⇒ 429.

## 8. Bảo mật & PII
- Link công khai = chia sẻ rộng ⇒ **luôn mask**: tên (ẩn phần cuối), SĐT (`maskedBuyerPhone`), địa chỉ chỉ còn xã/huyện/tỉnh.
- **Không trả**: giá sản phẩm, item_total, phí ship, giảm giá, grand_total, raw_payload, credential ĐVVC, mã vận đơn (PII suy luận).
- **Tradeoff code-based:** `order_number` đoán-được một phần (date + 5 ký tự random). Chấp nhận theo yêu cầu; giảm thiểu bằng throttle `30,1` + mask toàn bộ PII + chỉ trả dữ liệu hành trình. (Nếu sau cần chặt hơn ⇒ chuyển token bền — đã chừa chỗ ở §11.)
- Endpoint public không nhận `X-Tenant-Id`, không cookie; tenant suy từ đơn tìm được.

## 9. Kiểm thử
- **Unit:** masking địa chỉ (giữ xã/huyện/tỉnh, bỏ chi tiết); chọn nguồn timeline (carrier vs status-history); map Steps theo status.
- **Feature:** `GET public/track?code=` đơn manual ⇒ payload đã mask & **không** chứa trường nhạy cảm; đơn sàn ⇒ 404; code sai ⇒ 404; code trùng >1 ⇒ 404; throttle.
- **FE:** typecheck + build (không có JS test runner — xem memory test-verify-baseline).

## 10. Acceptance
- [ ] Đơn manual: copy được `/tracking?code=...`, mở ẩn danh thấy đúng.
- [ ] SĐT & địa chỉ chi tiết bị ẩn; còn tỉnh/huyện/xã; tiền COD hiển thị, chi phí khác ẩn.
- [ ] Graph hành trình: dùng scan ĐVVC khi đã đẩy GHN/GHTK; fallback lịch sử trạng thái.
- [ ] Trang responsive, có logo, chuyên nghiệp.
- [ ] Đơn sàn / code sai ⇒ trang "không tìm thấy".
- [ ] `docs/05-api/endpoints.md` cập nhật endpoint public.

## 11. Câu hỏi mở
- (Sau) Nếu cần bảo mật chặt hơn: thêm bảng token bền + `/tracking?t={token}` thay cho code. Hiện theo yêu cầu dùng code.
