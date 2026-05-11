# Feature Specs

**Mọi tính năng lớn (một module/luồng nghiệp vụ đáng kể) phải có một spec ở thư mục này TRƯỚC khi code.** Spec nhỏ gọn (1–3 trang), trả lời: làm gì, vì sao, ranh giới, dữ liệu, API, edge case, cách test. PR triển khai phải link tới spec.

## Quy ước
- Đặt tên file: `NNNN-<slug>.md`, `NNNN` là số tăng dần 4 chữ số. Ví dụ: `0001-connect-tiktok-shop.md`, `0002-order-sync-pipeline.md`, `0003-manual-order-create.md`, `0004-sku-mapping.md`, `0005-stock-push.md`, `0006-bulk-label-print.md`, `0007-scan-to-pack.md`, ...
- Copy `_TEMPLATE.md` ra file mới, điền đầy đủ.
- Trạng thái spec ghi ở đầu file: `Draft` → `Reviewed` (đã được duyệt, sẵn sàng code) → `Implemented` (đã xong, link PR) → `Superseded by NNNN` (nếu thay đổi lớn).
- Spec **không** đi vào chi tiết code; nó định nghĩa hành vi & ràng buộc. Quyết định kiến trúc lớn nảy sinh từ spec ⇒ tách ra ADR riêng.
- Spec phải nhất quán với các tài liệu nền (`00-overview/*`, `01-architecture/*`, `03-domain/*`). Mâu thuẫn ⇒ sửa tài liệu nền trước (qua thảo luận/ADR).

## Sổ spec (cập nhật khi thêm)
| # | Tên | Phase | Trạng thái | PR |
|---|---|---|---|---|
| [0001](0001-tiktok-order-sync.md) | TikTok Shop — kết nối gian hàng & đồng bộ đơn | 1 | Implemented (code xong; chờ kiểm thử sandbox thật) | — |
| [0002](0002-customer-registry-and-buyer-reputation.md) | Sổ khách hàng & cờ rủi ro (cross-order matching theo SĐT) | 2 | Draft | — |

## Khi nào KHÔNG cần spec
- Sửa lỗi nhỏ, refactor không đổi hành vi, thay đổi tài liệu, bump dependency, chỉnh CI — chỉ cần PR mô tả rõ. Nhưng nếu "việc nhỏ" hoá ra động chạm kiến trúc/hành vi nghiệp vụ ⇒ dừng, viết spec/ADR.
