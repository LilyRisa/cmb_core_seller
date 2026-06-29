# Đồng bộ giao diện public theo landing "CMBcore Seller" — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`).

**Goal:** Port landing page "CMBcore Seller" (từ `D:\work\cmb_core_company`) thành trang gốc `/` React, và **đồng bộ toàn bộ trang public** (Bảng giá, Tài liệu API, Tools, Download) theo cùng chrome + design-system: header frosted + footer tối + màu/font của landing; thân từng trang giữ nội dung nhưng style lại cho khớp.

**Architecture:** Một **design-system CSS dùng chung** (port từ CSS landing), scope dưới wrapper `.cmb-public` để KHÔNG rò rỉ vào app đăng nhập (AntD). Một `PublicLayout` mới bọc mọi trang public trong `.cmb-public` + header/footer mới. Landing `/` chỉ render phần thân (header/footer do layout lo). Các trang còn lại style lại bằng các primitive dùng chung (container/section/card/btn/heading). JS landing (scroll-reveal + count-up) → `useEffect`.

**Tech Stack:** React 18 + React Router + Vite + TypeScript. CSS thuần (design-system port). Ant Design vẫn dùng cho app đăng nhập — KHÔNG đụng.

## Nguồn (đọc, KHÔNG sửa dự án company)
- `D:\work\cmb_core_company\resources\views\front\seller.blade.php` (~1410 dòng: `<style>` ~48–510, body, `<script>` ~1377–1407).
- `D:\work\cmb_core_company\resources\views\front\partials\landing-header.blade.php` (header partial + JS).

## Global Constraints
- Lệnh Node từ `app/`. Verify mỗi task FE: `npm run lint && npm run typecheck && npm run build`.
- Chuỗi tiếng Việt; định danh tiếng Anh.
- **CSS scope dưới `.cmb-public`** — TUYỆT ĐỐI không để selector global trần lọt ra: `:root{--x}`→`.cmb-public{--x}`; `body{...}`→`.cmb-public{...}`; `*`/`*,*::before,*::after`→`.cmb-public *,.cmb-public *::before,...`; mọi `.hero`/`.btn`/`.card`/... → `.cmb-public .hero`...; `@keyframes` giữ tên. Guard: `grep -nE '^(body|html|:root|\*)\b' app/resources/css/cmb-public.css` phải rỗng. (Rò rỉ reset/body/:root sẽ phá app AntD.)
- Font: `@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap');` đầu file CSS.
- HTML→JSX: `class`→`className`, `for`→`htmlFor`, `style="a:b"`→`style={{a:'b'}}` camelCase, tag rỗng tự đóng, SVG attr camelCase (`stroke-width`→`strokeWidth`, `stroke-linecap`→`strokeLinecap`, `fill-rule`→`fillRule`, `clip-rule`→`clipRule`, `stop-color`→`stopColor`; giữ `viewBox`), comment `{/* */}`, `&amp;`→`&`. KHÔNG `dangerouslySetInnerHTML`.
- Mỗi `.map()` phải có `key`.
- KHÔNG sửa các file đang dở trong working tree (help-center md, useMessageNotifications.ts). KHÔNG đụng app đăng nhập / admin.

## Header dùng chung (mọi trang public)
- Logo "CMBcore**Seller**" → `/`
- Anchor: Tính năng `/#features`, AI & Quảng cáo `/#automation`, Tích hợp `/#integrations`
- Bảng giá → `/pricing`; Tài liệu API → `/api-docs`
- Dropdown "Phần mềm phụ trợ": Chrome extension (`<a href={CHROME_EXT_URL}>` external), App mobile → `/download`
- CTA: đã đăng nhập → "Truy cập" `/dashboard`; chưa → "Dùng thử" `/register` + "Truy cập" `/dashboard` (giữ logic auth của `PublicHeader.tsx` hiện tại).
- Anchor `/#x` từ trang khác: dùng `<Link to="/#features">`; landing có `useEffect` đọc `location.hash` để cuộn tới.

---

### Task 1: Design-system CSS dùng chung `cmb-public.css`

**Files:** Create `app/resources/css/cmb-public.css`

**Interfaces:** Produces file CSS scope dưới `.cmb-public`, gồm: design tokens (`--primary:#1B4DFF`, `--accent`, `--violet`, `--text`, `--ink`, fonts, shadow, radius...), reset scoped, và toàn bộ class landing (`.hero .stats-band .trust-strip .pain-section .feature-block .automation-section .compare-section .integrations .audience-section .final-cta .site-footer .reveal/.in .grad`) + primitive tái dùng (`.container`, `.btn`/`.btn-primary`/`.btn-ghost`, `.card`, `.section`, heading helpers, `.lh-*` header). Task 2–6 dùng đúng các tên này.

- [ ] **Step 1:** Đọc `<style>` của `seller.blade.php` (≈48–510) và `<style>` `.lh-*` của `landing-header.blade.php`.
- [ ] **Step 2:** Tạo `app/resources/css/cmb-public.css`: `@import` Google Fonts; copy toàn bộ CSS; **scope mọi selector dưới `.cmb-public`** theo Global Constraints. `:root` vars → `.cmb-public`. `body` rules → `.cmb-public`. Reset `*` → `.cmb-public *`. Thêm `.cmb-public{scroll-behavior:smooth}` và `scroll-margin-top: <header_height>` cho các section có `id`.
- [ ] **Step 3:** Guard grep rỗng (xem Global Constraints). 
- [ ] **Step 4: Commit** `git add app/resources/css/cmb-public.css && git commit -m "feat(public): design-system CSS port landing (scoped .cmb-public)"`

---

### Task 2: PublicLayout + Header + Footer mới (chrome dùng chung)

**Files:**
- Modify: `app/resources/js/pages/public/PublicLayout.tsx` (bọc `.cmb-public` + import CSS)
- Modify: `app/resources/js/pages/public/PublicHeader.tsx` (header frosted, nav như "Header dùng chung")
- Modify: `app/resources/js/pages/public/PublicFooter.tsx` (footer tối port từ landing)

**Interfaces:** Consumes `cmb-public.css` (Task 1). Produces: `<PublicLayout>` render `<div className="cmb-public"><PublicHeader/><main><Outlet/></main><PublicFooter/></div>`; header/footer dùng class `.lh-*`/`.site-footer`.

- [ ] **Step 1:** `PublicLayout.tsx`: `import '../../css/cmb-public.css';` bọc nội dung trong `<div className="cmb-public">`. (Giữ `<Outlet/>`.)
- [ ] **Step 2:** `PublicHeader.tsx`: viết lại theo `landing-header.blade.php` (JSX), nav theo "Header dùng chung", hamburger mobile bằng `useState`, sticky shadow bằng `useEffect` scroll listener (cleanup). Giữ logic auth hiện có (đã đăng nhập? → "Truy cập"). `import { CHROME_EXT_URL } from './ToolsPage';`. Dùng `<Link>` cho route nội bộ, `<a>` cho extension.
- [ ] **Step 3:** `PublicFooter.tsx`: port `.site-footer` (4 cột: brand+CTA / sản phẩm / tích hợp / công ty), `new Date().getFullYear()`, "CMB Core Solutions".
- [ ] **Step 4:** Verify `npm run lint && npm run typecheck && npm run build`.
- [ ] **Step 5: Commit** các 3 file → `feat(public): chrome dùng chung (PublicLayout/Header/Footer) theo landing`

---

### Task 3: Trang gốc `/` — landing body (port)

**Files:**
- Create: `app/resources/js/pages/public/SellerLandingPage.tsx`
- Modify: `app/resources/js/app.tsx` (route `/` dùng layout mới + SellerLandingPage; bỏ HomePage cũ khỏi route)

**Interfaces:** Consumes layout/CSS (Task 1,2). Produces component `SellerLandingPage` (chỉ thân, không header/footer).

- [ ] **Step 1:** Đọc body + `<script>` của `seller.blade.php`. Lập const arrays cho các phần lặp (pain points, 9 features, trust logos, automations, comparisons, ROI, integrations, audience).
- [ ] **Step 2:** Tạo `SellerLandingPage.tsx`: render các section JSX (giữ `id`: `features automation integrations roi ...`), data lặp `.map()` có `key`. CTA `/register`, `/dashboard`. Bỏ back-link công ty. `useEffect`: IntersectionObserver `.reveal→.in` + count-up `[data-count]` + cuộn theo `location.hash` (dùng `useLocation`); cleanup observers.
- [ ] **Step 3:** `app.tsx`: đưa `/` VÀO nhóm `<Route element={<PublicLayout/>}>` cùng `/pricing,/tools,/api-docs`; `path="/"` → `<SellerLandingPage/>` (thay `HomePage`). Giữ `HomePage.tsx` trên đĩa (không xóa, ghi chú report).
- [ ] **Step 4:** Verify build/lint/types. Sửa lỗi JSX/SVG/TS.
- [ ] **Step 5: Commit** → `feat(landing): trang gốc / dùng landing CMBcore Seller (port React)`

---

### Task 4: Restyle Bảng giá `/pricing`

**Files:** Modify `app/resources/js/pages/public/PricingPage.tsx`

**Interfaces:** Consumes design-system (Task 1). Trang nằm trong PublicLayout mới (đã có chrome).

- [ ] **Step 1:** Đọc `PricingPage.tsx` hiện tại — giữ NGUYÊN dữ liệu gói/giá/tính năng.
- [ ] **Step 2:** Style lại thân: thay layout AntD bằng `.cmb-public` primitives (`.container`, `.section`, card giá theo phong cách landing, `.btn-primary`/`.btn-ghost`, typography `--font`). Có thể giữ vài component AntD nếu cần (Table/Tag) nhưng bọc trong style mới để khớp màu/nền; ưu tiên card/section CSS dùng chung. Đảm bảo tương phản nền (landing nhiều nền sáng + accent) — không để chữ tối trên nền tối.
- [ ] **Step 3:** Verify build/lint/types.
- [ ] **Step 4: Commit** → `style(pricing): đồng bộ giao diện theo landing`

---

### Task 5: Restyle Tài liệu API `/api-docs`

**Files:** Modify `app/resources/js/pages/public/ApiDocsPage.tsx`

- [ ] **Step 1:** Đọc `ApiDocsPage.tsx` (8 mục, code blocks copy-to-clipboard) — giữ NGUYÊN nội dung & chức năng copy. Base URL `https://app.cmbcore.com/api/v1` giữ nguyên.
- [ ] **Step 2:** Style lại: sidebar mục lục + nội dung theo design-system (container/section/card, code block dùng font `--mono` JetBrains Mono, màu theo theme). Giữ `Anchor`/copy logic; chỉ đổi lớp vỏ trình bày cho khớp landing.
- [ ] **Step 3:** Verify build/lint/types.
- [ ] **Step 4: Commit** → `style(api-docs): đồng bộ giao diện theo landing`

---

### Task 6: Restyle Tools `/tools` + Download `/download`

**Files:**
- Modify `app/resources/js/pages/public/ToolsPage.tsx`
- Modify `app/resources/js/pages/DownloadAppPage.tsx`
- Modify `app/resources/js/app.tsx` (đưa `/download` vào PublicLayout)

- [ ] **Step 1:** `ToolsPage.tsx`: giữ `CHROME_EXT_URL` export + nội dung; style lại theo design-system.
- [ ] **Step 2:** `DownloadAppPage.tsx`: hiện standalone với `dl-nav` + `download-app.css`. Bỏ nav riêng, đưa route `/download` vào nhóm `<PublicLayout>` trong `app.tsx` (xóa route standalone cũ). Style thân theo design-system; giữ hằng `APK_URL` + nội dung. `download-app.css` có thể giữ nếu không xung đột, hoặc gộp style cần thiết — ưu tiên dùng `.cmb-public` primitives.
- [ ] **Step 3:** Verify build/lint/types.
- [ ] **Step 4: Commit** → `style(tools,download): đồng bộ giao diện theo landing`

---

### Task 7: Kiểm tra trực quan toàn bộ (Playwright)

- [ ] **Step 1:** Chạy app (dev `npm run dev` + `php artisan serve`, hoặc build + serve). Mở bằng Playwright: `/`, `/pricing`, `/api-docs`, `/tools`, `/download` — chụp ảnh desktop (1440) + mobile (390).
- [ ] **Step 2:** Kiểm: header/footer nhất quán mọi trang; landing đủ section (hero command-center, stats count-up, marquee, features, footer); các trang khác khớp phong cách; responsive OK.
- [ ] **Step 3:** **Quan trọng** — mở 1 trang app đăng nhập (vd `/login` hoặc `/dashboard`) xác nhận CSS `.cmb-public` KHÔNG rò rỉ (AntD vẫn bình thường).
- [ ] **Step 4:** Lệch/vỡ → ghi nhận & sửa ở task tương ứng. Đạt → xong.

---

## Self-Review
- Approach (React port) + depth (chung chrome + restyle thân) + header (anchor + product links) = đúng lựa chọn user. ✓
- Rủi ro #1 = CSS rò rỉ global phá app AntD → scope `.cmb-public` + grep guard (Task 1 Step 3) + kiểm tra trang login (Task 7 Step 3). ✓
- Mọi trang public 1 layout/chrome (Task 2) ⇒ nhất quán; `/` & `/download` đưa vào layout (Task 3,6). ✓
- Không TDD (trang tĩnh, không JS test runner — baseline); verify = lint/typecheck/build + ảnh Playwright. ✓
- Thứ tự: Task 1→2 nền tảng; 3–6 độc lập tương đối (đều phụ thuộc 1,2); 7 cuối.
