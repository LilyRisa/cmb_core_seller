# Advantage+ creative (standard enhancements) — sub-feature K+ — Design

> Bật **Advantage+ creative** (standard enhancements) ở cấp quảng cáo — Meta tự tối ưu nhẹ hình ảnh/văn bản.
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog, chủ uỷ quyền). Làm trên `main`.

## 1. Backend
- `AdSpecDTO` += `bool $standardEnhancements = false`.
- `FacebookAdsConnector::createAd`: khi bật, thêm vào `creative`:
  `degrees_of_freedom_spec.creative_features_spec.standard_enhancements.enroll_status = 'OPT_IN'` (cho cả nhánh page-post lẫn creative mới). Tên field Graph chỉ ở connector.
- `AdDraftSpecMapper::ad`: đọc `node.creative.standard_enhancements` (bool).

## 2. Frontend
- `AdDraftPayload.creative` (⇒ `AdNode.creative`) += `standard_enhancements?: boolean`.
- StepCreative: Switch "Advantage+ — Tối ưu nội dung tự động" ghi `creative.standard_enhancements`.

## 3. Bất biến / Testing
- Pass-through node→DTO→creative. Không bảng mới.
- BE: createAd thêm/bỏ `degrees_of_freedom_spec`.
- FE: typecheck + lint + build.

## 4. Giới hạn / nối tiếp
- **Advantage+ Shopping Campaign** (loại chiến dịch riêng, cần catalog) — follow-up lớn, chưa làm.
