# Architecture Decision Records (ADR)

**Mỗi quyết định kiến trúc có ảnh hưởng lâu dài = 1 file ADR ở thư mục này.** Đánh số tăng dần `NNNN-tieu-de.md`. Không sửa nội dung ADR đã "Accepted" — nếu thay đổi, tạo ADR mới ở trạng thái "Supersedes ADR-XXXX".

## Mẫu (copy vào file mới)

```markdown
# ADR-NNNN: <Tiêu đề quyết định>

- **Trạng thái:** Proposed | Accepted | Superseded by ADR-XXXX | Deprecated
- **Ngày:** YYYY-MM-DD
- **Người quyết định:** <tên>

## Bối cảnh
<Vấn đề là gì? Ràng buộc? Các phương án đã cân nhắc?>

## Quyết định
<Chọn gì, và tại sao chọn cái đó.>

## Hệ quả
- Tích cực: ...
- Tiêu cực / đánh đổi: ...
- Việc phải làm theo sau: ...
```

## Sổ ghi quyết định (cập nhật khi thêm ADR)

| # | Tiêu đề | Trạng thái | Ngày |
|---|---|---|---|
| 0001 | Backend Laravel + React SPA nhúng trong Laravel, chỉ `/api` + `/webhook` + `/oauth/callback` | Accepted | 2026-05-11 |
| 0002 | Dùng PostgreSQL 15 (không MySQL) + partition theo tháng | Accepted | 2026-05-11 |
| 0003 | Modular monolith (domain modules), không microservice | Accepted | 2026-05-11 |
| 0004 | Connector + Registry pattern cho sàn & ĐVVC; core không biết tên sàn | Accepted | 2026-05-11 |
| 0005 | Chỉ thị trường Việt Nam ở giai đoạn này (VND, ĐVVC VN); kiến trúc chừa đường mở rộng | Accepted | 2026-05-11 |
| 0006 | UI kit React = Ant Design 5 | Accepted | 2026-05-11 |
| 0007 | Đồng bộ đơn = webhook + polling backup; mọi job idempotent | Accepted | 2026-05-11 |
| 0008 | Tồn kho: master SKU là một nguồn sự thật; ghép SKU hỗ trợ combo (1→N) | Accepted | 2026-05-11 |

> Khi bạn thực sự tạo các file `0001-*.md` ... `0008-*.md`, dùng mẫu trên và chép bối cảnh/lý do tương ứng từ `tech-stack.md`, `extensibility-rules.md`, `vision-and-scope.md`. Hiện liệt kê ở đây để không mất dấu quyết định nào.
