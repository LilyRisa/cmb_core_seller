# Hướng dẫn giao diện (Frontend Guide)

> Mô tả từng màn hình theo góc nhìn người dùng: mục đích, **cách vào** (menu/nút), ai dùng được, thành phần và **luồng thao tác**.
> Quy ước: giao diện **tự ẩn nút/cột** khi vai trò của bạn không đủ quyền. Một số khối tự ẩn nếu gói chưa mở tính năng (ví dụ Bảng điều khiển ẩn khối kế toán khi chưa bật).

---

## A. Đăng nhập & bắt đầu

### Đăng nhập
- **Mục đích**: vào hệ thống. **Cách vào**: trang Đăng nhập (công khai).
- **Thành phần**: ô email + mật khẩu, ghi nhớ đăng nhập, liên kết sang Đăng ký / Quên mật khẩu.
- **Luồng**: nhập email + mật khẩu → bấm **Đăng nhập** → vào Bảng điều khiển.

### Đăng ký
- **Mục đích**: tạo tài khoản + gian hàng mới; người tạo là Chủ sở hữu. **Cách vào**: trang Đăng ký (công khai).
- **Luồng**: điền Họ tên / Email / Mật khẩu → bấm **Đăng ký** → tạo tài khoản, gửi email xác thực, bắt đầu 14 ngày dùng thử → vào hệ thống nhưng bị chặn cho tới khi xác thực email.

### Xác thực email
- **Mục đích**: bắt buộc xác thực trước khi dùng tính năng.
- **Thành phần**: hướng dẫn 4 bước (mở hộp thư → tìm email → kiểm tra Spam/Quảng cáo → bấm liên kết), nút **Gửi lại email** (chờ 60 giây), nút làm mới, đăng xuất.
- **Luồng**: bấm liên kết trong email → quay về với thông báo xác thực thành công → vào dùng được.

### Quên / Đặt lại mật khẩu
- **Cách vào**: trang Quên mật khẩu (công khai).
- **Luồng**: nhập email → nhận liên kết → đặt mật khẩu mới (≥ 8 ký tự, có hoa/thường/ký tự đặc biệt) → về đăng nhập.

---

## B. Bảng điều khiển

- **Mục đích**: tổng quan tình hình kinh doanh + việc cần làm. **Cách vào**: menu **Bảng điều khiển**. Chỉ đọc.
- **Thành phần**: bộ chọn khoảng (7/30/90 ngày) + Làm mới; thẻ chỉ số (Doanh thu, Số đơn, Lợi nhuận ước tính, Giá trị TB/đơn) có biểu đồ nhỏ + so kỳ trước; biểu đồ doanh thu+lợi nhuận; danh sách **Việc cần làm** (đơn chờ xử lý/chờ bàn giao/chờ in/chưa ghép SKU/có vấn đề/gian hàng cần kết nối lại — bấm vào mở thẳng danh sách đơn đã lọc); Top sản phẩm; doanh thu theo kênh; khối **Thống kê nhanh kế toán** (ẩn nếu chưa bật kế toán); nhắc kết nối khi chưa có gian hàng nào.
- **Luồng**: mở Bảng điều khiển → đọc chỉ số → bấm mục trong **Việc cần làm** → nhảy vào danh sách đơn để xử lý.

---

## C. Đơn hàng & xử lý

### Đơn hàng (màn hình trung tâm)
- **Mục đích**: xem, lọc, xử lý đơn từ mọi nguồn. **Cách vào**: menu **Đơn hàng**. Cần quyền xem đơn (các nút khác cần quyền tạo đơn / giao hàng / in / ghép SKU).
- **Thành phần**:
  - **Tab trạng thái** (Tất cả / Chờ xử lý / Đang xử lý / Chờ bàn giao…) + tab **Có vấn đề** + **Hết hàng** + **Vận đơn**.
  - **Bộ lọc**: chọn trường tìm (mã đơn/người mua, SKU, tên SP) + ô tìm; dải lọc theo Sàn → Gian hàng → Vận chuyển, theo Phiếu in, theo Thời gian; sắp xếp.
  - **Chọn nhiều dòng** + **thanh thao tác hàng loạt**: Chuẩn bị hàng, Sẵn sàng bàn giao, Bàn giao đơn vị vận chuyển, Nhận phiếu giao hàng, In phiếu giao hàng, Liên kết SKU.
  - **Nút đầu trang**: **Tạo đơn**, **Quét đơn**, **Đồng bộ đơn**, Làm mới.
- **Luồng "Chuẩn bị hàng"**: ở tab Chờ xử lý chọn đơn → bấm **Chuẩn bị hàng** → hệ thống tạo vận đơn + lấy tem → đơn sang Đang xử lý. Có cảnh báo khi trộn đơn sàn với đơn thủ công, hoặc khi lợi nhuận âm / in lại.

### Chi tiết đơn
- **Cách vào**: bấm vào một đơn trong danh sách.
- **Thành phần**: phần đầu (sàn, gian hàng, trạng thái, COD); nút **Sửa đơn** chỉ hiện với đơn thủ công chưa kết thúc; thân = chi tiết sản phẩm + lịch sử trạng thái.

### Tạo / Sửa đơn thủ công (kiểu bán tại quầy)
- **Cách vào**: menu **Đơn hàng** → nút **Tạo đơn** (hoặc nút Sửa đơn trong đơn thủ công).
- **Thành phần**: khối sản phẩm có tìm nhanh + tab Sản phẩm/Combo; tra số điện thoại khách (cảnh báo khách quen / khách bị chặn); chọn địa chỉ tỉnh/huyện/xã; nguồn phụ (Website/Facebook/Zalo/Hotline); ô thanh toán; đính kèm; ghi chú in; nút **Lưu đơn** / **Lưu & In**. Có lưu nháp tạm.
- **Luồng**: chọn nguồn → nhập khách → **Tìm & thêm sản phẩm** (hoặc **Tạo sản phẩm nhanh** cho món không có sẵn) → chỉnh SL/giá/giảm → **Lưu đơn** (tự giữ tồn).

### Hoàn & Hủy
- **Mục đích**: duyệt yêu cầu sau bán. **Cách vào**: menu **Hoàn & Hủy**. Xem: ai xem được đơn; Duyệt/Từ chối: Chủ sở hữu/Quản trị/NV xử lý đơn.
- **Thành phần**: tab (Chờ duyệt / Đang mở / Tất cả) + lọc loại; bảng (đơn, loại, trạng thái, lý do, hoàn tiền, thời gian); nút **Duyệt** / **Từ chối**.

---

## D. Sản phẩm / Tồn kho

### Tồn kho
- **Cách vào**: menu **Tồn kho** (mục **Sản phẩm & SKU** cũng dẫn về đây). Cần quyền xem tồn.
- **Thành phần (các tab)**:
  - **Tồn theo SKU**: bảng tồn, lọc sắp hết, nút Điều chỉnh từng dòng.
  - **Danh mục SKU**: danh sách mã sản phẩm, tìm, **Thêm SKU**, điều chỉnh/đẩy tồn hàng loạt, xoá.
  - **Liên kết SKU**: ghép sản phẩm sàn ↔ mã trong kho, **Tự khớp**.
  - **Phiếu kho**: nhập/chuyển kho/kiểm kê.
- **Luồng ghép SKU**: tab **Liên kết SKU** → sản phẩm "Chưa ghép" → **Tự khớp** (gợi ý ghép đơn khi mã trùng) hoặc tạo combo → lưu → tồn được đẩy tự động.

### Thêm / Sửa mã sản phẩm (SKU)
- **Cách vào**: trong Tồn kho/Sản phẩm & SKU → **Thêm SKU**.
- **Thành phần**: ảnh, thông tin cơ bản (mã/tên/đơn vị), phương pháp giá vốn (bình quân/FIFO), giá vốn, trạng thái hoạt động.

---

## E. Khách hàng

### Danh sách khách hàng
- **Cách vào**: menu **Khách hàng**. Cần quyền xem khách.
- **Thành phần**: tab theo uy tín + tìm + sắp xếp; bảng (tên/SĐT che, điểm uy tín, nhãn, đơn hoàn tất/hủy, doanh thu, gần nhất).

### Chi tiết khách hàng
- **Cách vào**: bấm một khách trong danh sách.
- **Thành phần**: thông tin + thống kê vòng đời, điểm uy tín, lịch sử đơn, dòng thời gian ghi chú + thêm ghi chú (thông tin/cảnh báo/nguy hiểm), nút chặn khách.

---

## F. Tin nhắn (Hộp thư hợp nhất) — cần gói có hộp thư (Pro trở lên)

### Hộp thư
- **Cách vào**: menu **Tin nhắn → Hộp thư**. Xem: quyền xem tin nhắn; trả lời: quyền trả lời.
- **Thành phần (3 cột)**: trái = danh sách hội thoại (lọc theo sàn/loại/đọc–chưa đọc/thẻ); giữa = nội dung + ô soạn (chữ + ảnh/video/file, gõ "/" để chèn mẫu tin, emoji, **AI gợi ý**), với bình luận Facebook có thẻ bài viết đầu luồng + nút trên từng bình luận (Thích/Nhắn riêng/Xoá); phải = thông tin khách + đơn liên quan.
- **Luồng**: chọn hội thoại → đọc → soạn (gõ "/" chèn mẫu, hoặc **AI gợi ý**) → Gửi. Với bình luận: bấm nút Nhắn riêng trên bình luận khách → cửa sổ nhắn riêng → Gửi.

### Kết nối kênh (Facebook)
- **Cách vào**: menu **Tin nhắn → Kết nối kênh**.
- **Luồng**: **Kết nối Facebook** → chọn Trang → đồng bộ; có làm mới, kết nối lại, ngắt kết nối.

### Mẫu tin
- **Cách vào**: menu **Tin nhắn → Mẫu tin**.
- **Thành phần**: bảng mẫu trả lời nhanh; thêm/sửa có chèn biến (tên người mua...), bật/tắt, xoá.

### Tự động trả lời
- **Cách vào**: menu **Tin nhắn → Tự động trả lời**.
- **Thành phần**: bảng quy tắc; tạo quy tắc chọn loại kích hoạt (tin đầu / lịch / theo trạng thái đơn / chưa trả lời sau N phút / bình luận…), áp cho tin nhắn/bình luận/cả hai, trả công khai/nhắn riêng, nội dung (chữ/mẫu/AI).

### Kịch bản tự động — gói Business
- **Cách vào**: menu **Tin nhắn → Kịch bản tự động**.
- **Thành phần**: danh sách kịch bản; trình thiết kế trực quan dạng sơ đồ; Lưu / Xuất bản / Tạm dừng.

### AI training (tài liệu cho AI)
- **Cách vào**: menu **Tin nhắn → AI training**.
- **Thành phần**: bảng tài liệu; thêm tài liệu (chữ/đường dẫn/tải file), xem nội dung đã chia, làm mới, xoá.

### Cấu hình AI tin nhắn
- **Cách vào**: trong khu vực **Tin nhắn → Cấu hình AI**.
- **Thành phần**: chọn nhà cung cấp AI, bật AI + chế độ tự gửi (tách riêng sàn vs Facebook).

---

## G. Gian hàng

- **Mục đích**: kết nối & quản lý gian hàng sàn. **Cách vào**: menu **Gian hàng**. Cần quyền quản lý gian hàng.
- **Thành phần**: kết nối qua đăng nhập sàn (kèm hướng dẫn lỗi theo sàn); mỗi gian hàng: đổi tên, đồng bộ lại, xoá (gõ đúng tên để xác nhận), bật/tắt tin nhắn, bật/tắt tự động chuyển chờ bàn giao; mục **IP máy chủ** (sao chép IP để khai báo cho Lazada).
- **Luồng kết nối**: bấm **Kết nối [sàn]** → đăng nhập shop & cấp quyền → quay về tạo gian hàng + đồng bộ 90 ngày đơn → thấy "Kết nối thành công".

---

## H. Mua hàng

### Nhà cung cấp
- **Cách vào**: menu **Nhà cung cấp**.
- **Thành phần**: bảng nhà cung cấp (mã/tên/MST, liên hệ, điều khoản công nợ, số giá đã đặt) + thêm/sửa; tab Bảng giá nhập (giá theo mã sản phẩm).

### Đơn mua hàng
- **Cách vào**: menu **Đơn mua hàng**.
- **Thành phần**: danh sách đơn mua (nháp/đã xác nhận/nhận một phần/đã nhận/đã huỷ) + tạo (chọn mã sản phẩm, kho), chi tiết, xác nhận, nhận hàng (tăng tồn/giá vốn), huỷ.

### Đề xuất nhập hàng — cần tính năng tương ứng (Pro trở lên)
- **Cách vào**: menu **Đề xuất nhập hàng**.
- **Thành phần**: bảng (tốc độ bán, khả dụng, đang về, số ngày còn, mức khẩn, SL đề xuất, nhà cung cấp); ô khoảng thời gian/thời gian giao; chọn nhiều dòng → tạo đơn mua nháp tách theo nhà cung cấp một cú bấm.

---

## I. Báo cáo · Đối soát · Kế toán

### Báo cáo
- **Cách vào**: menu **Báo cáo**. Xem: Chủ sở hữu/Quản trị/Kế toán; lợi nhuận cần gói Pro+.
- **Thành phần**: tab Doanh thu / Lợi nhuận / Top sản phẩm; mốc thời gian + khoảng tuỳ ý + mức chi tiết + lọc nguồn; biểu đồ/bảng; nút **Xuất file**.

### Đối soát sàn — gói Pro+
- **Cách vào**: menu **Đối soát sàn**.
- **Thành phần**: bảng kỳ đối soát (gian hàng, kỳ, trạng thái chờ/đã đối soát/lỗi); kéo từ sàn; chi tiết phí theo dòng; nút **Đối soát**.

### Kế toán (gói kế toán Pro+; nâng cao Business)
Mỗi trang hiện dải Khởi tạo nếu chưa khởi tạo.
- **Sổ nhật ký** — bảng bút toán, lọc kỳ/nguồn, tìm; tạo bút toán tay; ghi bút toán đảo.
- **Hệ thống TK** — cây tài khoản, tạo/sửa/xoá.
- **Kỳ kế toán** — kỳ theo tháng/năm; đóng/mở/khoá; tạo kỳ cho năm.
- **Cân đối phát sinh** — bảng cân đối theo kỳ; tính lại số dư.
- **Công nợ phải thu** — theo dõi nợ khách; phiếu thu.
- **Công nợ phải trả** — theo dõi nợ nhà cung cấp; hoá đơn NCC; phiếu chi.
- **Quỹ & Ngân hàng** — tài khoản tiền mặt/ngân hàng/ví/COD; ghi giao dịch.
- **Báo cáo tài chính** — Cân đối phát sinh / Kết quả kinh doanh / Bảng cân đối / Sổ cái; xuất MISA.

---

## J. Hệ thống & Cài đặt

### Nhật ký đồng bộ
- **Cách vào**: menu **Nhật ký đồng bộ**.
- **Thành phần**: tab Lần đồng bộ / Tín hiệu từ sàn; bảng trạng thái + nút chạy lại.

### Cài đặt
- **Hồ sơ cá nhân** — đổi tên/email (đổi email cần mật khẩu hiện tại) + đổi mật khẩu.
- **Thông tin gian hàng** — tên/đường dẫn gian hàng.
- **Gói & nâng cấp** — gói hiện tại + hạn mức, hoá đơn, so sánh 4 gói; nâng cấp (chu kỳ + cổng), huỷ (chỉ Chủ sở hữu).
- **Nhân viên & vai trò** — bảng thành viên; thêm thành viên (email + vai trò). Chỉ Chủ sở hữu/Quản trị.
- **Đơn vị vận chuyển** — tài khoản hãng vận chuyển (GHN); thêm/sửa (khoá kết nối, chọn shop, địa chỉ gửi), xác minh, đặt mặc định.
- **Gian hàng & module phụ trợ** — bật/tắt kết nối phụ trợ.
- **Cài đặt đơn hàng** — % phí sàn theo nền tảng (để ước tính lợi nhuận).
- **Mẫu in** — khổ tem + ghi chú in mặc định.
- **Mẫu phiếu giao hàng** — thiết kế bằng công cụ kéo–thả.
- **Quy tắc hạch toán** — nối nghiệp vụ → tài khoản Nợ/Có.
- **Nhật ký thao tác** — tra cứu ai làm gì.

---

> Xem thêm các bước cụ thể trong [user-manual.md](user-manual.md) và quy tắc nghiệp vụ trong [business-rules.md](business-rules.md).
