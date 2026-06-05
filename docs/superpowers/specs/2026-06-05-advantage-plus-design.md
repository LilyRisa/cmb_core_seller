# Advantage+ (Meta Advantage+) — sub-feature K — Design

> Bật tuỳ chọn Advantage+ của Meta: **Advantage+ Audience** (tự mở rộng đối tượng) và làm rõ **Advantage+ Placements** (đã có ở vị trí tự động).
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog, chủ uỷ quyền). FE-only (pass-through), làm trên `main`.

## 1. Advantage+ Audience (mới)
- Toggle ở bước Đối tượng (StepAudience). Khi bật ⇒ FE thêm vào `targeting`:
  `targeting_automation: { advantage_audience: 1 }` (generic, nằm trong targeting pass-through).
- Các tiêu chí thủ công (vị trí/tuổi/giới tính/chi tiết) vẫn gửi — Meta dùng làm gợi ý và mở rộng. Không vô hiệu hoá trường nào (đúng hành vi Advantage+ Audience).
- `initFromTargeting` đọc lại `targeting.targeting_automation.advantage_audience === 1`. `buildTargetingSpec` thêm khoá khi bật; bỏ khi tắt. Thêm vào deps effect.

## 2. Advantage+ Placements (làm rõ)
- "Vị trí tự động" (E, `placement_config.automatic = true`) chính là Advantage+ Placements. Đổi nhãn Segmented ở StepPlacements → **"Advantage+ (tự động — khuyến nghị)"**. Không đổi logic/spec.

## 3. Bất biến / không đụng
- Pass-through: `targeting_automation` nằm trong `targeting` → mapper/connector.createAdSet không đổi. Tên field Graph chỉ ở lớp FE dựng spec.
- FE-only; không bảng/endpoint mới.

## 4. Testing
- FE: typecheck + lint + build. (Logic dựng spec thuần; không có test runner JS.)

## 5. Giới hạn / nối tiếp
- **Advantage+ Shopping Campaign** (campaign type riêng) → follow-up xa.
- Các Advantage+ creative (enhancements) → follow-up.
