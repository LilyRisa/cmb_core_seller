# Thử nghiệm A/B (split test) — sub-feature L — Design (v1)

> Tạo biến thể A/B cho **nhóm quảng cáo**, khác nhau ở **một biến số** (nội dung / đối tượng / vị trí), gắn nhãn nhóm thử nghiệm, so sánh chỉ số ở báo cáo.
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog, chủ uỷ quyền). FE-only v1, làm trên `main`.

## 1. Phạm vi v1 (thực dụng, tận dụng clone — H)
- A/B ở **cấp nhóm quảng cáo**: từ nhóm đang chọn, tạo 2 biến thể `[A]`/`[B]` (clone), cùng cấu hình trừ **biến số** người dùng chọn — để chỉnh khác nhau ở đúng một yếu tố.
- **Biến số**: `creative` (Nội dung) / `audience` (Đối tượng) / `placement` (Vị trí).
- Gắn metadata `experiment: { id, variable }` lên cả hai biến thể (FE-only — KHÔNG gửi Graph; mapper bỏ qua key lạ). Hai biến thể xuất bản như 2 nhóm quảng cáo bình thường ⇒ **so sánh chỉ số ở báo cáo** (dashboard đã hỗ trợ lọc/so theo nhóm).
- **Ngoài phạm vi v1** (ghi follow-up): Meta **ad_study/split-test API** chính thức (study/experiment cell, tự tuyên bố người thắng, chia ngân sách điều khiển bởi Meta); A/B cấp **chiến dịch** và **quảng cáo (creative/danh mục)**.

## 2. Store (`draftStore.ts`)
- `AdSetNode.experiment?: AbExperiment` (`{ id: string; variable: 'creative'|'audience'|'placement' }`).
- Action `createAbTest(adsetKey, variable)`:
  - lấy nhóm gốc, sinh `experimentId = nextKey('exp')`, `baseName` (bỏ hậu tố `[A]/[B]` cũ).
  - `a = {...src, name: baseName+' [A]', experiment}`; `b = {...cloneAdSetNode(src,''), name: baseName+' [B]', experiment}` (clone: key mới, ad key mới, `external_id=null`).
  - chèn `[a, b]` thay cho gốc; chọn `b`. Qua `mergeTree` ⇒ autosave.

## 3. UI (`AdSetSelector`)
- Nút **"A/B Test"** (ExperimentOutlined) mở Dropdown: `Segmented` chọn biến số + giải thích + nút "Tạo biến thể A/B" → `createAbTest`.
- Nhóm thuộc thử nghiệm hiển thị icon ExperimentOutlined (tím) + tooltip "A/B test theo {biến số}".
- Icons @ant-design/icons; không emoji.

## 4. Bất biến / không đụng
- Pass-through: `experiment` là metadata FE; `AdDraftSpecMapper::adSet` chỉ đọc các key đã biết ⇒ bỏ qua. Publish tạo 2 nhóm độc lập (idempotent ở backend). Không DTO/endpoint/bảng mới.

## 5. Testing
- FE: typecheck + lint + build. (Logic store thuần; không có test runner JS.)

## 6. Nối tiếp
- Tích hợp **ad_study (split test) API** của Meta: tạo study + experiment cells, Meta điều phối ngân sách & tuyên bố winner; báo cáo experiment riêng.
- A/B cấp chiến dịch & cấp quảng cáo (biến thể creative / danh mục).
- Bảng so sánh A/B chuyên dụng (gom theo `experiment.id`) trong dashboard.
