# Audit giao diện app người dùng trên tablet ngang — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task (inline execution required — a single continuous Playwright browser session/login must persist across all tasks; do NOT use subagent-driven-development, which would lose that session per task). Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Audit trực quan toàn bộ trang trong app người dùng (`app.cmbcore.com`) ở 3 độ phân giải tablet ngang, ghi nhận lỗi hiển thị/tương tác vào 1 báo cáo markdown, làm cơ sở cho việc lên kế hoạch sửa ở giai đoạn sau. Không sửa code trong plan này.

**Architecture:** Playwright (MCP browser tools) đăng nhập 1 lần vào production bằng tài khoản thật, giữ nguyên session xuyên suốt, lần lượt set viewport → điều hướng từng trang → chụp ảnh → so sánh trực quan → ghi phát hiện vào file báo cáo `docs/technical-audit-tablet-ui-2026-07-21.md`. Report được append dần qua từng task, commit sau mỗi task để không mất tiến độ.

**Tech Stack:** Playwright MCP browser tools (`browser_navigate`, `browser_resize`, `browser_take_screenshot`, `browser_snapshot`), file thao tác qua Write/Edit, git commit.

## Global Constraints

- Chỉ đọc/xem — không tạo/sửa/xóa dữ liệu thật trên production (không submit form, không lưu cài đặt); được phép mở modal/drawer xem chi tiết.
- Không log/lưu mật khẩu vào bất kỳ file nào trong repo.
- Ảnh chụp lưu tại `C:\Users\minhm\AppData\Local\Temp\claude\D--cmb-core-seller\51b4d022-62f3-4290-b393-f460897bb5b8\scratchpad\tablet-audit\` (KHÔNG commit — chứa dữ liệu tài khoản thật).
- 3 viewport bắt buộc cho MỌI trang: 1280×800, 1024×768, 900×600. Chỉ ngang — không test viewport dọc.
- Chỉ audit app người dùng (`app.cmbcore.com`), không đụng `/admin/*`.
- Report duy nhất: `docs/technical-audit-tablet-ui-2026-07-21.md`, append theo từng task, KHÔNG ghi đè phần đã có.
- Mức độ nghiêm trọng dùng đúng 3 mức: High (chặn thao tác) / Medium (dùng được nhưng khó chịu/mất thẩm mỹ) / Low (tiểu tiết).

---

### Task 0: Khởi tạo báo cáo + đăng nhập

**Files:**
- Create: `docs/technical-audit-tablet-ui-2026-07-21.md`

**Interfaces:**
- Produces: file báo cáo với khung sườn mục lục; browser session đã đăng nhập (dùng xuyên suốt Task 1-9).

- [ ] **Step 1: Tạo khung báo cáo**

Viết `docs/technical-audit-tablet-ui-2026-07-21.md` với nội dung:

```markdown
# Audit giao diện tablet ngang — App người dùng (2026-07-21)

Phạm vi: `app.cmbcore.com`, viewport 1280×800 / 1024×768 / 900×600 (chỉ ngang). Spec: `docs/superpowers/specs/2026-07-21-tablet-ui-audit-design.md`.

## Mục lục
- [Khung layout chung (Sider/Header)](#khung-layout-chung)
- [Đơn hàng & Hoàn hủy](#đơn-hàng--hoàn-hủy)
- [Khách hàng, Gian hàng, Sản phẩm](#khách-hàng-gian-hàng-sản-phẩm)
- [Tin nhắn](#tin-nhắn)
- [Đăng bán sàn](#đăng-bán-sàn)
- [Kho & mua hàng](#kho--mua-hàng)
- [Quảng cáo](#quảng-cáo)
- [Báo cáo](#báo-cáo)
- [Kế toán](#kế-toán)
- [Hệ thống](#hệ-thống)
- [Tổng hợp lỗi lặp lại nhiều trang](#tổng-hợp)

---
```

- [ ] **Step 2: Đăng nhập vào production**

Dùng Playwright: `browser_navigate` tới `https://app.cmbcore.com/login`, `browser_resize` về 1280×800, điền form đăng nhập bằng `browser_fill_form` hoặc `browser_type` với tài khoản `mìnhmen99@gmail.com` / mật khẩu người dùng đã cung cấp trong hội thoại (KHÔNG gõ mật khẩu vào bất kỳ file nào), bấm đăng nhập, `browser_snapshot` xác nhận vào được `/dashboard`.

Expected: URL chuyển sang `/dashboard`, không còn form đăng nhập.

- [ ] **Step 3: Commit khung báo cáo**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): khởi tạo báo cáo audit tablet ngang"
```

---

### Task 1: Khung layout chung (Sider/Header) — mọi trang

**Files:**
- Modify: `docs/technical-audit-tablet-ui-2026-07-21.md` (section `## Khung layout chung`)

**Interfaces:**
- Consumes: session đã đăng nhập từ Task 0.
- Produces: kết luận về hành vi Sider/Header dùng chung cho mọi task sau (nếu lỗi lặp lại y hệt ở mọi trang, các task sau chỉ cần ghi "giống lỗi khung chung" thay vì lặp mô tả).

- [ ] **Step 1: Chụp Dashboard ở cả 3 viewport**

Trên trang `/dashboard` (đã vào từ Task 0): `browser_resize` lần lượt 1280×800 → chụp ảnh lưu `tablet-audit/dashboard-1280x800.png`; 1024×768 → `dashboard-1024x768.png`; 900×600 → `dashboard-900x600.png`. Dùng `browser_snapshot` để đọc DOM/kiểm tra phần tử bị tràn/che khuất song song với ảnh chụp.

- [ ] **Step 2: Đánh giá hành vi Sider, Header, nút thu gọn menu**

Với mỗi viewport, kiểm tra: Sider (236px/64px collapsed) có tự thu gọn không hay luôn full-width chiếm chỗ; nút thu gọn (`MenuFoldOutlined`/`MenuUnfoldOutlined` trong `AppHeader`) có hoạt động và đủ chỗ bấm không; `Content` còn đủ rộng để đọc hay bị bóp; có thanh cuộn ngang toàn trang xuất hiện không (dấu hiệu tràn layout tổng thể).

- [ ] **Step 3: Ghi phát hiện vào báo cáo**

Append vào `## Khung layout chung`:

```markdown
## Khung layout chung {#khung-layout-chung}

Áp dụng cho **mọi trang** — Sider/Header dùng chung `AppLayout.tsx`. Nếu 1 trang cụ thể không lặp lại mô tả này, phần dưới đây chỉ nêu khác biệt.

| Viewport | Mức độ | Mô tả |
|---|---|---|
| 1280×800 | ... | ... |
| 1024×768 | ... | ... |
| 900×600 | ... | ... |
```

(Điền mô tả thật dựa trên ảnh/snapshot đã chụp ở Step 1-2 — không để placeholder.)

- [ ] **Step 4: Commit**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): audit khung layout chung tablet ngang"
```

---

### Task 2: Đơn hàng & Hoàn hủy

**Files:**
- Modify: `docs/technical-audit-tablet-ui-2026-07-21.md` (section `## Đơn hàng & Hoàn hủy`)

**Interfaces:**
- Consumes: session Task 0; quy ước ghi "giống khung chung" từ Task 1.

- [ ] **Step 1: Audit từng trang ở 3 viewport**

Với mỗi route dưới đây: `browser_navigate`, rồi lần lượt `browser_resize` 1280×800 / 1024×768 / 900×600, `browser_take_screenshot` lưu `tablet-audit/<slug>-<w>x<h>.png`, `browser_snapshot` kiểm tra tràn/che khuất:

- `/orders?tab=pending` — danh sách đơn hàng (Table nhiều cột: mã đơn, khách, sàn, trạng thái, tổng tiền, thao tác — trọng điểm kiểm tra tràn ngang Table)
- `/orders/new` — form tạo đơn (form nhiều cột: thông tin khách, sản phẩm, vận chuyển — trọng điểm kiểm tra vỡ layout form/input chồng lấn)
- Mở 1 đơn bất kỳ từ danh sách (`/orders/:id`) — chi tiết đơn (thường có Modal/Drawer + nhiều panel song song)
- `/returns` — danh sách hoàn/hủy

- [ ] **Step 2: Ghi phát hiện vào báo cáo**

Append vào `## Đơn hàng & Hoàn hủy`, mỗi trang 1 bảng như mẫu Task 1 Step 3, cột "Mô tả" ghi cụ thể (VD: "Table tràn ngang, phải cuộn ngang mới thấy cột Thao tác" hoặc "giống khung chung, không có lỗi riêng").

- [ ] **Step 3: Commit**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): audit Đơn hàng & Hoàn hủy tablet ngang"
```

---

### Task 3: Khách hàng, Gian hàng, Sản phẩm

**Files:**
- Modify: `docs/technical-audit-tablet-ui-2026-07-21.md` (section `## Khách hàng, Gian hàng, Sản phẩm`)

- [ ] **Step 1: Audit từng trang ở 3 viewport** (cùng quy trình Task 2 Step 1)

- `/customers` — danh sách khách hàng
- `/channels` — danh sách gian hàng đã kết nối
- `/products` — danh sách sản phẩm & SKU

- [ ] **Step 2: Ghi phát hiện vào báo cáo** (cùng format Task 2 Step 2)

- [ ] **Step 3: Commit**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): audit Khách hàng/Gian hàng/Sản phẩm tablet ngang"
```

---

### Task 4: Tin nhắn

**Files:**
- Modify: `docs/technical-audit-tablet-ui-2026-07-21.md` (section `## Tin nhắn`)

- [ ] **Step 1: Audit từng trang ở 3 viewport**

- `/messaging` — hộp thư (layout 2-3 cột: danh sách hội thoại + khung chat + panel thông tin khách — trọng điểm kiểm tra 900×600 có đủ chỗ cho cả 3 cột không)
- `/messaging/channels` — kết nối kênh
- `/messaging/templates` — mẫu tin
- `/messaging/utility-templates` — tin tiện ích
- `/messaging/auto-rules` — tự động trả lời
- `/messaging/flows` — kịch bản tự động (Flow Builder — canvas kéo-thả, trọng điểm kiểm tra canvas/toolbar có bị che ở màn hẹp)
- `/messaging/knowledge` — AI training

- [ ] **Step 2: Ghi phát hiện vào báo cáo**

- [ ] **Step 3: Commit**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): audit Tin nhắn tablet ngang"
```

---

### Task 5: Đăng bán sàn

**Files:**
- Modify: `docs/technical-audit-tablet-ui-2026-07-21.md` (section `## Đăng bán sàn`)

- [ ] **Step 1: Audit từng trang ở 3 viewport**

- `/marketplace/products` — sao chép sản phẩm
- `/marketplace/to-push` — chờ đẩy lên sàn
- `/marketplace/on-channel` — đã có trên sàn
- `/marketplace/promotions` — chiến dịch giảm giá

- [ ] **Step 2: Ghi phát hiện vào báo cáo**

- [ ] **Step 3: Commit**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): audit Đăng bán sàn tablet ngang"
```

---

### Task 6: Kho & mua hàng, Quảng cáo

**Files:**
- Modify: `docs/technical-audit-tablet-ui-2026-07-21.md` (sections `## Kho & mua hàng`, `## Quảng cáo`)

- [ ] **Step 1: Audit từng trang ở 3 viewport**

- `/inventory` — tồn kho
- `/procurement/demand-planning` — đề xuất nhập hàng
- `/procurement/suppliers` — nhà cung cấp
- `/procurement/purchase-orders` — đơn mua hàng
- `/marketing` — quảng cáo Facebook
- `/marketing/tiktok` — quảng cáo TikTok

- [ ] **Step 2: Ghi phát hiện vào báo cáo** (2 section riêng)

- [ ] **Step 3: Commit**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): audit Kho & mua hàng, Quảng cáo tablet ngang"
```

---

### Task 7: Báo cáo

**Files:**
- Modify: `docs/technical-audit-tablet-ui-2026-07-21.md` (section `## Báo cáo`)

- [ ] **Step 1: Audit từng trang ở 3 viewport**

- `/reports/overview` — báo cáo tổng thể (dashboard nhiều biểu đồ — trọng điểm kiểm tra chart co giãn/tràn)
- `/reports` — báo cáo bán hàng
- `/shop-report` — báo cáo sàn
- `/finance/settlements` — đối soát sàn

- [ ] **Step 2: Ghi phát hiện vào báo cáo**

- [ ] **Step 3: Commit**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): audit Báo cáo tablet ngang"
```

---

### Task 8: Kế toán

**Files:**
- Modify: `docs/technical-audit-tablet-ui-2026-07-21.md` (section `## Kế toán`)

- [ ] **Step 1: Audit từng trang ở 3 viewport**

- `/accounting/dashboard` — tổng quan kế toán
- `/accounting/journals` — sổ nhật ký chung
- `/accounting/chart-of-accounts` — hệ thống tài khoản
- `/accounting/balances` — cân đối phát sinh
- `/accounting/periods` — kỳ kế toán
- `/accounting/ar` — công nợ phải thu
- `/accounting/ap` — công nợ phải trả
- `/accounting/cash` — quỹ & ngân hàng
- `/accounting/reports` — báo cáo tài chính & thuế

- [ ] **Step 2: Ghi phát hiện vào báo cáo**

- [ ] **Step 3: Commit**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): audit Kế toán tablet ngang"
```

---

### Task 9: Hệ thống + Tổng hợp cuối

**Files:**
- Modify: `docs/technical-audit-tablet-ui-2026-07-21.md` (sections `## Hệ thống`, `## Tổng hợp`)

**Interfaces:**
- Consumes: toàn bộ phát hiện từ Task 1-8 (đọc lại file report để tổng hợp).

- [ ] **Step 1: Audit từng trang ở 3 viewport**

- `/sync-logs` — nhật ký đồng bộ
- `/support` — trung tâm trợ giúp
- `/settings` — cài đặt (nhiều tab con — trọng điểm kiểm tra tab bar có tràn không)

- [ ] **Step 2: Ghi phát hiện vào báo cáo**

- [ ] **Step 3: Đọc lại toàn bộ report, viết mục Tổng hợp**

Đọc lại `docs/technical-audit-tablet-ui-2026-07-21.md` (Task 1-9), liệt kê:
- Lỗi xuất hiện ở TẤT CẢ hoặc ĐA SỐ trang (ứng viên sửa 1 lần ở tầng layout/component dùng chung).
- Lỗi riêng biệt từng trang, xếp theo mức độ nghiêm trọng giảm dần (High trước).
- Đề xuất thứ tự ưu tiên sửa ở giai đoạn sau (không viết code, chỉ liệt kê định hướng).

Append:

```markdown
## Tổng hợp {#tổng-hợp}

### Lỗi lặp lại nhiều trang
...

### Lỗi riêng từng trang (theo mức độ)
**High:**
- ...

**Medium:**
- ...

**Low:**
- ...

### Đề xuất ưu tiên sửa (giai đoạn sau)
...
```

- [ ] **Step 4: Commit cuối**

```bash
git add docs/technical-audit-tablet-ui-2026-07-21.md
git commit -m "docs(audit): audit Hệ thống + tổng hợp báo cáo tablet ngang"
```

---

## Self-Review Checklist (đã chạy khi viết plan)

- **Spec coverage:** 3 viewport ngang ✓ (mọi task), chỉ app người dùng ✓, ~35+ trang từ `buildNav()` ✓ (Task 1-9 phủ hết kể cả `/orders/new` và `/orders/:id` không có trong nav nhưng có trong spec ý "tạo đơn, chi tiết 1 đơn"), report tại đúng đường dẫn ✓, ảnh không commit ✓, không sửa code ✓.
- **Placeholder scan:** các bảng "..." trong Step ghi báo cáo là khuôn mẫu để điền, không phải nội dung cuối — mỗi Step ghi rõ "điền dựa trên ảnh/snapshot đã chụp, không để placeholder" để nhắc người thực thi.
- **Type consistency:** không áp dụng (không có code/hàm xuyên task).
