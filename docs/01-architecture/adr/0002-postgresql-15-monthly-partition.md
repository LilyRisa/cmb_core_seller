# ADR-0002: Dùng PostgreSQL 15 (không MySQL); bảng lớn partition RANGE theo tháng

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Team

## Bối cảnh

Quy mô mục tiêu ~500k đơn/tháng (~17k/ngày): bảng `orders`, `order_items`, `order_status_history`, `inventory_movements`, `webhook_events`, `sync_runs`, `settlement_lines`… sẽ rất lớn. Cần lưu **payload thô** từ sàn (jsonb) để debug/tái xử lý, truy vấn báo cáo phức tạp (window/CTE), và một cơ chế giữ DB gọn theo thời gian.

## Quyết định

- DB chính = **PostgreSQL 15**. Lý do: `jsonb` + index GIN cho payload sàn; **partition RANGE theo tháng** (declarative partitioning) cho bảng lớn; CTE/window mạnh cho báo cáo; `timestamptz`.
- Bảng lớn ⇒ `PARTITION BY RANGE (<cột thời gian>)`; job định kỳ tạo trước partition tháng kế (`db:partitions:ensure` + helper `app/Support/Database/MonthlyPartition.php` + `PartitionRegistry`); archive/drop partition cũ theo chính sách lưu trữ. Vì Postgres bắt khoá phân vùng nằm trong mọi unique/primary key ⇒ bảng partition dùng khoá phức hợp `(id, <cột thời gian>)` (xem ADR-0009).
- Test/dev nhanh có thể chạy SQLite (`MonthlyPartition` tự degrade thành bảng thường) — nhưng staging/prod là Postgres.

## Hệ quả

- Tích cực: truy vấn theo khoảng thời gian + drop partition cũ rẻ; jsonb thay vì cột "linh tinh"; hợp với báo cáo.
- Đánh đổi: migration của bảng partition phải khai báo khoá tường minh + ràng buộc unique phải chứa cột partition; một số thao tác (vd unique cross-tenant không kèm thời gian) cần thiết kế lại. Lên managed (RDS/Cloud SQL) sau chỉ đổi config.
- Liên quan: `02-data-model/overview.md` §1, `07-infra/queues-and-scheduler.md` §2, ADR-0009.
