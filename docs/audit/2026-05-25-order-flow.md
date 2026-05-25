# Đánh giá luồng xử lý & đồng bộ đơn hàng — 2026-05-25

**Phạm vi:** Luồng đơn hàng (đồng bộ sàn → xử lý → giao hàng) + đánh giá UI (phần thừa, phần chưa đáp ứng yêu cầu, nội dung/thao tác không nhất quán).
**Phương pháp:** Trình duyệt thật trên `https://app.cmbcore.com` (tài khoản seed `owner@demo.local`, tenant `Cửa hàng demo` id=1) · DB prod `cmbcoreseller` qua SSH `192.168.1.111` (**chỉ đọc**) · đối chiếu `docs/03-domain/*` + code `app/`.
**Dữ liệu nền:** 406 đơn (Lazada 365, TikTok 39, manual 2); 463/463 dòng `order_items` chưa map SKU; `sync_runs`: poll done 2584 / **failed 164**, unprocessed done 862 / **failed 68** / **running 1 (kẹt)**.

---

## Tóm tắt luồng (để tham chiếu)

- **Đồng bộ:** webhook (`/webhook/{provider}` → `webhook_events` → `ProcessWebhookEvent`) **+** polling (`SyncOrdersForShop` 3 mode: `poll` 10', `unprocessed` 30', `backfill` 1 lần). Cả hai luôn `fetchOrderDetail` rồi `OrderUpsertService` (idempotent theo `source+channel_account_id+external_order_id`, chặn ghi đè đến trễ theo `source_updated_at`).
- **State machine** (`StandardOrderStatus`): `unpaid → pending → processing → ready_to_ship → shipped → delivered → completed` (+ `delivery_failed`, `returning`, `returned_refunded`, `cancelled`). `ready_to_ship` **chỉ đạt bằng thao tác nội bộ** (`markPacked`), không từ raw status sàn.
- **Xử lý:** "Chuẩn bị hàng (lấy phiếu)" (`POST /orders/{id}/ship`) → "Đã gói & sẵn sàng bàn giao" (`/shipments/pack`) → "Bàn giao ĐVVC" (`/shipments/handover`, trừ tồn ở bước này). Toàn bộ là tab trong `/orders`; không còn màn `/fulfillment` riêng (SPEC 0009).

---

## A. PHẦN THỪA / CODE CHẾT trong app web

| # | Phát hiện | Bằng chứng |
|---|---|---|
| A1 | **3 hook + 3 endpoint "bảng xử lý đơn" đã chết.** `useProcessingBoard`/`useProcessingCounts`/`useReadyOrders` (gọi `GET /fulfillment/processing`, `/processing/counts`, `/fulfillment/ready`) được export nhưng **không nơi nào import**. Đúng phần SPEC 0009 nói web FE không dùng nữa. | `lib/fulfillment.tsx:88-133`; `ShipmentController.php:30` (`/fulfillment/ready` chỉ là alias cũ) |
| A2 | **Logic "không in chung nhiều nền tảng/ĐVVC" + "đã in, in tiếp?" bị nhân đôi** ở 2 nơi, dễ lệch nhau khi sửa. | `OrdersPage.tsx:296-363` và `OrderProcessing.tsx:362-409` |
| A3 | **Bộ lọc `stage` chết.** `OrderFilters.stage` + `STAGE_LABEL` khai báo nhưng không màn nào gửi/`dùng` (đã chuyển sang tab theo status). | `lib/orders.tsx:113`, `lib/fulfillment.tsx:71` |
| A4 | **Chuông thông báo là placeholder** ("Thông báo (sắp có)"). | `AppLayout.tsx:129` |

> *Không phải phần thừa (đã kiểm):* tab **"Trả/hoàn"** (lọc đơn `status ∈ {returning, returned_refunded}`) **khác** trang **"Hoàn & Hủy"** `/returns` (danh sách bản ghi `order_returns`, là **nơi duy nhất** duyệt/từ chối — SPEC 0025). Hai phạm vi khác nhau, không trùng.

---

## B. PHẦN CHƯA ĐÁP ỨNG YÊU CẦU

| # | Phát hiện | Bằng chứng |
|---|---|---|
| B1 | **Deep-link "Giao hàng & in" HỎNG.** Chi tiết đơn có link → `/fulfillment` → redirect `/orders?tab=prepare`, nhưng `prepare` **không phải** key tab nào (`ORDER_STATUS_TABS` chỉ có ''/pending/processing/ready_to_ship/...). Kết quả: rơi vào danh sách **không tab nào được chọn** (đã xác minh: `selectedTab=null`). | `app.tsx:108`, `lib/format.ts:64-74`, `OrderDetailBody.tsx:150` |
| B2 | **`resync-unprocessed` không có nút nào trên FE.** `docs/03-domain/order-sync-pipeline.md §3.3` yêu cầu "endpoint cho user manual"; route + controller có (`ChannelAccountController.php:181`) nhưng **không có lời gọi nào** trong `resources/js`. Người dùng không thể kéo lại các đơn tồn đọng. | grep `resources/js` |
| B3 | **Lỗi đồng bộ lặp không được cảnh báo chủ động.** Lazada lỗi liên tục ~5h (xem D1) nhưng trang Đơn hàng **không** báo; chỉ thấy nếu mở "Nhật ký đồng bộ". App đã có sẵn endpoint `GET /channel-accounts/outbound-ip` để khắc phục nhưng không dẫn người dùng tới. | banner Orders chỉ hiện 1 shop; `channel_accounts.status` Lazada vẫn `active`, `last_synced_at` đứng ở 09:35 |
| B4 | **In phiếu soạn hàng / đóng gói chỉ có ở backend.** `type: picking|packing` hợp lệ ở BE và có nhãn `PRINT_TYPE_LABEL`, nhưng **không nút UI** nào kích hoạt (chỉ label/delivery/invoice được nối). | `OrderProcessing.tsx` OrderActions; `PrintJobBar` |
| B5 | **Quét đóng gói thiếu bước xác nhận từng SKU.** Domain doc §5.2 nói "quét tiếp barcode từng SKU"; ScanTab chỉ quét mã đơn/kiện. | `OrderProcessing.tsx:258-298` |
| B6 | **Chưa có lưu trữ/purge chứng từ in 90 ngày** (`print_jobs.expires_at`, `order_print_documents`, `PrunePrintDocuments`) — SPEC 0009/0013 để "follow-up", chưa làm. | thiếu migration/job |

---

## C. NỘI DUNG / THAO TÁC KHÔNG NHẤT QUÁN

| # | Phát hiện | Bằng chứng |
|---|---|---|
| C1 | **Một hành động "lấy phiếu" mang 3 tên khác nhau:** danh sách (đơn chờ xử lý) "**Chuẩn bị hàng (lấy phiếu)**", danh sách (đã xử lý) "**Lấy phiếu giao hàng**", chi tiết "**Giao hàng & in**". Cùng gọi `POST /orders/{id}/ship`. | `OrderProcessing.tsx:105-254`, `OrderDetailBody.tsx:150` |
| C2 | **Bộ lọc "Vận chuyển" phân mảnh: ~18 chip cho ~5 hãng.** Tên ĐVVC để nguyên chuỗi thô từ sàn, không chuẩn hoá. VD J&T xuất hiện thành: `J&T VN` (27), `Drop-off: J&T VN, Delivery: J&T VN` (6), `Pickup: J&T VN, Delivery: J&T VN` (3), `TT Virtual# JNT express` (24), `Pickup: J&T VN, Delivery: LEX VN` (2); BEST/LEX/GHN/AhaMove tương tự; còn chip rác `lazada` (5). | snapshot Orders, cột `ĐVVC` + filter `by_carrier` |
| C3 | **Loại sync `unprocessed` hiển thị thô & không lọc được** trong "Nhật ký đồng bộ": filter chỉ whitelist `[poll, backfill, webhook]`, dropdown chỉ có "Định kỳ/Lấy lại lịch sử/Webhook", `SYNC_RUN_TYPE_LABEL` thiếu key → render chuỗi "unprocessed". (DB có **862** run loại này.) | `SyncLogController.php:38`, `SyncLogsPage.tsx:125-127`, `lib/syncLogs.tsx:120-122` |
| C4 | **"SKU chưa ghép" bị xử lý ở 3 mức nghiêm trọng mâu thuẫn nhau:** banner nói "vẫn in & bàn giao bình thường" (không chặn), nhưng cùng điều kiện đó (a) **bôi đỏ mọi dòng**, (b) set `has_issue=true`, (c) đếm vào tab **"Có vấn đề" (99+)**. Với 387/406 đơn → tab "Có vấn đề" và màu đỏ thành nhiễu, mất ý nghĩa cảnh báo. | DB `has_issue=402`, `issue_reason='SKU chưa ghép'=387`; banner Orders e505 vs cột trạng thái |
| C5 | **Hai khối vận đơn chồng nhau trên trang chi tiết:** "Vận đơn" báo *"Chưa có vận đơn. Tạo & in tem ở Giao hàng & in"* trong khi "Kiện hàng / Vận đơn" báo *"(chưa có mã vận đơn) J&T VN pending"* — cùng một kiện, hai hộp, thông tin trùng/khó hiểu. | `OrderDetailBody.tsx`; snapshot order 393 |
| C6 | **Trạng thái shipment hiện mã thô** ở chi tiết (`<Tag>{order.shipment.status}</Tag>`) dù `OrderResource` đã trả `status_label`; nơi khác dùng `SHIPMENT_STATUS_LABEL`. | `OrderDetailBody.tsx:145` |
| C7 | **Lợi nhuận "LN" hiển thị cả ở đơn đã huỷ** và bằng đúng doanh thu khi chưa có giá vốn (vd đơn huỷ "LN: 7.599.000 ₫"). Chi tiết có chú thích "ước tính", danh sách thì không. | snapshot Orders (đơn `525531625280318` huỷ) |
| C8 | **Tab "Đang giao" gộp `delivery_failed` chung `shipped`** — đơn "Giao thất bại" nằm trong tab "Đang giao", lệch với nhãn trạng thái. | `lib/format.ts:69` |
| C9 | **Nhãn auto-RTS lệch doc:** doc gọi "Tự động RTS sau khi in"; UI là "Tự động gửi đơn cho ĐVVC sau khi in". `AfterSalesStatus` enum cũng lệch SPEC 0025 (`refunded→completed`, bỏ `closed`). | `ChannelsPage.tsx:108`; `AfterSalesStatus.php:13-23` |

---

## D. BUG LUỒNG ĐỒNG BỘ / XỬ LÝ — ĐANG CHẠY PRODUCTION (ưu tiên cao)

| # | Phát hiện | Bằng chứng |
|---|---|---|
| D1 | **Lazada ngừng đồng bộ ~5 giờ** do IP server không nằm trong whitelist app Lazada. Mọi `poll`/`unprocessed` của shop TekoTest fail: `[AppWhiteIpLimit] The binding IP whitelist of the app does not contain the source IP`. Tích luỹ 164 poll + 68 unprocessed failed; `last_synced_at` đứng ở 2026-05-25 09:35. *(Vấn đề vận hành/cấu hình, không phải bug code — nhưng app không bề mặt hoá nó, xem B3.)* | `sync_runs` id 3709-3715 |
| D2 | **Run "zombie" kẹt từ 2026-05-23.** `sync_runs` id 2975 (TikTok acct 2, `unprocessed`) ở trạng thái `running`, stats 0/0/0, không `finished_at` (job chết giữa chừng). Hệ quả: banner trang Đơn hàng **luôn** hiện *"Đang đồng bộ đơn từ 1 gian hàng — Đã nhận: 0, Mới: 0"* suốt 2 ngày → làm sai lệch quan sát (không chặn job mới vì `ShouldBeUnique` chỉ giữ 900s). | DB `status='running'`; banner Orders e246 |
| D3 | **Webhook TikTok im từ 2026-05-19** (`last_webhook_at` 6 ngày trước); toàn hệ thống chỉ 54 webhook_events. Đồng bộ thực tế gần như hoàn toàn nhờ polling (`order_status_history.source`: polling 452 vs webhook 25). Không mất đơn nhưng nên cảnh báo "ngưng nhận webhook" như doc §5. | `channel_accounts.last_webhook_at`; `webhook_events` |
| D4 | **Container `cmb_seller-web-1` đang `unhealthy`** (các container khác healthy). Cần soi healthcheck nginx (ngoài phạm vi đánh giá này). | `docker ps` |

---

## Khuyến nghị ưu tiên

1. **Khắc phục ngay (prod):** thêm IP server vào whitelist Lazada (dùng `/channel-accounts/outbound-ip`); dọn run zombie id 2975 + thêm "reaper" đánh dấu `failed` các run `running` quá hạn; xem healthcheck `web-1`.
2. **Bề mặt hoá lỗi đồng bộ (B3, D1-D3):** banner/cảnh báo trên trang Đơn hàng khi shop có chuỗi sync failed hoặc ngưng webhook, kèm link sửa.
3. **Giảm nhiễu "SKU chưa ghép" (C4):** tách "chưa ghép SKU" khỏi cờ `has_issue`/tab "Có vấn đề" (đưa thành chỉ báo riêng, không bôi đỏ toàn bộ) — đúng tinh thần banner "không chặn".
4. **Chuẩn hoá tên ĐVVC (C2)** về danh mục hãng để bộ lọc dùng được.
5. **Thống nhất nhãn thao tác (C1)** + sửa deep-link `prepare` (B1) + nối nút `resync-unprocessed` (B2).
6. **Dọn code chết (A1-A4)** để giảm rủi ro bảo trì.
