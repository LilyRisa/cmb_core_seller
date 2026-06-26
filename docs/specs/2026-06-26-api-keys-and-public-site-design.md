# SPEC: API key bên thứ 3 + Public marketing site + Tài liệu API

- **Trạng thái:** Draft
- **Phase:** Tenancy/Auth (API keys) + Frontend (public site) + Docs
- **Module backend liên quan:** Tenancy (token + tenant resolution), Billing (Plan cho pricing), Settings (UI)
- **Tác giả / Ngày:** 2026-06-26
- **Liên quan:** `05-api/conventions.md`, `05-api/endpoints.md`, Sanctum PAT (`personal_access_tokens`), `EnsureTenant` middleware, `Role` enum, SPEC 0031 (custom roles)

## 1. Vấn đề & mục tiêu

1. **API key cho bên thứ 3:** chủ gian hàng (owner) tạo access token để tích hợp ngoài, **chỉ định thời hạn**, **tạo/xem/xóa được**; key thao tác **toàn quyền như trên web**. **Chỉ owner** — nhân viên KHÔNG tạo, KHÔNG xem, KHÔNG xóa.
2. **Tái cấu trúc public:** route gốc `/` thành site giới thiệu (thông tin phần mềm, bảng giá, phần mềm phụ trợ gộp 1 menu header, tài liệu API); **dashboard chuyển sang `/dashboard`**.
3. **Trang tài liệu API** biên soạn riêng cho bên thứ 3.

## 2. Trong / ngoài phạm vi

- **Trong:**
  - API key = Sanctum PAT gắn `tenant_id`, abilities `['*']`, có `expires_at`; owner CRUD (tạo/list/**xóa**), gate `api_keys.manage` (owner-only).
  - Middleware resolve tenant từ token (ép theo token nếu có `tenant_id`).
  - Public pages tại `/` (Home), `/pricing`, `/tools`, `/api-docs` + `PublicLayout`/header; dời dashboard → `/dashboard`.
  - Endpoint công khai `GET /api/v1/public/plans` cho pricing.
  - Trang tài liệu API biên soạn (markdown riêng).
- **Ngoài (làm sau):**
  - Scope quyền hẹp cho key (giờ full `['*']`).
  - Tách toàn bộ route nội bộ sang `/app/*` (giữ nguyên route hiện tại; chỉ dời dashboard index).
  - Tự thu hồi key khi owner đổi/ rời shop (chỉ xóa thủ công).
  - OpenAPI generated spec.

## 3. Luồng chính

**API key:** Owner → Settings → "API & Tích hợp" → "Tạo API key" (tên + thời hạn) → hệ thống trả **token plaintext 1 lần** (copy ngay) → bên thứ 3 gọi `Authorization: Bearer <token>` (không cần `X-Tenant-Id`) → thao tác như web trong đúng shop. Owner xem danh sách (4 ký tự cuối, thời hạn, lần dùng cuối), **xóa/thu hồi** key bất kỳ.

**Public site:** Khách vào `/` thấy trang giới thiệu + header menu (Trang chủ, Bảng giá, Tài liệu API, Phần mềm phụ trợ↓, Đăng nhập). Đăng nhập rồi → nút "Vào ứng dụng" (/dashboard). Dashboard ở `/dashboard`; các nghiệp vụ /orders, /settings... giữ nguyên.

## 4. Hành vi & quy tắc nghiệp vụ

- **Token:** `tokenable` = user owner tạo key; `abilities=['*']`; `tenant_id` = shop hiện tại; `expires_at` từ input (nullable = không hết hạn, nhưng UI khuyến khích đặt). Tạo qua `createToken(name, ['*'], $expiresAt)` rồi set `tenant_id`.
- **Tenant từ token:** `EnsureTenant` ưu tiên: nếu access token hiện tại có `tenant_id` ⇒ dùng `tenant_id` đó (BỎ QUA header `X-Tenant-Id` để khóa key theo shop); nếu không (cookie SPA / mobile token) ⇒ giữ luồng cũ (header → query → session). Vẫn validate user là thành viên tenant.
- **Quyền như web:** key thuộc user owner ⇒ `CurrentTenant::can()` trả `['*']`; abilities `['*']` qua mọi `abilities:*` middleware.
- **Owner-only:** permission `api_keys.manage`. `Role::Owner` = `['*']` (có); `Role::Admin` thêm deny `!api_keys.manage`; staff/viewer không có `*`. Mọi endpoint (list/create/delete) `abort_unless(can('api_keys.manage'))` ⇒ nhân viên 403 (không tạo/xem/xóa).
- **Xóa key:** `DELETE /tenant/api-keys/{id}` xóa token row (thu hồi tức thì; request sau bằng key đó → 401). Chỉ xóa key thuộc tenant hiện tại (scope theo tenant_id + tránh xóa nhầm token mobile/extension — chỉ liệt kê/ xóa token có `tenant_id` của shop & là loại "api_key").
- **Phân loại token:** đánh dấu key bên-thứ-3 để KHÔNG lẫn token mobile/extension trong UI — dùng `name` prefix hoặc cột `tenant_id IS NOT NULL` + abilities `['*']`; an toàn hơn: thêm cờ `kind='api_key'` (xem §5).

## 5. Dữ liệu

- Migration thêm vào `personal_access_tokens`: `tenant_id` (unsignedBigInteger, nullable, index), `kind` (string nullable — `'api_key'` cho key bên thứ 3; mobile/extension để null). Reversible.
- Không bảng mới. Đọc `Plan` (Billing) cho pricing.
- Không event mới.

## 6. API & UI

- **`GET /api/v1/tenant/api-keys`** (auth+tenant, `api_keys.manage`): `{ data:[{ id, name, last_four, abilities, expires_at, last_used_at, created_at }] }`.
- **`POST /api/v1/tenant/api-keys`** `{ name, expires_at? }` → `{ data:{ id, name, token (plaintext, 1 lần), expires_at } }`.
- **`DELETE /api/v1/tenant/api-keys/{id}`** → 204/`{data:{deleted:true}}`. Chỉ token `kind='api_key'` + `tenant_id`=shop hiện tại.
- **`GET /api/v1/public/plans`** (KHÔNG auth): `{ data:[{ code, name, description, price_monthly, price_yearly, currency, features, limits }] }` — plan `is_active`, sort theo `sort_order`.
- Cập nhật `05-api/endpoints.md`.
- **FE — public:** `PublicLayout` (PublicHeader menu + PublicFooter); pages `HomePage`, `PricingPage`, `ToolsPage` (gộp Chrome extension + app mobile, tái dùng nội dung `DownloadAppPage`), `ApiDocsPage`. Route trong `app.tsx` đặt TRƯỚC catch-all auth.
- **FE — dashboard dời:** `appRoutes.tsx` index `/` → chuyển DashboardPage sang path `/dashboard`; index redirect `/dashboard`; `AppLayout` nav "Bảng điều khiển" → `/dashboard`. Route nội bộ khác KHÔNG đổi.
- **FE — Settings API keys:** thêm mục "API & Tích hợp" vào `SettingsLayout` (chỉ hiện khi `useCan('api_keys.manage')`); trang `SettingsApiKeysPage` (list + modal tạo + hiện token 1 lần + nút Xóa có confirm). Icon `@ant-design/icons` (ApiOutlined), tránh `<Select>` (dùng Radio/Segmented cho preset thời hạn: 30/90/365 ngày/không hết hạn + custom DatePicker).

## 7. Edge case & lỗi

- Key hết hạn → Sanctum 401 tự động. Xóa key → 401 ngay lần gọi sau.
- Gửi `X-Tenant-Id` khác với tenant của token → bỏ qua header, dùng tenant của token (khóa shop).
- Nhân viên gọi endpoint api-keys → 403 (gate). Owner của shop khác → tenant scope chặn (chỉ thấy/xóa key shop mình).
- Token mobile/extension (không `tenant_id`/`kind`) KHÔNG xuất hiện trong UI api-keys & không bị xóa nhầm.
- Khách chưa đăng nhập vào `/dashboard` hay route nội bộ → redirect login (giữ nguyên RequireAuth).
- Đăng nhập rồi vào `/` → hiện Home + nút "Vào ứng dụng" (không ép redirect).
- `GET /public/plans` không lộ dữ liệu tenant (chỉ catalog Plan).

## 8. Bảo mật & dữ liệu cá nhân

- Token plaintext chỉ trả **1 lần** lúc tạo; sau đó chỉ lưu hash (Sanctum) + hiện `last_four`. Không log token.
- Key full quyền ⇒ cảnh báo UI "giữ bí mật như mật khẩu". Khuyến khích đặt thời hạn.
- Public pages không yêu cầu auth; không lộ dữ liệu nội bộ; tài liệu API là subset biên soạn.

## 9. Kiểm thử

- **Feature (BE):** owner tạo key → token dùng được + ép đúng tenant (Bearer không cần header); nhân viên tạo/list/xóa → 403; key hết hạn → 401; xóa key → 401 sau đó; key tenant A không thao tác được tenant B; `public/plans` trả plan active không cần auth.
- **FE:** mục Settings api-keys ẩn với staff; tạo hiện token 1 lần; xóa có confirm; public routes render không cần auth; dashboard ở /dashboard; logged-in thấy "Vào ứng dụng".
- Theo memory `test-verify-baseline`: chạy test liên quan Tenancy/Billing/Settings.

## 10. Tiêu chí hoàn thành

- [ ] Migration `tenant_id` + `kind` trên personal_access_tokens (reversible).
- [ ] `EnsureTenant` ép tenant theo token khi có `tenant_id`.
- [ ] Endpoints api-keys (list/create/**delete**) owner-only + permission `api_keys.manage` (Admin deny).
- [ ] `GET /public/plans`.
- [ ] FE Settings api-keys (owner-only, tạo/list/xóa, token 1 lần).
- [ ] FE PublicLayout + Home/Pricing/Tools/ApiDocs + header menu; dashboard → /dashboard.
- [ ] Trang tài liệu API biên soạn (`resources/docs/api-public.md` render).
- [ ] Test BE/FE pass; pint/phpstan/lint/typecheck/build xanh.
- [ ] Docs: `05-api/endpoints.md` cập nhật.

## 11. Câu hỏi mở

- Route tên `/tools` vs `/extensions` cho trang phần mềm phụ trợ (đề xuất `/tools`).
- Trang Home/About: gộp làm 1 (đề xuất gộp — Home chứa thông tin phần mềm).
- Thư viện render markdown cho ApiDocsPage (kiểm `react-markdown` có sẵn; nếu không → render cấu trúc React).
