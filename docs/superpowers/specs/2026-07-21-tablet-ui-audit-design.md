# Audit giao diện app người dùng trên tablet ngang — Design

## Bối cảnh

Giao diện app người dùng (`app.cmbcore.com`, bundle `resources/js/app.tsx` + `AppLayout.tsx`) đang lỗi khi xem trên tablet ngang / màn hình nhỏ hơn. Kiểm tra sơ bộ cho thấy `AppLayout.tsx` dùng `Layout.Sider` chiều rộng cố định (236px, `collapsedWidth={64}`), không có breakpoint tự thu gọn theo kích thước màn hình — đây nhiều khả năng là nguyên nhân gốc, nhưng cần audit thực tế trên từng trang trước khi kết luận và sửa.

Đây là **giai đoạn 1: audit** — chỉ ghi nhận lỗi, chưa sửa code. Giai đoạn sửa sẽ lên plan riêng sau khi có báo cáo.

## Mục tiêu

Audit trực quan toàn bộ trang trong app người dùng ở các độ phân giải tablet ngang, ghi nhận lỗi hiển thị/tương tác thành 1 báo cáo có bằng chứng (ảnh chụp + mô tả), làm cơ sở cho việc lên kế hoạch sửa ở giai đoạn sau.

## Ngoài phạm vi

- Không sửa code trong giai đoạn này.
- Không audit admin panel (`/admin/*`) — để đợt sau.
- Không audit màn dọc / điện thoại — người dùng xác nhận chỉ cần ngang.

## Cách thực hiện

**Công cụ:** Playwright (MCP browser tools), đăng nhập thật vào production bằng tài khoản người dùng cung cấp (`mìnhmen99@gmail.com` tại `app.cmbcore.com`). Đây là tài khoản/môi trường của chính người dùng, được cung cấp trực tiếp cho mục đích audit này.

**Viewport test** (chỉ ngang, không test dọc):
- 1280×800 — tablet ngang lớn
- 1024×768 — tablet ngang chuẩn (iPad ngang)
- 900×600 — màn hình nhỏ hơn nhưng vẫn ngang

**Danh sách trang** (lấy từ `buildNav()` trong `AppLayout.tsx`, ~35+ trang):
- Đăng nhập, Dashboard
- Đơn hàng (list, tạo đơn, chi tiết 1 đơn), Hoàn & Hủy, Khách hàng, Gian hàng, Sản phẩm & SKU
- Tin nhắn: Hộp thư, Kết nối kênh, Mẫu tin, Tin tiện ích, Tự động trả lời, Kịch bản tự động, AI training (Facebook; Zalo OA dùng chung layout nên không nhân đôi)
- Đăng bán sàn: Sao chép sản phẩm, Chờ đẩy lên sàn, Đã có trên sàn, Chiến dịch giảm giá
- Kho & mua hàng: Tồn kho, Đề xuất nhập hàng, Nhà cung cấp, Đơn mua hàng
- Quảng cáo: Quảng cáo Facebook, Quảng cáo TikTok
- Báo cáo: Báo cáo tổng thể, Báo cáo bán hàng, Báo cáo sàn, Đối soát sàn
- Kế toán: Tổng quan, Sổ nhật ký chung, Hệ thống tài khoản, Cân đối phát sinh, Kỳ kế toán, Công nợ phải thu, Công nợ phải trả, Quỹ & Ngân hàng, Báo cáo tài chính & Thuế
- Hệ thống: Nhật ký đồng bộ, Trung tâm trợ giúp, Cài đặt

**Quy trình mỗi trang:** set viewport → điều hướng tới trang → chụp ảnh toàn trang → ghi nhận lỗi trực quan nếu có (tràn ngang phải cuộn, sidebar/table/form vỡ layout, phần tử chồng lấn, nút/text bị cắt, không bấm được, khoảng trắng bất thường...). Bỏ qua trang nếu rỗng dữ liệu / không thể vào được (ghi chú lý do).

**Phân loại mức độ nghiêm trọng:** High (chặn thao tác, không dùng được) / Medium (dùng được nhưng khó chịu, mất thẩm mỹ) / Low (tiểu tiết).

## Đầu ra

File `docs/technical-audit-tablet-ui-2026-07-21.md`:
- Mục lục theo trang, mỗi mục: tên trang, route, viewport lỗi xuất hiện, mức độ, mô tả lỗi.
- Mục tổng hợp cuối: các lỗi lặp lại nhiều trang (ví dụ lỗi ở khung `AppLayout`/`AppHeader` xuất hiện ở mọi trang) để nhóm gộp khi lên kế hoạch sửa.
- Ảnh chụp lưu tại thư mục scratchpad (không commit vào repo do chứa dữ liệu tài khoản thật); báo cáo mô tả bằng lời, dẫn chiếu ảnh theo tên file nếu cần xem lại trong phiên làm việc này.

## Rủi ro / lưu ý

- Đây là audit trên **production thật** với tài khoản thật — chỉ đọc/xem, không thao tác tạo/sửa/xóa dữ liệu thật (trừ khi cần thiết để vào được 1 trang, ví dụ mở modal xem chi tiết — không submit form).
- Không log/lưu mật khẩu vào file trong repo.
