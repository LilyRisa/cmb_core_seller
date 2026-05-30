# Cẩm nang sử dụng (User Manual) — Luồng thao tác từng bước

> Mô tả step-by-step các nghiệp vụ chính theo nhãn nút/màn hình tiếng Việt thực tế.

---

## 1. Đăng ký + Xác thực email + Onboarding

1. Vào **`/register`**, điền Họ tên / Email / Mật khẩu → bấm **"Đăng ký"**. Hệ thống tạo tài khoản + workspace (bạn là **Chủ sở hữu**), gửi email xác thực, và bắt đầu **gói Dùng thử 14 ngày**.
2. Bạn đã đăng nhập nhưng **chưa xác thực email** → màn hình hướng dẫn 4 bước hiện ra (mở hộp thư → tìm email → **kiểm tra Spam/Quảng cáo** → bấm link). Mọi tính năng bị chặn cho tới khi xác thực.
3. Mở email, bấm **"Xác thực email"** → chuyển về `/email-verified?status=success` → hệ thống tải lại tài khoản → vào dùng được.
4. Chưa nhận được email? Bấm **"Gửi lại email"** (chờ 60 giây giữa 2 lần).

---

## 2. Kết nối gian hàng sàn (TikTok / Lazada)

1. Vào **"Gian hàng"** (`/channels`) → bấm **"Kết nối [TikTok/Lazada]"**. (Nếu đã đủ hạn số gian hàng của gói → báo `402 PLAN_LIMIT_REACHED`, cần nâng gói.)
2. Hệ thống mở trang ủy quyền của sàn → đăng nhập shop & **cấp quyền**.
3. Sàn chuyển hướng về hệ thống → tạo gian hàng (lưu token mã hoá), đăng ký webhook, và **kéo 90 ngày đơn gần nhất** vào hệ thống.
4. Quay lại Gian hàng thấy **"Kết nối thành công"**. Với **Lazada**: nếu báo lỗi IP, mở modal **"IP máy chủ"** để copy IP và whitelist trong cổng Lazada; thẻ shop Lazada còn có công tắc **"Tự động RTS sau khi in"**.
5. **Ngắt kết nối**: bấm xoá gian hàng → gõ **đúng tên shop** để xác nhận. Lưu ý: thao tác này xoá toàn bộ đơn của shop + nhả tồn đã giữ + bỏ mọi liên kết SKU.

---

## 3. Đồng bộ & xử lý đơn (Sync → Chuẩn bị → In → Đóng gói → Bàn giao)

1. **Đồng bộ (tự động)**: đơn mới về qua webhook + polling dự phòng. Đơn đã thanh toán rơi vào tab **"Chờ xử lý"**. Có thể bấm **"Đồng bộ đơn"** để kéo thủ công.
2. **Chuẩn bị hàng**: ở `/orders` tab "Chờ xử lý", chọn đơn → **"Chuẩn bị hàng"** (đơn lẻ hoặc hàng loạt) → hệ thống gọi sàn tạo vận đơn + lấy tem (PDF lưu trên hệ thống). Đơn sang **"Đang xử lý"**.
   - *Bị chặn nếu có SKU âm kho.*
3. **In tem / phiếu**: bấm **"In phiếu giao hàng"** → job gộp PDF (sắp theo ĐVVC → đơn) → tải về. Có **Phiếu lấy hàng** (gom theo SKU) và **Phiếu đóng gói** (mỗi đơn 1 phiếu). In lại từ lần 2 có popup xác nhận; file giữ ~90 ngày.
4. **Đóng gói / Quét**: màn đóng gói → **quét barcode vận đơn** → hệ thống tìm đúng đơn (trong tenant, chống quét trùng) → (tuỳ chọn) quét từng SKU để xác nhận → đánh dấu **đã gói**, **trừ tồn**.
5. **Bàn giao**: đơn ở **"Chờ bàn giao"** gom thành **Lô lấy hàng** theo shop/ĐVVC → in phiếu bàn giao cho shipper → bấm bàn giao hàng loạt. (Lazada bật auto-RTS thì tự chuyển sang "Chờ bàn giao" ngay sau khi in.)

---

## 4. Tạo đơn thủ công (POS) + In phiếu

1. Vào **"Tạo đơn"** (`/orders/new`): chọn nguồn phụ (Thủ công/Website/Facebook/Zalo/Hotline), nhập khách (tên, SĐT, địa chỉ tỉnh/huyện/xã).
2. Mục **"Hàng hoá"** → bấm **"Tìm & thêm sản phẩm"** → tìm SKU gốc (ảnh · tên · mã · tồn khả dụng · giá tham chiếu), hoặc **"Tạo sản phẩm nhanh"** (hàng không có SKU — chỉ tên/ảnh/giá/SL, không theo dõi tồn).
3. Chỉnh số lượng / đơn giá / giảm giá từng dòng → bấm **"Lưu đơn"** → tạo đơn `manual`, mặc định **"Chờ xử lý"** (hoặc "Đang xử lý"), **tự đặt giữ tồn** cho dòng có SKU. Có cảnh báo nếu trùng SĐT+SKU gần đây.
4. **Giao + In**: dùng ĐVVC riêng (GHN…) tạo vận đơn + tem, hoặc tự giao; in phiếu như đơn sàn.

---

## 5. Quản lý tồn kho + Ghép SKU + Đẩy tồn

1. Vào **"Tồn kho"** (`/inventory`): xem `on_hand`/`reserved`/`available` theo kho, lịch sử biến động; cảnh báo sắp hết/hết/âm kho, hàng bán chậm, listing chưa ghép.
2. **Ghép SKU**: tab **"Liên kết SKU"** → listing **"Chưa ghép SKU"** → **"Tự khớp"** (gợi ý `single ×1` khi `seller_sku` = `sku_code`) hoặc tạo **combo** (1 listing → nhiều SKU × số lượng). Listing chưa ghép **không đẩy tồn** và làm đơn bị "có vấn đề".
3. **Đẩy tồn**: mọi thay đổi tồn tự tính `available = max(0, on_hand − reserved − safety_stock)` rồi đẩy lên từng listing (single = `floor(available/qty)`; combo = min thành phần). Có thể **"ghim tồn"** từng listing (không tự đẩy).

---

## 6. Nhập hàng (PO → Nhận hàng → Kho/Giá vốn)

1. Tạo **Đơn mua (PO)** với một NCC (gói Pro+). Quản lý NCC + bảng giá nhập ở `/procurement/suppliers`.
2. Khi hàng về, NV kho ghi **Nhận hàng (Goods Receipt)** từ PO → xác nhận.
3. Xác nhận → `on_hand += SL` và tạo **lớp giá vốn** (`cost_layers`). Khi đơn ship sau này, hệ thống tiêu thụ lớp **FIFO** và ghi giá vốn (COGS) bất biến vào `order_costs`. Thiếu lớp FIFO ⇒ dùng giá vốn tổng hợp (gắn cờ).
4. (Nếu bật kế toán) tự định khoản **Nợ 156 / Có 331**.

---

## 7. Đối soát sàn + Xem lợi nhuận

1. (Gói `finance_settlements`.) Vào **"Đối soát sàn"** (`/finance/settlements`) → **kéo sao kê** từ sàn → `settlements` (kỳ + tổng tiền) + `settlement_lines` (phí theo đơn).
2. **Lợi nhuận đơn** = doanh thu − COGS (FIFO) − Σ phí đối soát − ship thực − giảm giá − khác. Gộp vào `profit_snapshots`.
3. **Đối soát**: khớp dòng sao kê ↔ đơn theo `external_order_id`; settlement → **reconciled** khi mọi dòng khớp.
4. **Báo cáo** (`/reports`, gói `profit_reports`): doanh thu/lợi nhuận theo thời gian, theo shop/SKU, top sản phẩm; export Excel/CSV.

---

## 8. Hộp thư + Auto-reply + AI

1. (Gói `messaging_inbox` Pro+.) Vào **`/messaging`** → 3 cột: trái = danh sách hội thoại; giữa = luồng tin + ô soạn; phải = panel khách + đơn liên kết. Bình luận Facebook có **Post Card** đầu luồng.
2. **Soạn**: gõ text + đính kèm ảnh/video/file; gõ `/` để chèn **mẫu tin**; bấm **"AI gợi ý"** để AI viết nháp; Gửi.
3. **Auto-reply**: owner vào `/messaging/auto-rules` → **"Thêm quy tắc"** chọn 1 trong 4 trigger: **lịch** (vd 22:00–08:00), **theo trạng thái đơn** (vd đã giao → cảm ơn), **không phản hồi sau N phút**, **tin đầu tiên** (lời chào).
4. **AI**: super-admin thêm provider ở `/admin/ai-providers`; tenant chọn provider ở `/settings/messaging` (bật AI, auto-mode opt-in, tách riêng sàn vs Facebook). Owner tải tài liệu FAQ/policy ở `/messaging/knowledge` (RAG) để AI trả lời sát.
5. **Nhắn riêng bình luận Facebook**: bấm ✉️ trên comment khách → modal nhắn riêng đầy đủ (text + ảnh/video/file + mẫu tin). Lưu ý Facebook chỉ cho nhắn riêng **1 lần/comment**; tin tiếp theo gửi qua hội thoại tin nhắn khi khách trả lời.

---

## 9. Nâng cấp gói + Thanh toán

1. (Owner, `billing.manage`.) Vào **"Gói & nâng cấp"** (`/settings/plan`) → thấy 4 gói + **"Chọn gói này"**.
2. Bấm vd **"Chọn Pro"** → modal nâng cấp: chọn **chu kỳ** (Tháng/Năm) + **cổng** (SePay/VNPay) → xác nhận.
3. Hệ thống tạo **hoá đơn** `INV-YYYYMM-NNNN` (hạn +7 ngày) và mở cổng:
   - **SePay**: hiện VietQR + thông tin chuyển khoản + nội dung (memo) + nút **"Tôi đã chuyển"**; tự poll trạng thái mỗi 5 giây.
   - **VNPay**: chuyển hướng sang trang VNPay.
4. Cổng báo về (webhook) → đối soát (dedupe, khớp hoá đơn theo `reference`) → payment thành công → kích hoạt gói; gửi email cảm ơn.
5. Hết hạn → **7 ngày grace** → nếu vẫn chưa trả thì rơi về **trial miễn phí vĩnh viễn** (dữ liệu không bị khoá).

---

## 10. Kế toán (Khởi tạo → Sổ nhật ký → Khoá kỳ)

1. **Khởi tạo** (gói `accounting_basic`): lần đầu vào `/accounting` thấy banner **"Khởi tạo hệ thống tài khoản theo TT133"** → bấm **"Khởi tạo"** → tạo hệ thống TK + các kỳ + quy tắc định khoản mặc định.
2. **Sổ nhật ký** (`/accounting/journals`): nghiệp vụ tự sinh bút toán kép (idempotent): nhận hàng → Nợ 156/Có 331; chuyển kho → Nợ 156(đến)/Có 156(đi); kiểm kê lệch → 156↔711/811. Sửa map ở `/settings/accounting/post-rules`. Có thể tạo bút toán tay (cân Nợ/Có).
3. **Khoá/Đóng kỳ** (`/accounting/periods`, `accounting.close_period`): cuối tháng bấm **"Đóng kỳ"** → chốt số dư (cuối kỳ = đầu kỳ sau). Ghi vào kỳ đã đóng ⇒ `422 PERIOD_CLOSED`; đảo bút toán kỳ đóng nhảy sang kỳ mở kế. Kỳ **locked** không mở lại được.

---

## 11. Quản lý nhân viên & vai trò

1. Owner/Admin vào **"Nhân viên & vai trò"** (`/settings/members`).
2. **Thêm thành viên**: nhập email + chọn vai trò (Chủ sở hữu / Quản trị / NV xử lý đơn / NV kho / NV chăm sóc khách / Kế toán / Chỉ xem).
3. Mỗi vai trò có bộ quyền cố định (xem [business-rules.md](business-rules.md#) và [agent_context.md](agent_context.md)).

---

## 12. (Super-admin) Vận hành SaaS

1. Đăng nhập `/admin/login` (username/password).
2. **Tenants** (`/admin/tenants`): xem/quản lý nhà bán, đổi gói (bỏ qua chặn hạ gói), suspend/reactivate, gia hạn trial, feature override.
3. **Gói** (`/admin/plans`): tạo/sửa gói + cờ tính năng. **Voucher** (`/admin/vouchers`): tạo/cấp voucher. **Broadcast**: gửi email hàng loạt.
4. **Hệ thống** (`/admin/settings`): chỉnh `system_settings` (thương hiệu/email/marketplace/vận hành/đồng bộ/AI), "Nạp từ env".
5. **Nhà cung cấp AI** (`/admin/ai-providers`): thêm provider (Anthropic/OpenAI-compatible/custom HTTP/manual), test kết nối.

> Tham khảo thêm: [frontend-guide.md](frontend-guide.md) (chi tiết màn hình) · [faq.md](faq.md) · [troubleshooting.md](troubleshooting.md).
