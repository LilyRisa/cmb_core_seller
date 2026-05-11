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
| 0009 | Khoá chính `bigint identity`; ID lộ ra ngoài không dùng số tự tăng (hashid / `public_id`) | Accepted | 2026-05-11 |
| 0010 | RBAC bằng Role enum + permission map tự viết (chưa dùng spatie/laravel-permission) | Accepted | 2026-05-11 |

> Tất cả 0001–0010 đã có file `NNNN-*.md` trong thư mục này. Khi thêm quyết định mới: tạo file theo mẫu trên, thêm dòng vào bảng, và (nếu cần) cập nhật `tech-stack.md`/`extensibility-rules.md`/doc liên quan.
