# ADR-0001: Backend Laravel 11; React SPA nhúng cùng repo; Laravel chỉ phục vụ `/api` + `/webhook` + `/oauth/callback` + catch-all → SPA

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Chủ dự án + team

## Bối cảnh

Cần một ứng dụng quản lý bán hàng đa sàn (đơn/kho/in/đối soát) cho ~100 nhà bán VN. Chủ dự án yêu cầu **PHP/Laravel** ở backend và **React/TypeScript** ở frontend. Câu hỏi: tách 2 repo/2 deploy hay nhúng SPA vào Laravel? Và Laravel phục vụ những route gì?

## Quyết định

- **Một repo**: backend Laravel 11 + frontend React (build bằng Vite, `laravel-vite-plugin`) ở `app/`. Entry `resources/js/app.tsx` mount vào `<div id="app">` trong `resources/views/app.blade.php`.
- Laravel chỉ phục vụ 4 nhóm route:
  - `/api/v1/*` — REST JSON, auth Sanctum SPA (cookie), middleware `tenant`.
  - `/webhook/{provider}` — nhận webhook sàn/ĐVVC; không CSRF, không auth session; verify chữ ký rồi xử lý async.
  - `/oauth/{provider}/callback` — đổi code↔token khi kết nối gian hàng.
  - catch-all `/{any}` (không khớp 3 nhóm trên + asset) → trả `app.blade.php` ⇒ **React Router** quyết định màn hình.
- Auth = **Sanctum SPA cookie** (cùng domain ⇒ không phải quản token bearer; CSRF qua cookie).

## Hệ quả

- Tích cực: một codebase, một pipeline, một deploy; SPA và API luôn cùng version; dùng được toàn bộ hệ sinh thái Laravel (queue/scheduler/Horizon/Sanctum).
- Đánh đổi: SPA và API gắn vào nhau (chấp nhận được ở giai đoạn này); nếu sau cần app mobile/đối tác → API đã versioned (`/api/v1`) nên tách client mới không phá vỡ.
- Liên quan: `01-architecture/overview.md`, `05-api/conventions.md`, `05-api/webhooks-and-oauth.md`, `06-frontend/overview.md`.
