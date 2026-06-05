# Mẫu đối tượng chi tiết (audience templates) — sub-feature J+ — Design

> Lưu & áp **mẫu nhắm mục tiêu chi tiết** (bao gồm/thu hẹp/loại trừ) tái dùng giữa các nháp — mirror hạ tầng mẫu loại trừ geo (F).
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog, chủ uỷ quyền). Làm trên `main`.

## 1. Backend (tenant-scoped, mirror geo_exclusion_templates)
- Migration `audience_templates`: `id`, `tenant_id`(index), `created_by` nullable, `name`, `payload` json, timestamps, `unique(['tenant_id','name'])`.
- `payload` = `{ include:[], narrow:[], exclude:[] }`, mỗi item `{ id, name, type }` (type = khoá flexible_spec/exclusions).
- Model `AudienceTemplate` (BelongsToTenant, cast payload=>array, `@property` docblock).
- Service `AudienceTemplateService` (list/create/delete).
- Request `AudienceTemplateRequest`: `name` ≤120; `payload` present array; mỗi item include/narrow/exclude có `id`/`name`/`type` (string).
- Resource `AudienceTemplateResource`: chuẩn hoá payload luôn có 3 khoá.
- Controller `AudienceTemplateController` (index/store/destroy), Gate `marketing.view`/`marketing.ads.create`.
- Routes group `api/v1/marketing`: `audience-templates` index/store/destroy.

## 2. Frontend (StepAudience)
- Lib `@/lib/adWizard/audienceTemplates.ts`: hooks `useAudienceTemplates`/`useCreateAudienceTemplate`/`useDeleteAudienceTemplate` (mirror exclusionTemplates).
- Khu "Mẫu đối tượng chi tiết": Select **áp mẫu** (gộp include/narrow/exclude vào state hiện tại, khử trùng theo `id`), nút **Lưu thành mẫu** (Modal nhập tên → POST 3 nhóm hiện tại), danh sách mẫu + Popconfirm xoá. Icons @ant-design/icons.

## 3. Bất biến / Testing
- Pass-through không đổi (mẫu chỉ là tiện ích nạp `flexible_spec`/`exclusions`). Bảng mới có `tenant_id` + BelongsToTenant.
- BE: feature test CRUD + reject item thiếu id + cô lập tenant.
- FE: typecheck + lint + build.

## 4. Giới hạn / nối tiếp
- **>2 nhóm thu hẹp** (danh sách động nhiều `flexible_spec`): hiện hỗ trợ 1 nhóm bao gồm + 1 nhóm thu hẹp (đủ cho đa số) → mở rộng UI danh sách nhóm là follow-up.
