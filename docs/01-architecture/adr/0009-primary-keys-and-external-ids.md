# ADR-0009: Khoá chính `bigint identity`, ID lộ ra ngoài không dùng số tự tăng

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Team (Phase 0)

## Bối cảnh

`02-data-model/overview.md` §1 yêu cầu chốt kiểu khoá chính ở Phase 0 và ghi ADR. Các phương án:

1. `bigint` tự tăng (`bigIncrements`) cho mọi bảng — đơn giản, index nhỏ, là mặc định của Laravel.
2. UUID v4 PK ở mọi bảng — không lộ thứ tự/đếm được, nhưng index lớn hơn, ghi phân tán, join chậm hơn, và **xung khắc với partition theo tháng** (partition key cần là cột thời gian, không phải UUID).
3. UUID v7 (time-ordered) PK — bớt vấn đề phân mảnh của v4 nhưng vẫn nặng hơn `bigint` và phức tạp hơn cần thiết cho quy mô ~100 nhà bán.

Vấn đề cần tránh: lộ `id` tự tăng ra API/URL khiến đối thủ đếm được số đơn/khách của một tenant (rủi ro "enumeration").

## Quyết định

- **Khoá chính nội bộ** = `bigint identity` (`$table->id()` hoặc, với bảng partition theo tháng, khoá phức hợp `(id, <cột_thời_gian>)` vì Postgres bắt buộc partition key nằm trong mọi unique/primary key — xem `app/Support/Database/MonthlyPartition.php`).
- **ID lộ ra API/URL** = **không** phải số tự tăng. Dùng hashid (mã hoá `id` nội bộ, có thể đảo ngược, không cần cột mới) hoặc, cho tài nguyên người dùng share link nhiều (đơn, vận đơn), thêm cột `public_id` (ULID/UUID v7) có index unique. Resource layer chịu trách nhiệm map; quy ước này cũng nằm ở `05-api/conventions.md` §5.
- UUID/ULID **được** dùng làm khoá *thứ cấp* khi cần (vd `failed_jobs.uuid`, `public_id`), không thay thế PK `bigint`.

## Hệ quả

- Tích cực: index gọn, join nhanh, tương thích trực tiếp với partition RANGE theo `created_at`; vẫn không lộ số đếm ra ngoài.
- Đánh đổi: cần một lớp encode/decode (hashid) hoặc cột `public_id` bổ sung ở những bảng lộ ra ngoài — chi phí nhỏ, khu trú ở Resource.
- Việc theo sau (Phase 1): chọn & cấu hình thư viện hashid (hoặc helper tự viết) trong `app/Support`; quyết định bảng nào cần `public_id` (đơn, vận đơn, print job có link tải) khi schema các bảng đó được viết.
