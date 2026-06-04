---
title: Kế toán (TT133)
slug: 14-ke-toan
menu: "Báo cáo & Kế toán → Sổ nhật ký / Hệ thống TK / ... / Kỳ kế toán"
plan: Pro
roles: ["Kế toán", "Quản trị", "Chủ sở hữu"]
---

# Kế toán (Thông tư 133)

**Việc này giúp gì:** Ghi sổ kế toán theo Thông tư 133 ngay trong phần mềm: sổ nhật ký, hệ thống tài khoản, công nợ, quỹ, báo cáo tài chính và kỳ kế toán. Nhiều bút toán được **lên sổ tự động** khi bạn nhận hàng, giao đơn, đối soát.

**Bạn cần:** Gói **Pro** trở lên (nâng cao cần **Business**); vai trò **Kế toán**, **Quản trị** hoặc **Chủ sở hữu**.

## Khởi tạo lần đầu

- Nếu mới dùng, trang Kế toán sẽ hiện nút **Khởi tạo TT133**. Bấm một lần để hệ thống tự tạo hệ thống tài khoản, các kỳ và quy tắc hạch toán. (Đã khởi tạo rồi thì bỏ qua bước này.)

## Sổ nhật ký

1. Vào menu **Sổ nhật ký**.

   ![Sổ nhật ký](images/14-ke-toan-1.png)

2. Xem danh sách bút toán (tự động + tay). Lọc theo **nguồn**, **kỳ**, hoặc gõ tìm mã/diễn giải.
3. Bấm **Tạo bút toán tay** để ghi tay: nhập ngày, diễn giải và các dòng tài khoản sao cho **tổng Nợ = tổng Có**.
4. Cần sửa một bút toán đã ghi: dùng **Đảo bút toán** (kế toán không sửa trực tiếp, mà ghi đảo rồi ghi lại).

## Các trang kế toán khác

- **Hệ thống TK**: cây tài khoản theo TT133; thêm/sửa/ẩn tài khoản.
- **Cân đối phát sinh**: dư đầu + phát sinh Nợ/Có + dư cuối theo kỳ.
- **Công nợ phải thu**: tuổi nợ theo khách; tạo **Phiếu thu**.
- **Công nợ phải trả**: tuổi nợ theo nhà cung cấp; nhập **Hóa đơn** và tạo **Phiếu chi**.
- **Quỹ & Ngân hàng**: quản lý quỹ tiền mặt và tài khoản ngân hàng; **Import sao kê** rồi khớp giao dịch.
- **Báo cáo tài chính**: Cân đối kế toán, Kết quả kinh doanh, Sổ chi tiết tài khoản; xuất ra định dạng MISA.

## Kỳ kế toán

1. Vào menu **Kỳ kế toán**.

   ![Kỳ kế toán](images/14-ke-toan-2.png)

2. Mỗi kỳ có trạng thái: **Mở** (đang ghi) → **Đóng** (chốt số) → **Khoá** (vĩnh viễn, sau khi nộp tờ khai).
3. Cuối kỳ, bấm **Đóng kỳ** và ghi chú. Cần sửa lại có thể **Mở lại** (nếu kỳ sau chưa đóng).

## Mẹo

- Bút toán tự động sinh ra khi: nhận hàng, chuyển kho, kiểm kê, giao đơn, đối soát sàn — bạn không phải ghi tay những việc này.

## Lỗi thường gặp & cách xử lý

- **"Kỳ kế toán đã đóng nên không ghi thêm được":** Bạn đang ghi vào kỳ đã đóng. Hãy **Mở lại** kỳ đó, hoặc ghi vào kỳ đang mở.
- **Bút toán không cân (Nợ khác Có):** Kiểm tra lại các dòng sao cho tổng Nợ bằng tổng Có.

## Xem thêm

- [Mua hàng](09-mua-hang.md)
- [Đối soát & lợi nhuận](12-doi-soat-loi-nhuan.md)
