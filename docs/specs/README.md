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
| [0002](0002-customer-registry-and-buyer-reputation.md) | Sổ khách hàng & cờ rủi ro (cross-order matching theo SĐT) | 2 | Implemented | — |
| [0003](0003-products-skus-inventory-manual-orders.md) | Sản phẩm / SKU / Tồn kho lõi / Đơn thủ công / Ghép SKU / Đẩy tồn | 2 | Implemented | — |
| [0004](0004-bulk-stock-and-sku-linking.md) | Tồn kho thủ công hàng loạt + Liên kết SKU nhanh từ đơn | 2 | Implemented | — |
| [0005](0005-sku-pim-and-create-form.md) | SKU PIM (thông tin hàng hoá) & form "Thêm SKU đơn độc" | 2 | Implemented | — |
| [0006](0006-fulfillment-shipments-and-printing.md) | Giao hàng & in: vận đơn, ĐVVC (Manual + GHN), in tem hàng loạt, picking/packing, quét đóng gói | 3 | Implemented (lõi; logistics-của-sàn / GHTK+JT / template tuỳ biến / lưu-in-lại-90-ngày để follow-up) | — |
| [0007](0007-settings-center.md) | Trung tâm Cài đặt — tài khoản/gói, trung tâm kết nối, nhân viên username-only & vai trò chi tiết, cài đặt đơn hàng, mẫu in *(umbrella plan; tách thành các spec con với số kế tiếp khi triển khai)* | 3–6 | Draft | — |
| [0008](0008-lazada-channel.md) | Lazada — connector (auth + đồng bộ đơn + listings + đẩy tồn + webhook) | 4 | Implemented (lõi; RTS/AWB của Lazada + đối soát = follow-up) | — |
| [0009](0009-order-processing-screen.md) | Màn "Xử lý đơn hàng" — chuẩn bị → in tem → đóng gói → bàn giao ĐVVC (1 màn, đếm số lần in, lọc nền tảng/khách/SP, API quét cho app) | 3 | Implemented (mở rộng SPEC-0006; luồng-logistics-của-sàn + tách nhiều kiện = follow-up) | — |

## Khi nào KHÔNG cần spec
- Sửa lỗi nhỏ, refactor không đổi hành vi, thay đổi tài liệu, bump dependency, chỉnh CI — chỉ cần PR mô tả rõ. Nhưng nếu "việc nhỏ" hoá ra động chạm kiến trúc/hành vi nghiệp vụ ⇒ dừng, viết spec/ADR.
