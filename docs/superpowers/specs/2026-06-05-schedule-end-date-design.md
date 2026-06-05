# Lịch chạy: thêm ngày kết thúc (end_time) — sub-feature I — Design

> Bổ sung **ngày kết thúc** tuỳ chọn cho nhóm quảng cáo (như Ads Manager), bên cạnh ngày bắt đầu đã có.
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog, chủ uỷ quyền). Làm trên `main`.

## 1. Dòng dữ liệu (pass-through, mở rộng tối thiểu)
`schedule.end_time` ở node nháp → `AdDraftSpecMapper::adSet` → `AdSetSpecDTO.endTime` → `FacebookAdsConnector::createAdSet` → param Graph `end_time`. Song song với `start_time` đã có.

## 2. Backend
- `AdSetSpecDTO`: thêm `public ?string $endTime = null` (cuối danh sách param, có default ⇒ không vỡ caller named-arg). ISO-8601; null = chạy đến khi dừng.
- `FacebookAdsConnector::createAdSet`: `if ($spec->endTime !== null) $params['end_time'] = $spec->endTime;` (chỉ gửi khi có).
- `AdDraftSpecMapper::adSet`: `endTime: isset($schedule['end_time']) ? (string) $schedule['end_time'] : null`.
- Tên field Graph `end_time` chỉ ở connector.

## 3. Frontend (StepBudget)
- Kiểu `AdSetNode.schedule` (+ payload phẳng): thêm `end_time?: string | null`.
- Gộp patch lịch (`patchSchedule`) để set start/end không ghi đè nhau (trước đây `handleStartTimeChange` thay cả object).
- Thêm DatePicker **"Ngày kết thúc (tuỳ chọn)"** ngay dưới "Bắt đầu chạy" (trong cả nhánh ngân sách chiến dịch và nhóm — tách hàm `renderSchedule(adset)` dùng chung).
  - `disabledDate`: chặn ngày trước thời điểm bắt đầu (nếu đã chọn bắt đầu).
  - Để trống = chạy liên tục. Icons @ant-design/icons; không emoji.

## 4. Bất biến / không đụng
- Pass-through; không bảng mới; tiền VND không liên quan.
- Lưu ý (ngoài phạm vi): ngân sách trọn đời (lifetime) yêu cầu end_time bắt buộc — sẽ xử lý khi mở lifetime budget.

## 5. Testing
- BE connector: gửi `end_time` khi set; bỏ khi null.
- BE mapper: map `schedule.end_time` → `endTime`; null khi vắng.
- FE: typecheck + lint + build.
