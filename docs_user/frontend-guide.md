# Hướng dẫn giao diện (Frontend Guide)

> Mô tả từng màn hình theo góc nhìn người dùng: mục đích, đường dẫn, quyền, thành phần giao diện, chức năng và **luồng thao tác**.
> Quy ước: route **không** chặn theo quyền; trang **ẩn nút/cột** khi thiếu quyền (`useCan('<permission>')`). Gating theo gói (plan.feature) chặn ở backend — trang tự suy biến (ví dụ Dashboard ẩn khối kế toán nếu gói chưa bật).

---

## A. Xác thực & onboarding

### Đăng nhập
- **Mục đích**: vào hệ thống.
- **Đường dẫn**: `/login` · **Quyền**: công khai.
- **Thành phần**: form email + mật khẩu, checkbox "ghi nhớ", link sang Đăng ký / Quên mật khẩu.
- **Luồng**: nhập email + mật khẩu → "Đăng nhập" → gọi `POST /auth/login` → thành công chuyển `/`.

### Đăng ký
- **Mục đích**: tạo tài khoản + workspace (tenant) mới; người tạo là `owner`.
- **Đường dẫn**: `/register` · **Quyền**: công khai.
- **Thành phần**: form Họ tên / Email / Mật khẩu.
- **Luồng**: điền form → "Đăng ký" → `POST /auth/register` (tạo User + Tenant, gửi email xác thực, bắt đầu gói dùng thử 14 ngày) → vào `/` nhưng bị chặn cho tới khi xác thực email.

### Xác thực email
- **Mục đích**: bắt buộc xác thực trước khi dùng tính năng.
- **Hiển thị**: không phải route riêng — `RequireAuth` render trang này khi `email_verified_at` rỗng.
- **Thành phần**: hướng dẫn 4 bước (mở hộp thư → tìm email → kiểm tra Spam/Quảng cáo → bấm link), nút "Gửi lại email" (chờ 60s), nút làm mới, đăng xuất.
- **Luồng**: bấm link trong email → `/email-verified?status=success` → tải lại `/auth/me` → vào hệ thống.

### Quên / Đặt lại mật khẩu
- **Đường dẫn**: `/forgot-password`, `/password-reset` · **Quyền**: công khai.
- **Luồng**: nhập email → nhận link → trang reset (đọc `token`+`email` từ URL) → nhập mật khẩu mới (≥8 ký tự, có hoa/thường/ký tự đặc biệt) → về đăng nhập.

---

## B. Bảng điều khiển (Dashboard)

- **Mục đích**: tổng quan sức khoẻ kinh doanh + việc cần làm.
- **Đường dẫn**: `/` · **Quyền**: `dashboard.view`. Chỉ đọc.
- **Thành phần**: bộ chọn khoảng (7/30/90 ngày, lưu vào URL) + nút Làm mới; thẻ KPI (Doanh thu, Số đơn, Lợi nhuận ước tính, GMV TB/đơn) có sparkline + so sánh kỳ trước; biểu đồ cột doanh thu+lợi nhuận; danh sách **"Việc cần làm"** (đơn chờ xử lý/chờ bàn giao/chờ in/chưa ghép SKU/có vấn đề/gian hàng cần reconnect — mỗi mục dẫn vào Đơn hàng đã lọc); Top SKU; doanh thu theo kênh; khối **"Thống kê nhanh kế toán"** (ẩn nếu gói kế toán chưa bật hoặc chưa khởi tạo); cảnh báo khi chưa có gian hàng nào.
- **Luồng**: mở Dashboard → đọc KPI → bấm mục trong "Việc cần làm" → nhảy vào danh sách đơn tương ứng để xử lý.

---

## C. Đơn hàng & xử lý

### Đơn hàng (màn hình trung tâm xử lý)
- **Mục đích**: xem, lọc và xử lý đơn từ mọi nguồn (sàn + thủ công).
- **Đường dẫn**: `/orders` · **Quyền**: `orders.view` (các nút khác cần `orders.create`, `fulfillment.ship/print`, `inventory.map`).
- **Thành phần**:
  - **Tab trạng thái** (Tất cả / Chờ xử lý / Đang xử lý / Chờ bàn giao…) + tab **Có vấn đề** + **Hết hàng** + **Vận đơn** (shipments).
  - **Bộ lọc**: Select chọn trường tìm (mã đơn/người mua, SKU, tên SP) + ô tìm; dải chip lọc tầng Sàn TMĐT → Gian hàng → Vận chuyển, Phiếu in, Thời gian (preset + RangePicker); Select sắp xếp.
  - **Bảng** có chọn nhiều dòng; **thanh hành động hàng loạt**: Chuẩn bị hàng, Sẵn sàng bàn giao, Bàn giao ĐVVC, Nhận phiếu giao hàng, In phiếu giao hàng, Liên kết SKU.
  - **Nút đầu trang**: Tạo đơn, Quét đơn (modal), Đồng bộ đơn (chạy nền + banner tiến độ), Làm mới.
  - **Modal/Drawer**: chọn ĐVVC (đơn thủ công), chọn mẫu tem, thanh in (`PrintJobBar`), tiến độ hàng loạt, liên kết SKU, chi tiết đơn nhanh, quét đơn.
- **Luồng "Chuẩn bị hàng"**: chọn đơn ở tab "Chờ xử lý" → "Chuẩn bị hàng" → hệ thống gọi sàn tạo vận đơn + lấy tem → đơn sang "Đang xử lý". Có cảnh báo khi trộn đơn sàn với đơn thủ công, hoặc khi lợi nhuận âm / in lại.

### Chi tiết đơn
- **Đường dẫn**: `/orders/:id` · **Quyền**: `orders.view`.
- **Thành phần**: header (badge sàn, tag gian hàng, trạng thái, COD); nút "Sửa đơn" **chỉ** với đơn thủ công chưa kết thúc (`orders.update`); thân = chi tiết sản phẩm + lịch sử trạng thái.

### Tạo / Sửa đơn thủ công (kiểu POS)
- **Đường dẫn**: `/orders/new`, `/orders/:id/edit` · **Quyền**: `orders.create` / `orders.update`.
- **Thành phần**: khối sản phẩm có tìm nhanh + tab (Sản phẩm/Combo) + tuỳ chọn còn-hàng; tra cứu SĐT khách (cảnh báo khách quay lại / khách bị chặn); chọn địa chỉ (tỉnh/huyện/xã, autocomplete); nguồn phụ (Website/Facebook/Zalo/Hotline); ô thanh toán; đính kèm; tab ghi chú in; thanh dưới Lưu / Lưu & In. Có nháp lưu localStorage, hỗ trợ nhiều tab.
- **Luồng**: chọn nguồn → nhập khách → "Tìm & thêm sản phẩm" (hoặc "Tạo sản phẩm nhanh" cho hàng không có SKU) → chỉnh SL/giá/giảm → "Lưu đơn" (tự đặt giữ tồn) → mở chi tiết (tuỳ chọn `?print=1`).

### Hoàn & Hủy
- **Mục đích**: duyệt yêu cầu sau bán (hủy/hoàn/refund).
- **Đường dẫn**: `/returns` · **Quyền**: xem = `orders.view`; Duyệt/Từ chối = `orders.update` (Owner/Admin/StaffOrder).
- **Thành phần**: segmented (Chờ duyệt / Đang mở / Tất cả) + lọc loại; bảng (đơn, loại, trạng thái, lý do, hoàn tiền, thời gian); Duyệt/Từ chối qua Popconfirm.

---

## D. Sản phẩm / Tồn kho / Kho

### Tồn kho
- **Đường dẫn**: `/inventory` (`/products` → redirect tới đây) · **Quyền**: `inventory.view`.
- **Thành phần (tab)**:
  - **Tồn theo SKU**: bảng tồn, toggle "Sắp hết ≤5", nút Điều chỉnh từng dòng (`inventory.adjust`).
  - **Danh mục SKU**: danh sách SKU, tìm, Thêm SKU, điều chỉnh/đẩy tồn hàng loạt, xoá (`products.manage`/`inventory.adjust`).
  - **Liên kết SKU (sàn)**: ghép listing sàn ↔ SKU gốc, tự khớp (`inventory.map`).
  - **Phiếu kho**: phiếu nhập/xuất/điều chuyển/kiểm kê (WMS).
- **Luồng ghép SKU**: tab "Liên kết SKU" → listing "Chưa ghép" → "Tự khớp" (gợi ý single×1 khi mã trùng) hoặc tạo combo (1 listing → nhiều SKU) → lưu → tồn được đẩy tự động.

### Thêm / Sửa SKU
- **Đường dẫn**: `/inventory/skus/new`, `/inventory/skus/:id/edit` · **Quyền**: `products.manage`.
- **Thành phần**: upload ảnh, thông tin cơ bản (mã/SPU/tên/đơn vị), phương pháp giá vốn (bình quân/FIFO), giá vốn, trạng thái hoạt động.

---

## E. Khách hàng

### Danh sách khách hàng
- **Đường dẫn**: `/customers` · **Quyền**: `customers.view`.
- **Thành phần**: tab theo uy tín + tìm + sắp xếp; bảng (tên/SĐT che, badge uy tín, nhãn, đơn hoàn tất/hủy, doanh thu, gần nhất). Dòng dẫn sang chi tiết.

### Chi tiết khách hàng
- **Đường dẫn**: `/customers/:id` · **Quyền**: `customers.view` (ghi chú `customers.note`, chặn `customers.block`).
- **Thành phần**: thông tin + thống kê vòng đời, badge uy tín, lịch sử đơn (dẫn sang đơn), dòng thời gian ghi chú + thêm ghi chú (mức info/warning/danger), nút chặn khách.

---

## F. Tin nhắn (Hộp thư hợp nhất)

### Hộp thư
- **Mục đích**: hội thoại DM + bình luận từ mọi nền tảng trong một nơi.
- **Đường dẫn**: `/messaging` · **Quyền**: `messaging.view` (trả lời `messaging.reply`). Cần gói có `messaging_inbox` (Pro+).
- **Thành phần (3 cột)**: trái = danh sách hội thoại (lọc theo sàn/loại/đọc–chưa đọc/thẻ); giữa = luồng tin + ô soạn (text + ảnh/video/file, mẫu tin `/slash`, emoji, **AI gợi ý**), với bình luận Facebook có post card đầu luồng + nút trên từng comment (Thích/Nhắn riêng/Xoá) + modal nhắn riêng đầy đủ; phải = panel khách + đơn liên kết. Có đánh dấu đọc/chưa đọc, chặn/bỏ chặn, quản lý thẻ, push notification.
- **Luồng**: chọn hội thoại → đọc → soạn (gõ `/` để chèn mẫu, hoặc "AI gợi ý") → Gửi. Với bình luận: bấm ✉️ trên comment khách → modal nhắn riêng (ảnh/video/file/mẫu tin) → Gửi.

### Kết nối kênh (Facebook)
- **Đường dẫn**: `/messaging/channels` · **Quyền**: `messaging.connect`.
- **Luồng**: "Kết nối Facebook" → OAuth popup → chọn Page → đồng bộ; có resync, reconnect, ngắt kết nối.

### Mẫu tin
- **Đường dẫn**: `/messaging/templates` · **Quyền**: `messaging.template.manage`.
- **Thành phần**: bảng mẫu trả lời nhanh; modal thêm/sửa có chèn biến (`{{buyer.name}}`...), bật/tắt, xoá.

### Tự động trả lời
- **Đường dẫn**: `/messaging/auto-rules` · **Quyền**: `messaging.rule.manage`.
- **Thành phần**: bảng quy tắc; modal builder: trigger (tin đầu / lịch / theo trạng thái đơn / từ khoá / bình luận…), loại luồng (DM/bình luận/cả hai), đích (công khai/nhắn riêng), hành động (text/mẫu/AI).

### Kịch bản tự động (Flow Builder)
- **Đường dẫn**: `/messaging/flows`, `/messaging/flows/:id/edit` · **Quyền**: `messaging.rule.manage`.
- **Thành phần**: danh sách flow (trạng thái draft/active/paused/archived); trình thiết kế trực quan (node/edge, MiniMap, palette node theo nhóm, drawer cấu hình node, post picker, chọn trigger), Lưu/Xuất bản/Tạm dừng.

### AI training (RAG)
- **Đường dẫn**: `/messaging/knowledge` · **Quyền**: `messaging.ai.train`.
- **Thành phần**: bảng tài liệu (trạng thái pending/ready/failed, số chunk); thêm tài liệu (text/URL/upload), xem chunk, reindex, xoá.

---

## G. Gian hàng (Channels)

- **Mục đích**: kết nối & quản lý gian hàng sàn (TikTok/Lazada; Shopee chờ).
- **Đường dẫn**: `/channels` · **Quyền**: `channels.manage` (xem `channels.view`).
- **Thành phần**: kết nối qua OAuth popup (có hướng dẫn lỗi theo sàn: scope TikTok, whitelist IP/subscribe Lazada); mỗi gian hàng: đổi tên, đồng bộ lại (chạy nền), xoá (gõ đúng tên để xác nhận), bật/tắt tin nhắn, bật/tắt auto-RTS; modal "IP máy chủ" (copy IP để whitelist Lazada).
- **Luồng kết nối**: "Kết nối [sàn]" → `POST /channel-accounts/{provider}/connect` trả `auth_url` → đăng nhập shop & cấp quyền → callback tạo gian hàng + đồng bộ 90 ngày đơn → quay lại "Kết nối thành công".

---

## H. Mua hàng (Procurement)

### Nhà cung cấp
- **Đường dẫn**: `/procurement/suppliers` · **Quyền**: `procurement.view`/`procurement.manage`.
- **Thành phần**: bảng NCC (mã/tên/MST, liên hệ, điều khoản NET-x, số giá đã map, hoạt động) + drawer thêm/sửa; tab "Bảng giá nhập" (giá theo SKU).

### Đơn mua hàng
- **Đường dẫn**: `/procurement/purchase-orders` · **Quyền**: `procurement.manage`/`procurement.receive`.
- **Thành phần**: danh sách PO (draft/confirmed/partially_received/received/cancelled) + drawer tạo (chọn SKU, kho), drawer chi tiết, xác nhận, nhận hàng (→ phiếu nhập, cập nhật tồn/giá vốn), huỷ.

### Đề xuất nhập hàng
- **Đường dẫn**: `/procurement/demand-planning` · **Quyền**: `procurement.manage` + gói `demand_planning`.
- **Thành phần**: bảng (tốc độ bán, khả dụng, đang về, số ngày còn, mức khẩn, SL đề xuất, NCC); ô cửa sổ/lead-time; chọn nhiều dòng → tạo PO nháp tách theo NCC 1 cú bấm.

---

## I. Báo cáo · Đối soát · Kế toán

### Báo cáo
- **Đường dẫn**: `/reports` · **Quyền**: `reports.view` (export `reports.export`; lợi nhuận cần gói `profit_reports`).
- **Thành phần**: tab Doanh thu / Lợi nhuận / Top sản phẩm; preset ngày + RangePicker + độ chi tiết + chip nguồn; biểu đồ/bảng; nút Export (CSV UTF-8 BOM).

### Đối soát sàn
- **Đường dẫn**: `/finance/settlements` · **Quyền**: `finance.view`/`finance.reconcile`; gói `finance_settlements`.
- **Thành phần**: bảng kỳ đối soát (gian hàng, kỳ, trạng thái pending/reconciled/error); modal kéo từ sàn; drawer chi tiết với phí theo dòng (chip màu theo loại phí); nút Đối soát.

### Kế toán (gói `accounting_basic` Pro+; nâng cao `accounting_advanced` Business)
Mỗi trang hiện banner khởi tạo nếu chưa init.
- **Sổ nhật ký** `/accounting/journals` — bảng bút toán (auto/đảo/điều chỉnh), lọc kỳ/nguồn, tìm; chi tiết; tạo bút toán tay (`accounting.post`); đảo bút toán.
- **Hệ thống TK** `/accounting/chart-of-accounts` — cây TK, lọc loại, tạo/sửa/xoá TK (`accounting.config`).
- **Kỳ kế toán** `/accounting/periods` — kỳ theo tháng/năm; đóng/mở/khoá (`accounting.close_period`); tạo kỳ cho năm (`accounting.config`).
- **Cân đối phát sinh** `/accounting/balances` — trial balance theo kỳ; tính lại số dư (`accounting.config`).
- **Công nợ phải thu** `/accounting/ar` — TK 131; tab Aging theo khách / Phiếu thu; tạo/xác nhận/huỷ phiếu thu.
- **Công nợ phải trả** `/accounting/ap` — TK 331; tab Aging theo NCC / Hoá đơn NCC / Phiếu chi.
- **Quỹ & Ngân hàng** `/accounting/cash` — TK tiền mặt/ngân hàng/ví/COD; tạo TK, ghi/nhập giao dịch.
- **Báo cáo tài chính** `/accounting/reports` — Trial / P&L / Bảng cân đối / Sổ cái; export MISA (`accounting.export`).

---

## J. Hệ thống & Cài đặt

### Nhật ký đồng bộ
- **Đường dẫn**: `/sync-logs` · **Quyền**: `channels.view` (retry `channels.manage`).
- **Thành phần**: tab Lần đồng bộ / Webhook; bảng trạng thái + nút redrive/retry.

### Cài đặt (shell `SettingsLayout`)
- **Hồ sơ cá nhân** `/settings/profile` — sửa tên/email (đổi email cần mật khẩu hiện tại) + đổi mật khẩu.
- **Thông tin gian hàng** `/settings/workspace` — tên/slug workspace (`tenant.settings`).
- **Gói & nâng cấp** `/settings/plan` — gói hiện tại + hạn mức, lịch sử hoá đơn, so sánh 4 gói; modal nâng cấp (chu kỳ tháng/năm, cổng SePay/VNPay), huỷ gói (`billing.manage`, chỉ owner).
- **Nhân viên & vai trò** `/settings/members` — bảng thành viên; thêm thành viên (email + vai trò). Chỉ owner/admin.
- **Đơn vị vận chuyển** `/settings/carriers` — tài khoản ĐVVC (GHN live; khác "sắp có"); thêm/sửa (token API, chọn shop GHN, địa chỉ gửi tầng tỉnh/huyện/xã), xác minh, đặt mặc định (`fulfillment.carriers`).
- **Cài đặt đơn hàng** `/settings/orders` — % phí sàn theo nền tảng (để ước tính lợi nhuận).
- **Cấu hình AI tin nhắn** `/settings/messaging` — chọn nhà cung cấp AI, bật AI + auto-mode (tách riêng sàn vs Facebook) (`messaging.ai.config`).
- **Mẫu in** `/settings/print` — khổ tem (A6/100×150/80mm/A5/A4) + ghi chú in mặc định.
- **Mẫu phiếu giao hàng** `/settings/shipping-labels` + trình thiết kế kéo–thả `/settings/shipping-labels/new|:id`.
- **Quy tắc hạch toán** `/settings/accounting/post-rules` — map sự kiện → TK Nợ/Có (`accounting.config`).

---

## K. Ứng dụng super-admin (`/admin/*`)

- **Đăng nhập admin** `/admin/login` — username/password (guard riêng).
- **Tổng quan** `/admin` — màn chào.
- **Tenants** `/admin/tenants` — bảng nhà bán (over-quota/suspended), lọc + tìm, drawer quản lý.
- **Người dùng** `/admin/users` — tab Admin / Tenant users; drawer admin & tenant user; suspend/reset password.
- **Voucher** `/admin/vouchers` — bảng voucher (percent/fixed/free_days/plan_upgrade); tạo, cấp cho tenant, vô hiệu.
- **Gói thuê bao** `/admin/plans` — bảng gói; tạo/sửa với toàn bộ cờ tính năng.
- **Broadcast** `/admin/broadcasts` — gửi email broadcast (toàn bộ owner / admin+owner / tenant chỉ định); lịch sử.
- **Hệ thống** `/admin/settings` — system_settings động, nhóm segmented; "Nạp từ env".
- **Nhà cung cấp AI** `/admin/ai-providers` — bảng provider; thêm/sửa (adapter Anthropic/OpenAI-compatible/custom_http/manual), test kết nối.
- **Nhật ký** `/admin/audit-logs` — bảng audit; lọc action/tenant/user/thời gian; chi tiết.

> Xem thêm luồng đầy đủ trong [user-manual.md](user-manual.md) và quy tắc nghiệp vụ trong [business-rules.md](business-rules.md).
