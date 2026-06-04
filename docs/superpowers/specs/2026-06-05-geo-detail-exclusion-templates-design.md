# Geo chi tiết + Mẫu loại trừ (exclusion templates) — sub-feature F — Design

> Nhắm theo vùng/thành phố (không chỉ quốc gia), loại trừ địa điểm, và lưu/áp “mẫu loại trừ” tái sử dụng.
> Ngày: 2026-06-05 · Trạng thái: approved (chủ uỷ quyền, bám roadmap). Làm trên `main`. Nối tiếp E.

## 1. Vấn đề hiện tại
- `StepAudience` chỉ có `<Select>` 6 quốc gia cứng → ghi `targeting.geo_locations.countries`. Không có vùng/thành phố, không có loại trừ (`excluded_geo_locations`).
- `targeting` là **pass-through trong suốt**: FE `buildTargetingSpec` → `node.targeting` → mapper copy nguyên (`(array)$node['targeting']`) → connector JSON-encode. ⇒ Thêm geo chi tiết/loại trừ **không cần đổi DTO/mapper/validation**; chỉ đụng FE builder + connector geo-search.
- Chưa có hạ tầng “template/preset” nào → mẫu loại trừ là net-new (mirror `ad_drafts`: tenant + JSON payload).

## 2. Geo search ở connector (mở rộng, không phá vỡ)
- Route + contract đã chuyển tiếp tham số `type` sẵn (`GET ad-accounts/{id}/targeting-search?q=&type=`). `searchTargeting` hiện chỉ map `adinterest|adbehavior`.
- Thêm nhánh `type === 'adgeolocation'`: gọi Graph `/search?type=adgeolocation&q=&location_types=["country","region","city"]&limit=...`, map mỗi kết quả → `TargetingOptionDTO`:
  - `id` = `key` (geo key của FB), `name` = tên hiển thị (kèm hậu tố vùng/quốc gia nếu có), `type` = `country|region|city`, `raw` = payload gốc (giữ `country_code`).
  - Không thêm field DTO mới — tái dùng `id`/`type`/`raw`.
- Giữ “core không biết sàn”: tên field Graph (`adgeolocation`, `location_types`) chỉ ở connector.

## 3. Frontend — geo include/exclude (StepAudience)
Lưu metadata FE trên node: `geo?: { include: GeoItem[]; exclude: GeoItem[] }` với `GeoItem = { key, name, type: 'country'|'region'|'city', country_code? }`. (Mapper **bỏ qua** `node.geo`; nó chỉ đọc `node.targeting`.)
- Hai ô tìm kiếm (AntD `Select` showSearch async, dùng hook geo-search `type=adgeolocation`): **Bao gồm** và **Loại trừ**. (Select tìm kiếm hợp lệ — tập lớn động, không áp dụng luật “tránh Select cho tập nhỏ”.)
- Mỗi lần đổi `geo`, **dẫn xuất** vào `targeting` (gộp với age/genders/interests sẵn có):
  - `geo_locations`: gom `include` theo type → `countries` (country_code||key), `regions: [{key}]`, `cities: [{key, radius: 25, distance_unit: 'kilometer'}]`.
  - `excluded_geo_locations`: gom `exclude` tương tự. Nếu rỗng → bỏ key khỏi targeting.
  - Thành phố mặc định bán kính 25km (UI bán kính/đơn vị tuỳ chỉnh là follow-up).
- Khi tải lại draft: render pickers từ `node.geo` (có sẵn name). Tương thích ngược: nếu chỉ có `targeting.geo_locations.countries` cũ mà chưa có `node.geo` → seed `include` = các country đó (name=mã).
- Icons @ant-design/icons. Không emoji.

## 4. Mẫu loại trừ (backend, net-new, tenant-scoped)
Mirror `ad_drafts`.
- Migration `geo_exclusion_templates`: `id`, `tenant_id` (index), `created_by` nullable, `name`, `payload` json (mảng `GeoItem[]` đã loại trừ), timestamps; `unique(['tenant_id','name'])`.
- Model `GeoExclusionTemplate` (Marketing/Models): `use BelongsToTenant`; `$fillable` gồm `tenant_id, created_by, name, payload`; cast `payload => array`.
- Service `GeoExclusionTemplateService`: `list()`, `create(userId, name, payload)`, `delete(id)` (model global scope lo tenant).
- Resource `GeoExclusionTemplateResource`: `{ id, name, payload, created_at }`.
- Request `GeoExclusionTemplateRequest`: `name` required string ≤120; `payload` array (each item `key` string, `name` string, `type` in country|region|city).
- Controller `GeoExclusionTemplateController` (index/store/destroy), Gate `marketing.view` (read) / `marketing.ads.create` (write).
- Routes (trong group `api/v1/marketing` sẵn có): `GET/POST exclusion-templates`, `DELETE exclusion-templates/{template}`.

## 5. Frontend — lưu & áp mẫu loại trừ (StepAudience)
- Lib `@/lib/adWizard/exclusionTemplates.ts`: TanStack Query hooks `useExclusionTemplates`, `useCreateExclusionTemplate`, `useDeleteExclusionTemplate` qua `lib/api.ts`.
- Khu “Loại trừ”: nút **Lưu thành mẫu** (mở modal nhập tên → POST payload = `geo.exclude`), `<Select>` **Áp mẫu** (chọn → nối các item vào `geo.exclude`, khử trùng theo `key`), nút xoá mẫu.
- Icons @ant-design/icons. Không emoji.

## 6. Không đụng / Bất biến
- DTO/mapper/connector createAdSet: **không đổi** (targeting vẫn pass-through; geo nằm trong targeting do FE dựng). Chỉ thêm nhánh search ở connector.
- Mọi bảng có `tenant_id` + `BelongsToTenant`. Controller mỏng → service → resource. Tiền = VND integer (không liên quan ở đây).

## 7. Testing
- BE connector: `searchTargeting('adgeolocation', ...)` gọi đúng URL (`type=adgeolocation`, `location_types`) + map type country/region/city. (Http::fake, không DB.)
- BE templates: feature test CRUD (tenant header + actingAs owner): tạo → list thấy; unique tên cùng tenant; xoá; cô lập tenant (tenant khác không thấy).
- FE: typecheck + lint + build.

## 8. Build sequence
1. Connector geo-search (`adgeolocation`) + test.
2. Backend exclusion-templates (migration/model/service/resource/request/controller/routes) + feature test.
3. FE StepAudience geo include/exclude (derive geo_locations/excluded_geo_locations) + tương thích ngược.
4. FE lưu/áp mẫu loại trừ (hooks + UI).

## 9. Giới hạn / nối tiếp
- Bán kính/đơn vị per-thành-phố tuỳ chỉnh, “vị trí gần đây” (custom_locations lat/lng), zips → follow-up.
- Mẫu chỉ cho **loại trừ** (đúng ask). Mẫu “đối tượng đầy đủ” (interests/age) là feature riêng sau.
