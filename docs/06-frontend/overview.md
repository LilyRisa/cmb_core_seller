# Frontend — React nhúng trong Laravel

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. Mô hình nhúng
- React build bằng **Vite** (`laravel-vite-plugin`). Entry: `resources/js/app.tsx` mount vào `<div id="app">` trong `resources/views/app.blade.php`.
- **Routing:** Laravel `web.php` có catch-all `Route::get('/{any?}', fn() => view('app'))->where('any', '^(?!api|webhook|oauth|build|storage|sanctum).*$')` ⇒ mọi URL không phải `/api`, `/webhook`, `/oauth`, asset → trả `app.blade.php` ⇒ **React Router** quyết định màn hình.
- Dev: `vite` HMR; Prod: `npm run build` ⇒ `public/build` ⇒ blade dùng `@vite`.
- Auth flow: SPA gọi `GET /sanctum/csrf-cookie` rồi `POST /api/v1/auth/login`; sau đó mọi request qua Axios `withCredentials:true` + tự gắn `X-XSRF-TOKEN`. Chưa đăng nhập ⇒ React Router đẩy về `/login`.

## 2. Stack frontend (xem `tech-stack.md`)
React 18 + TypeScript · Vite · **Ant Design 5** (UI) · **TanStack Query** (server state) · **Zustand** (UI/local state) · **React Router v6** · **React Hook Form + zod** (form/validate) · Axios · i18n tiếng Việt mặc định (kết cấu để thêm ngôn ngữ).

## 3. Cấu trúc thư mục (`resources/js/`)
```
resources/js/
├── app.tsx                  // bootstrap: QueryClientProvider, AntD ConfigProvider, Router, AuthGate
├── routes.tsx               // khai báo route
├── lib/
│   ├── api.ts               // axios instance (baseURL /api/v1, withCredentials, interceptor lỗi → toast/redirect)
│   ├── queryClient.ts       // TanStack Query config
│   └── auth.ts              // useAuth, login/logout, current tenant
├── stores/                  // zustand stores (uiStore, tenantStore...)
├── components/              // dùng chung (DataTable wrapper AntD, StatusTag, MoneyText, DateText, ...)
├── features/                // ★ theo domain — KHỚP với module backend
│   ├── auth/                (Login, Register, ForgotPassword, OnboardingCreateTenant)
│   ├── dashboard/
│   ├── channels/            (danh sách gian hàng, nút Kết nối, trạng thái token, callback success)
│   ├── orders/              (list + filter + detail + đổi trạng thái + bulk + tạo đơn tay + gộp/tách)
│   ├── inventory/           (tồn theo kho, điều chỉnh, lịch sử movements, kiểm kê)
│   ├── products/            (sản phẩm/SKU, listing, ghép SKU, đăng bán đa sàn)
│   ├── fulfillment/         (vận đơn, in hàng loạt, template in, màn quét đóng gói, pickup batch)
│   ├── procurement/         (NCC, PO)
│   ├── finance/             (đối soát, lợi nhuận)
│   ├── reports/
│   ├── settings/            (thành viên & phân quyền, automation rules, thông báo)
│   └── billing/             (gói, hoá đơn, nâng cấp)
├── hooks/                   // hook chung (useDebounce, useTableQuery, ...)
└── types/                   // TS types — ưu tiên generate/đồng bộ với backend Resource
```

## 4. Quy ước (RULES)
1. **`features/*` khớp 1-1 với `app/Modules/*` backend** — dễ tìm "UI của tính năng X ở đâu".
2. **Mọi gọi API qua TanStack Query** (`useQuery`/`useMutation`), không gọi axios rải rác trong component. Query key có pattern: `['orders', filters]`, `['order', id]`...
3. **Không giữ server state trong Zustand** — Zustand chỉ cho UI state (sidebar, modal, filter tạm, current tenant). Server data luôn từ React Query (có cache/invalidate).
4. **Component "dumb" + hook "smart"**: logic gọi API/biến đổi dữ liệu nằm trong hook (`features/orders/hooks/useOrders.ts`), component chỉ render.
5. **Form**: React Hook Form + zod schema; schema đặt cạnh form; submit gọi mutation.
6. **Bảng dữ liệu**: dùng wrapper `<DataTable>` (bọc AntD Table) chuẩn hoá phân trang/lọc/sort khớp với quy ước API; URL phản ánh filter (query string) để chia sẻ/đặt lại được.
7. **Hiển thị thống nhất**: `<StatusTag status=.../>` (màu theo mã chuẩn), `<MoneyText value=... />` (format VND), `<DateText value=... />` (timezone VN). Không tự format rải rác.
8. **i18n**: chuỗi hiển thị qua hệ i18n (mặc định `vi`), không hard-code chuỗi tiếng Việt rải rác (cho phép giai đoạn đầu nhưng gom dần).
9. **Xử lý lỗi**: interceptor đọc envelope `{error:{code,message}}` ⇒ toast `message`; `401` ⇒ về `/login`; `403` ⇒ trang "không đủ quyền"; `429` ⇒ thông báo thử lại; lỗi field (`422`) ⇒ gắn vào form.
10. **Phân quyền UI**: hook `useCan('orders.update')` ẩn/disable nút theo role; nhưng **không tin client** — backend luôn kiểm policy.
11. **Realtime (Phase sau)**: Laravel Echo + Reverb cho "đơn mới", "tiến độ in xong"; cập nhật cache React Query khi nhận event.
12. **Icon = font icon, KHÔNG dùng ký tự emoji/biểu tượng**: mọi icon trong UI phải dùng `@ant-design/icons` (`<WarningOutlined/>`, `<PrinterOutlined/>`, `<CheckCircleOutlined/>`…) — không nhúng ký tự `📦 🖨 🚚 🏷 ⚠️ ✓ ✗` vào label/text (không nhất quán giữa OS/font, không scale theo `fontSize`, không đổi màu theo theme). (Ký tự đặc biệt như tiền tệ `₫`, dấu `· − ×` thì được.)
13. **Hạn chế `<Select>`**: ưu tiên `Radio.Group`/`Segmented`/`Tag.CheckableTag`/nút bấm cho lựa chọn ít phương án; chỉ dùng `Select` khi danh sách dài/động.

## 5. Trang chính (sitemap rút gọn)
`/login` `/register` `/forgot-password` · `/onboarding` (tạo tenant + kết nối gian hàng đầu) · `/` Dashboard · `/orders` (list — panel "Lọc" chip rows, xem [`orders-filter-panel.md`](orders-filter-panel.md)) `/orders/:id` `/orders/new` (tạo tay) · `/channels` · `/products` `/products/skus` `/products/listings` `/products/sku-mapping` · `/inventory` `/inventory/:sku` `/inventory/stocktakes` `/warehouses` · `/fulfillment/shipments` `/fulfillment/print` `/fulfillment/templates` `/fulfillment/scan` (quét đóng gói) `/fulfillment/pickups` · `/procurement/suppliers` `/procurement/pos` · `/finance/settlements` `/finance/profit` · `/reports` · `/settings/profile` `/settings/workspace` `/settings/plan` *(Phase 6.4 — SPEC-0018: gói thuê bao + nâng cấp + hoá đơn)* `/settings/members` `/settings/carriers` `/settings/orders` `/settings/print` `/settings/automations` `/settings/notifications` · `/sync-logs` (nhật ký đồng bộ).

> **Pattern "panel Lọc + chip rows"** (kiểu BigSeller): các facet dạng danh sách hiển thị chip có số lượng (component `FilterChipRow`), state lọc trong query string, đếm từ `/orders/stats` faceted. Recipe thêm facet mới + ngoài-phạm-vi: [`orders-filter-panel.md`](orders-filter-panel.md).
