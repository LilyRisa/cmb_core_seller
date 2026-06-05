# Nhắm mục tiêu chi tiết (detailed targeting) — sub-feature J — Design

> Thêm nhắm mục tiêu chi tiết đầy đủ: tìm & thêm **sở thích / hành vi / nhân khẩu học**, **thu hẹp** (AND) và **loại trừ** — bên cạnh vị trí/tuổi/giới tính đã có.
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog, chủ uỷ quyền). Làm trên `main`.

## 1. Nguyên tắc
- `targeting` là **pass-through trong suốt**: FE dựng `flexible_spec` + `exclusions` vào `targeting` → mapper copy nguyên → connector JSON-encode. ⇒ Không đổi DTO/mapper/connector `createAdSet`; chỉ thêm **một nhánh tìm kiếm** ở connector + làm lại UI Đối tượng.
- "core không biết sàn": tên type Graph (`adTargetingCategory`, `flexible_spec`, `exclusions`) chỉ ở connector / lớp FE dựng spec.

## 2. Connector — tìm kiếm chi tiết hợp nhất
`searchTargeting` thêm nhánh `type === 'adTargetingCategory'`: trả mỗi kết quả với **type riêng của nó** (`$o['type']` — vd `interests`, `behaviors`, `family_statuses`, `industries`…) làm `TargetingOptionDTO.type`, để FE gom vào đúng khoá `flexible_spec`/`exclusions`. Nhánh `adinterest`/`adbehavior`/`adgeolocation` cũ giữ nguyên.

## 3. Frontend (StepAudience)
- Kiểu `DetailedItem = { id; name; type }` (type = khoá flexible_spec). Bảng nhãn VN cho các type phổ biến.
- 3 ô tìm kiếm (Select async, `type='adTargetingCategory'`, dùng chung 1 nguồn kết quả + ref map id→item):
  - **Nhắm mục tiêu chi tiết** (bao gồm) → nhóm `flexible_spec[0]`.
  - **Thu hẹp đối tượng (VÀ)** → nhóm `flexible_spec[1]` (AND với nhóm bao gồm).
  - **Loại trừ đối tượng** → `exclusions`.
- `groupDetailed(items)`: gom theo `type` → `{ interests:[{id,name}], behaviors:[…], … }`.
- `buildTargetingSpec`: `flexible_spec = [includeGroup, narrowGroup].filter(nonEmpty)`; `exclusions = excludeGroup` nếu có. Bỏ key khi rỗng.
- `flattenGroup(obj)` + `initFromTargeting`: tải lại từ `targeting.flexible_spec[0|1]` và `targeting.exclusions` (mỗi item đã có `{id,name}` do FB trả; type = khoá nhóm) ⇒ render lại đúng. Tương thích ngược: draft cũ chỉ có `flexible_spec[0].interests` → vẫn hiển thị ở ô "bao gồm".
- Select tìm kiếm async hợp lệ (tập lớn động) — không áp luật tránh Select. Icons @ant-design/icons.

## 4. Bất biến / không đụng
- DTO/mapper/connector.createAdSet không đổi (geo + age + flexible_spec + exclusions đều nằm trong `targeting` pass-through). Audience-estimate tự nhận spec mới.

## 5. Testing
- BE connector: `searchTargeting(type='adTargetingCategory')` map per-result type (interests/behaviors/family_statuses) + audience_size.
- FE: typecheck + lint + build.

## 6. Giới hạn / nối tiếp
- Mẫu "đối tượng chi tiết" tái dùng (như mẫu loại trừ geo) → follow-up.
- Nhiều nhóm thu hẹp (>2 flexible_spec) → hiện hỗ trợ 1 nhóm bao gồm + 1 nhóm thu hẹp; mở rộng sau nếu cần.
