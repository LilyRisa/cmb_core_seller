---
title: Nhật ký đồng bộ
slug: 16-nhat-ky-dong-bo
menu: "Hệ thống → Nhật ký đồng bộ"
---

# Nhật ký đồng bộ

**Việc này giúp gì:** Theo dõi các lần phần mềm đồng bộ với sàn — biết lần nào thành công, lần nào lỗi, và thử lại khi cần. Hữu ích khi đơn không tự về.

## Các bước

1. Vào menu **Nhật ký đồng bộ** (nhóm **Hệ thống**).

   ![Nhật ký đồng bộ](images/16-nhat-ky-dong-bo-1.png)

2. Xem tab **Lần đồng bộ**: mỗi dòng cho biết gian hàng, loại (Định kỳ / Lấy lại lịch sử / Quét đơn tồn đọng / Webhook), trạng thái (Đang chạy / Hoàn tất / Thất bại) và kết quả (nhận / mới / cập nhật / bỏ qua / lỗi).
3. Lọc theo gian hàng, loại, trạng thái để tìm nhanh.
4. Nếu một lần bị **Thất bại**, bấm **Chạy lại** để thử lại.
5. Tab **Webhook** cho biết các tín hiệu sàn gửi tới (đơn mới, đổi trạng thái…); dòng lỗi có nút **Xử lý lại**.

## Mẹo

- Trang tự làm mới định kỳ. Bạn cũng có thể bấm **Làm mới**.
- Đồng bộ chạy lại an toàn — không tạo đơn trùng.

## Lỗi thường gặp & cách xử lý

- **Nhiều dòng Thất bại liên tục:** Có thể sàn đã hết hạn cấp quyền. Vào [Gian hàng](03-gian-hang.md) bấm **Cấp quyền lại**. Nếu vẫn lỗi, hỏi **Trợ giúp → Hỏi CSKH**.

## Xem thêm

- [Gian hàng (kết nối sàn)](03-gian-hang.md)
- [Đơn hàng & giao hàng](04-don-hang.md)
