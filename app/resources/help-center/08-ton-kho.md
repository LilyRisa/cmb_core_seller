---
title: Tồn kho
slug: 08-ton-kho
menu: "Kho & Mua hàng → Tồn kho"
roles: ["NV kho", "Quản trị", "Chủ sở hữu"]
---

# Tồn kho

**Việc này giúp gì:** Theo dõi số lượng hàng thật, điều chỉnh tồn và đẩy số tồn lên các sàn để tránh bán quá (oversell).

**Bạn cần:** Vai trò **NV kho**, **Quản trị** hoặc **Chủ sở hữu** để điều chỉnh tồn.

## Hiểu các cột tồn

Vào menu **Tồn kho**, tab **Tồn theo SKU**:

![Tồn theo SKU](images/08-ton-kho-1.png)

- **Thực có**: số hàng thật trong kho.
- **Đang giữ**: số đang giữ cho các đơn chưa giao.
- **An toàn**: mức dự phòng bạn muốn chừa lại.
- **Khả dụng**: số thực sự bán được, cũng là số đẩy lên sàn.

> Công thức: **Khả dụng = Thực có − Đang giữ − An toàn.**

## Điều chỉnh tồn

1. Ở tab **Tồn theo SKU**, bấm **Điều chỉnh** trên dòng mã hàng cần sửa.
2. Nhập số thay đổi: số dương để **nhập thêm**, số âm để **xuất bớt**, kèm ghi chú.
3. Lưu lại. Mỗi lần điều chỉnh đều được ghi nhận.

> Cần điều chỉnh nhiều mã cùng lúc: mở tab **Danh mục SKU** → **Phiếu nhập/xuất hàng loạt**.

## Đẩy tồn lên sàn

1. Mở tab **Danh mục SKU**, tích chọn các mã hàng cần đẩy.
2. Bấm **Đẩy tồn lên sàn**.
3. Hệ thống gửi số tồn khả dụng lên các sàn đã ghép; trạng thái sẽ cập nhật khi sàn xác nhận.

## Mẹo

- Bấm **Sắp hết (≤5)** để lọc nhanh các mã sắp hết hàng.
- Diễn biến tồn theo đơn: vào Chờ xử lý/Đang xử lý → **giữ** tồn; huỷ trước giao → **nhả**; sang Đang giao → **trừ**; hoàn và hàng về → **cộng lại**.
- **Giá vốn** tính theo kiểu nhập trước xuất trước và được chốt khi đơn giao.

## Lỗi thường gặp & cách xử lý

- **Cột Khả dụng màu đỏ / ghi "âm":** Đang giữ nhiều hơn thực có (thường do trả hàng hoặc bán vượt). Nhập thêm hàng hoặc kiểm tra lại đơn.
- **Đẩy tồn báo lỗi:** Sản phẩm có thể chưa ghép SKU, hoặc bị khoá đẩy. Kiểm tra phần [Sản phẩm & SKU](07-san-pham-sku.md); nếu vẫn lỗi, hỏi **Trợ giúp → Hỏi CSKH**.

## Xem thêm

- [Sản phẩm & SKU](07-san-pham-sku.md)
- [Mua hàng](09-mua-hang.md)
- [Đối soát & lợi nhuận](12-doi-soat-loi-nhuan.md)
