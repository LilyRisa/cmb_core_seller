# SPEC NNNN: <Tên tính năng>

- **Trạng thái:** Draft | Reviewed | Implemented (PR #...) | Superseded by NNNN
- **Phase:** <0..7>
- **Module backend liên quan:** <Orders | Inventory | Channels | Fulfillment | ...>
- **Tác giả / Ngày:** <tên> · YYYY-MM-DD
- **Liên quan:** ADR-XXXX, spec NNNN, doc `03-domain/...`

## 1. Vấn đề & mục tiêu
<Người dùng cần gì? Tại sao làm bây giờ? Nó thuộc phase nào (kiểm với roadmap)?>

## 2. Trong / ngoài phạm vi của spec này
- **Trong:** ...
- **Ngoài (làm sau / spec khác):** ...

## 3. Câu chuyện người dùng / luồng chính
<Mô tả từng bước người dùng làm gì, hệ thống phản ứng ra sao. Có thể vẽ sơ đồ luồng.>

## 4. Hành vi & quy tắc nghiệp vụ
- Quy tắc 1 ...
- Quy tắc 2 ... (liên kết tới `03-domain/*` nếu áp dụng quy tắc đã có; nếu khác/đặc thù thì nêu rõ)
- Idempotency: ...
- Tác động tồn kho / trạng thái đơn / tài chính (nếu có): ...
- Phân quyền: role nào được làm gì.

## 5. Dữ liệu
- Bảng/cột mới hoặc thay đổi (nhất quán với `02-data-model/overview.md`): ...
- Migration: reversible? index? unique chống trùng? partition?
- Domain event phát ra / lắng nghe: ...

## 6. API & UI
- Endpoint mới/đổi (nhất quán với `05-api/conventions.md`): method, path, quyền, request, response, lỗi đặc thù → cập nhật `05-api/endpoints.md`.
- Màn hình / thành phần FE (nhất quán với `06-frontend/overview.md`): ...
- Job mới (nhất quán với `07-infra/queues-and-scheduler.md`): queue nào, tần suất, retry → cập nhật bảng job.
- Nếu đụng connector sàn/ĐVVC: nêu method `ChannelConnector`/`CarrierConnector` dùng; **không** thêm logic theo tên sàn ở core (xem `extensibility-rules.md`).

## 7. Edge case & lỗi
- ...
- Token hết hạn / rate limit / API sàn lỗi → xử lý thế nào.
- Dữ liệu thiếu (vd SKU chưa ghép, listing chưa map) → xử lý thế nào.
- Đến trễ / out-of-order / trùng → xử lý thế nào.

## 8. Bảo mật & dữ liệu cá nhân
<PII liên quan? Mask? Lưu/xoá? Token? — nhất quán với `08-security-and-privacy.md`.>

## 9. Kiểm thử (nhất quán với `09-process/testing-strategy.md`)
- Unit: ...
- Feature: ...
- Contract (nếu connector): fixtures nào, khẳng định gì.
- FE: ...

## 10. Tiêu chí hoàn thành (Acceptance criteria)
- [ ] ...
- [ ] ...
- [ ] Tài liệu cập nhật (endpoints/jobs/channel/ADR/roadmap nếu áp dụng).

## 11. Câu hỏi mở
- ...
