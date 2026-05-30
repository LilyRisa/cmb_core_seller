# Tổng quan hệ thống — OmniSell / CMBcoreSeller

> Dành cho: khách hàng tra cứu, nhân viên hỗ trợ, Trợ lý AI, người mới onboard.
> Góc nhìn: **người sử dụng phần mềm** (không đi vào chi tiết kỹ thuật). Khi chỉ đường, dùng **tên menu và nút tiếng Việt**.

---

## 1. Hệ thống là gì?

**OmniSell** (tên tầm nhìn / marketing) — còn gọi **CMBcoreSeller** (tên thương hiệu trong sản phẩm & email) — là phần mềm **quản lý bán hàng đa sàn** cho thị trường Việt Nam.

Hệ thống giúp nhà bán **gom mọi việc về một nơi** thay vì phải đăng nhập từng gian hàng riêng: đồng bộ đơn hàng, quản lý tồn kho chung theo từng mã sản phẩm, in tem/phiếu giao hàng hàng loạt, đóng gói bằng quét mã, nhập hàng và tính giá vốn, đối soát tiền sàn trả về và tính lợi nhuận thật, hộp thư tin nhắn hợp nhất có AI trả lời, sổ sách kế toán theo chuẩn Việt Nam (Thông tư 133), và quản lý gói thuê bao.

**Sàn / đối tác hỗ trợ:**

| Loại | Đang chạy | Đang chờ / sắp có |
|---|---|---|
| **Sàn TMĐT** | TikTok Shop, Lazada; đơn thủ công luôn có | Shopee (chờ duyệt kết nối) |
| **Đơn vị vận chuyển** | GHN | GHTK, J&T, ViettelPost, NinjaVan... bổ sung dần |
| **Cổng thanh toán gói** | SePay (chuyển khoản), VNPay | MoMo (đang phát triển) |
| **Tin nhắn** | Facebook + chat Shopee/TikTok/Lazada | — |
| **AI** | Do đội vận hành thiết lập; nhà bán chọn 1 nhà cung cấp | — |

> Vì sao một sàn "có nhưng chưa dùng được"? Mỗi sàn cần được **bật** cho môi trường của bạn. Shopee đã chuẩn bị sẵn nhưng còn chờ Shopee duyệt quyền kết nối. Việc bật thêm do đội ngũ vận hành CMBcoreSeller quyết định.

---

## 2. Ai dùng hệ thống?

**Phía nhà bán (mỗi gian hàng/workspace là một không gian dữ liệu riêng):**

| Vai trò | Dùng để làm gì |
|---|---|
| **Chủ sở hữu** | Toàn quyền, gồm thanh toán/đổi gói, xoá/chuyển gian hàng |
| **Quản trị** | Toàn bộ nghiệp vụ + quản lý nhân viên; **không** thanh toán/xoá gian hàng |
| **Nhân viên xử lý đơn** | Xem/sửa/tạo đơn, chuẩn bị hàng, in, bàn giao; xem khách & nhắn tin |
| **Nhân viên kho** | Tồn kho, điều chỉnh/chuyển/kiểm kê, ghép SKU, nhận hàng, quét đóng gói |
| **Nhân viên chăm sóc khách** | Hộp thư tin nhắn, mẫu tin; xem đơn/khách (không sửa đơn/kho) |
| **Kế toán** | Đối soát, báo cáo, làm sổ sách; xem hoá đơn gói |
| **Chỉ xem** | Chỉ đọc đơn/kho/sản phẩm/khách/bảng điều khiển |

**Phía vận hành dịch vụ:** đội ngũ vận hành CMBcoreSeller làm việc trên nhiều nhà bán, đăng nhập ở khu quản trị riêng — **không** phải vai trò trong gian hàng của bạn. Cần hỗ trợ, bạn dùng nút **Trợ giúp → Hỏi CSKH** ngay trong phần mềm.

---

## 3. Các khu vực chức năng chính

| Khu vực | Vai trò (một dòng) |
|---|---|
| **Tài khoản & nhân sự** | Tài khoản, gian hàng, thành viên, vai trò/quyền, tách biệt dữ liệu, nhật ký thao tác |
| **Gian hàng** | Kết nối sàn, nhận đơn, đồng bộ đơn/sản phẩm, nhật ký đồng bộ |
| **Đơn hàng** | Đơn từ mọi nguồn, trạng thái chuẩn, lịch sử, đơn thủ công, lọc/tìm |
| **Hoàn & Hủy** | Yêu cầu sau bán (hủy/hoàn/refund) có trạng thái riêng |
| **Khách hàng** | Sổ khách, nhận diện theo số điện thoại, thống kê, ghi chú, điểm uy tín |
| **Tồn kho** | Mã sản phẩm, kho, tồn, ghép SKU, đẩy tồn lên sàn, giá vốn |
| **Sản phẩm & SKU** | Sản phẩm gốc, sản phẩm trên sàn, đăng bán hàng loạt |
| **Xử lý & giao hàng** | Vận đơn, lô lấy hàng, lấy tem, in hàng loạt, mẫu in, quét đóng gói |
| **Mua hàng** | Nhà cung cấp, bảng giá nhập, đơn mua, nhận hàng → giá vốn, đề xuất nhập |
| **Đối soát & lợi nhuận** | Kéo tiền sàn trả, phí thật theo đơn, tính lợi nhuận |
| **Báo cáo** | Doanh thu/lợi nhuận/tồn + xuất file. Chỉ đọc |
| **Gói thuê bao** | 4 gói, dùng thử/gia hạn, hoá đơn, thanh toán, giới hạn |
| **Kế toán** | Theo Thông tư 133: hệ thống tài khoản, kỳ, sổ kép, công nợ, quỹ, báo cáo tài chính |
| **Tin nhắn** | Hộp thư hợp nhất, tự động trả lời, AI + tài liệu hướng dẫn AI |
| **Cài đặt** | Hồ sơ, gian hàng, nhân sự, kết nối, vận hành |

---

## 4. Bản đồ menu (toàn bộ điều hướng)

Thanh menu bên trái chia theo nhóm:

- **Tổng quan**
  - Bảng điều khiển
- **Bán hàng**
  - Đơn hàng *(có nút **Tạo đơn** để tạo đơn thủ công, nút **Quét đơn**, nút **Đồng bộ đơn**)*
  - Hoàn & Hủy
  - Khách hàng
  - **Tin nhắn**: Hộp thư · Kết nối kênh · Mẫu tin · Tự động trả lời · Kịch bản tự động · AI training
  - Gian hàng
  - Sản phẩm & SKU
- **Kho & Mua hàng**
  - Tồn kho *(có tab **Liên kết SKU** để ghép SKU)*
  - Đề xuất nhập hàng
  - Nhà cung cấp
  - Đơn mua hàng
- **Báo cáo & Kế toán**
  - Báo cáo
  - Đối soát sàn
  - Sổ nhật ký · Hệ thống TK · Cân đối phát sinh · Công nợ phải thu · Công nợ phải trả · Quỹ & Ngân hàng · Báo cáo tài chính · Kỳ kế toán
- **Hệ thống**
  - Nhật ký đồng bộ
  - **Cài đặt**

**Menu Cài đặt** (các mục con):

- *Tài khoản*: Hồ sơ cá nhân · Thông tin gian hàng · Gói & nâng cấp
- *Nhân sự & phân quyền*: Nhân viên & vai trò
- *Kết nối*: Đơn vị vận chuyển · Gian hàng & module phụ trợ
- *Vận hành*: Cài đặt đơn hàng · Mẫu in · Mẫu phiếu giao hàng · Quy tắc hạch toán · Nhật ký thao tác

> Phần **Cấu hình AI tin nhắn** nằm trong khu vực Tin nhắn (không nằm trong menu Cài đặt).

**Trang công khai (chưa đăng nhập):** Đăng nhập, Đăng ký, Xác thực email, Quên mật khẩu, Đặt lại mật khẩu.

---

## 5. Nền tảng (tóm tắt cho người dùng)

- Phần mềm chạy trên trình duyệt, giao diện tiếng Việt, thiết kế gọn theo từng màn hình.
- Tin nhắn cập nhật gần như tức thời; thông báo tin mới có thể bật trên trình duyệt.
- In tem/phiếu xuất ra file PDF; file lưu khoảng 90 ngày, in lại trả về đúng file cũ.
- Bảo mật: mỗi nhà bán tách biệt dữ liệu hoàn toàn; tiền luôn là **số nguyên đồng** (không số lẻ); giờ hiển thị theo Việt Nam.

---

## 6. Nguyên tắc luôn đúng

1. **Tách biệt dữ liệu**: không nhà bán nào thấy dữ liệu của nhà bán khác.
2. **Một nguồn chuẩn**: tồn kho theo **mã sản phẩm trong kho**; trạng thái đơn theo **bộ trạng thái chuẩn**.
3. **Dữ liệu sàn đáng tin**: hệ thống luôn xác minh tín hiệu, luôn lấy lại chi tiết trước khi lưu, và luôn có lưới kiểm tra định kỳ dự phòng.
4. **An toàn khi chạy lại**: đồng bộ lại không làm nhân đôi dữ liệu.
5. **Tiền = số nguyên đồng** ở mọi nơi (đơn, đối soát, sổ sách, hoá đơn).
6. **Mở rộng an toàn**: thêm sàn/đơn vị vận chuyển/cổng thanh toán mới không phải sửa phần lõi.

> Tài liệu liên quan: [what-the-system-does.md](what-the-system-does.md) · [frontend-guide.md](frontend-guide.md) · [business-rules.md](business-rules.md) · [user-manual.md](user-manual.md) · [faq.md](faq.md) · [troubleshooting.md](troubleshooting.md) · [agent_context.md](agent_context.md)
