# Kế hoạch thực thi — Bổ sung `docs_user/` & tạo `support_doc/`

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Viết bộ tài liệu hướng dẫn sử dụng đầy đủ, dễ hiểu cho người dùng cuối — cập nhật `docs_user/` (nuôi trợ lý AI) và tạo `support_doc/` (bài viết + ảnh cho trang support sau này) — khớp source code và giao diện thật.

**Architecture:** Hai nguồn tách biệt. `docs_user/` = tham khảo sâu + `rag_chunks.jsonl` (RAG). `support_doc/` = bài viết người dùng đọc, một bài/khu vực menu, kèm ảnh chụp thật. Xác minh từng bài bằng cách đăng nhập demo và thao tác trực tiếp; phần đọc source có thể chạy song song, phần duyệt live chạy tuần tự (một phiên trình duyệt).

**Tech Stack:** Markdown · Playwright (duyệt + chụp `app.cmbcore.com`) · `php artisan help:index` (kiểm tra RAG, local SQLite) · nguồn: Laravel modules `app/app/Modules/*` + React `app/resources/js/*`.

**Spec:** `docs/superpowers/specs/2026-06-04-user-doc-va-support-doc-design.md`

---

## QUY TẮC NỘI DUNG (áp cho MỌI task — đọc trước khi viết bất kỳ chữ nào)

1. **Chỉ** chỉ đường bằng **tên menu + nhãn nút tiếng Việt** đúng chữ trên màn hình.
2. **KHÔNG** nhắc: URL (vd `/orders`), endpoint/API, tên bảng, tên hàm/lớp, mã lỗi viết hoa trần.
3. **Lỗi** mô tả bằng lời ("Kỳ kế toán đã đóng nên không ghi thêm được"), không đọc mã lỗi.
4. Nhắc **gói** (Miễn phí/Pro/Business) + **vai trò** cần có bằng tiếng Việt khi tính năng bị giới hạn.
5. Câu ngắn, xưng "bạn", mỗi bước một hành động bắt đầu bằng động từ ("Bấm…", "Chọn…", "Nhập…").
6. Không bịa. Không chắc → ghi "tính năng này hãy hỏi qua **Trợ giúp → Hỏi CSKH**".

## KHUÔN BÀI VIẾT `support_doc/*.md` (dùng nguyên cho mọi bài)

```markdown
---
title: <Tiêu đề ngắn theo tên khu vực>
slug: <kebab-case khớp tên file>
menu: "<Nhóm> → <Mục>"          # vd: "Bán hàng → Đơn hàng"
plan: <Miễn phí | Pro | Business>   # bỏ dòng này nếu mọi gói dùng được
roles: ["<vai trò cần có>"]           # bỏ dòng này nếu mọi vai trò dùng được
---

# <Tiêu đề>

**Việc này giúp gì:** 1–2 câu.

**Bạn cần:** gói + vai trò (bỏ qua nếu ai cũng dùng được).

## Các bước
1. <Động từ + hành động>.
   ![<mô tả ngắn>](images/<slug>-1.png)
2. ...

## Mẹo
- ...

## Lỗi thường gặp & cách xử lý
- **<Triệu chứng bằng lời>:** <cách xử lý>.

## Xem thêm
- [<Bài liên quan>](<file>.md)
```

## QUY TRÌNH XÁC MINH (lặp cho mỗi bài — đây là "test" của task tài liệu)

1. Đọc source module liên quan (Controllers/Requests/Services + trang React) → nắm nút, điều kiện, gói/quyền, luồng.
2. Đăng nhập demo (xem Task 1) → thao tác **đầy đủ** tới màn hình (kể cả tạo/sửa thử, mức vừa đủ minh hoạ).
3. Đối chiếu nhãn nút + hành vi source ↔ live; ghi đúng chữ tiếng Việt.
4. Chụp ảnh các bước trực quan → `support_doc/images/<slug>-<n>.png`.
5. Viết bài theo khuôn; tự soát theo QUY TẮC NỘI DUNG §1–6.

## QUY ƯỚC COMMIT

- Mỗi task commit riêng, **chỉ `git add` đúng đường dẫn của task** (tránh quét nhầm thay đổi đang treo ngoài task — vd phần gỡ demo-login chưa commit).
- Mẫu: `git commit -m "docs(support): <khu vực>"`.

---

## Task 0: Vệ sinh nhánh & khởi tạo khung thư mục

**Files:**
- Create: `support_doc/README.md`
- Create thư mục: `support_doc/images/.gitkeep`

- [ ] **Step 1: Kiểm tra trạng thái cây làm việc**

Run: `cd D:/cmb_core_seller; git status --short`
Ghi nhận: có thể còn thay đổi treo (gỡ demo-login) + file test chưa track. **Không** commit chung với tài liệu. Nếu muốn tách hẳn, có thể tạo nhánh `docs/user-support-docs` — tuỳ chọn, hỏi người dùng nếu chưa rõ.

- [ ] **Step 2: Tạo khung `support_doc/`**

Tạo `support_doc/images/.gitkeep` (rỗng) và `support_doc/README.md`:

```markdown
# Trung tâm trợ giúp CMBcoreSeller — Nội dung

Bộ bài viết hướng dẫn sử dụng cho người dùng cuối. Mỗi bài là một khu vực trong app,
viết theo tên menu/nút tiếng Việt, từng bước, kèm ảnh chụp màn hình.

> Quy tắc: không nhắc URL/endpoint/tên bảng/tên class/mã lỗi. Xem chi tiết trong
> `docs/superpowers/specs/2026-06-04-user-doc-va-support-doc-design.md`.

## Mục lục

1. [Bắt đầu](01-bat-dau.md)
2. [Bảng điều khiển](02-bang-dieu-khien.md)
3. [Gian hàng (kết nối sàn)](03-gian-hang.md)
4. [Đơn hàng & giao hàng](04-don-hang.md)
5. [Hoàn & Hủy](05-hoan-huy.md)
6. [Khách hàng](06-khach-hang.md)
7. [Sản phẩm & SKU](07-san-pham-sku.md)
8. [Tồn kho](08-ton-kho.md)
9. [Mua hàng](09-mua-hang.md)
10. [Tin nhắn](10-tin-nhan.md)
11. [Quảng cáo](11-quang-cao.md)
12. [Đối soát & lợi nhuận](12-doi-soat-loi-nhuan.md)
13. [Báo cáo](13-bao-cao.md)
14. [Kế toán (TT133)](14-ke-toan.md)
15. [Gói thuê bao & thanh toán](15-goi-thanh-toan.md)
16. [Nhật ký đồng bộ](16-nhat-ky-dong-bo.md)
17. [Cài đặt](17-cai-dat.md)
18. [Trợ giúp & CSKH](18-tro-giup-cskh.md)
```

- [ ] **Step 3: Commit**

```bash
git add support_doc/README.md support_doc/images/.gitkeep
git commit -m "docs(support): khung thư mục + mục lục support_doc"
```

---

## Task 1: Thiết lập phiên duyệt live (dùng lại cho mọi task sau)

Không tạo file — đây là quy trình chuẩn để mọi task chụp ảnh nhất quán.

- [ ] **Step 1: Cố định kích thước cửa sổ** — đặt viewport `1440×900` (qua công cụ Playwright `browser_resize`) để ảnh đồng đều.

- [ ] **Step 2: Đăng nhập** — mở `https://app.cmbcore.com`, đăng nhập `owner@demo.local` / `Cmbcore1012@`. Xác nhận vào Bảng điều khiển.

- [ ] **Step 3: Quy ước chụp** — dùng `browser_take_screenshot` lưu vào `support_doc/images/<slug>-<n>.png`. Ưu tiên chụp vùng nội dung chính; chỉ chụp bước có ý nghĩa trực quan. Rà nhanh tránh lộ dữ liệu nhạy cảm (SĐT khách…).

- [ ] **Step 4: Lưu ý** — một phiên trình duyệt, thao tác tuần tự. Khi tạo/sửa thử, giữ tối thiểu, tránh xoá dữ liệu mẫu quan trọng.

---

## Task 2: Bản đồ tính năng (chạy SONG SONG bằng agent đọc source)

**Files:**
- Create: `docs/superpowers/plans/_feature-map.md` (tạm, hỗ trợ viết bài; có thể xoá ở Task cuối)

- [ ] **Step 1: Dispatch agent Explore song song** cho các nhóm module, mỗi agent trả "bản đồ": màn hình, nút chính (nhãn VN), điều kiện/khoá theo gói-vai trò, luồng nghiệp vụ, lỗi/validation hay gặp. Nhóm:
  - Orders + Fulfillment + Returns (`app/app/Modules/Orders`, `Fulfillment`, `Returns` nếu có)
  - Channels + Products + Inventory (`Channels`, `Inventory`, `Catalog/Products`)
  - Procurement (`Procurement`)
  - Messaging (`Messaging`) + Integrations/Messaging
  - Finance/Settlements + Reports + Accounting (`Finance`, `Reports`, `Accounting`)
  - Billing/Subscription + Tenancy (gói, vai trò, mời nhân sự) + Settings
  - Marketing/Ads (`Marketing`) — nhánh hiện tại
  - Support (widget Hỏi AI/CSKH)

- [ ] **Step 2: Tổng hợp** kết quả vào `_feature-map.md` (mỗi khu vực một mục). Dùng làm nguồn dữ kiện khi viết bài & rag_chunks.

- [ ] **Step 3: Commit**

```bash
git add docs/superpowers/plans/_feature-map.md
git commit -m "docs(support): bản đồ tính năng (nội bộ, hỗ trợ viết bài)"
```

---

## Task 3 → 20: Viết 18 bài `support_doc/` (mỗi bài một task)

> Mỗi task theo đúng **QUY TRÌNH XÁC MINH** ở trên. Dưới đây nêu: file, source cần đọc, màn hình cần chụp, dàn ý + dữ kiện bắt buộc, gói/quyền, lỗi thường gặp. Prose cuối viết trong lúc thực thi (đọc source + live).

### Task 3: `support_doc/01-bat-dau.md` — Bắt đầu
- **Source:** `Modules/Tenancy` (AuthController, đăng ký/đăng nhập, mời nhân sự, vai trò) · `Modules/Billing` (chọn gói) · React `pages` login/register, `SettingsLayout`, members.
- **Live cần chụp:** màn đăng nhập; tạo tài khoản/gian hàng; chuyển gian hàng (Select ở header); **Cài đặt → Nhân sự** (mời nhân viên, chọn vai trò).
- **Dàn ý:** Đăng nhập là gì → Đăng nhập/đăng ký → Hiểu "gian hàng" & chuyển gian hàng → Mời nhân viên & phân quyền (liệt kê 7 vai trò: Chủ sở hữu, Quản trị, NV xử lý đơn, NV kho, NV chăm sóc khách, Kế toán, Chỉ xem) → Chọn/nâng gói.
- **Lỗi thường gặp:** "Email hoặc mật khẩu không đúng"; giao diện tự ẩn nút khi thiếu quyền (giải thích).
- **Xem thêm:** Cài đặt, Gói thuê bao.

### Task 4: `support_doc/02-bang-dieu-khien.md` — Bảng điều khiển
- **Source:** React `pages/DashboardPage` (hoặc tương đương) + các widget.
- **Live:** trang `/` sau đăng nhập; giải thích từng thẻ số liệu/biểu đồ.
- **Dàn ý:** Bảng điều khiển cho biết gì → Đọc các thẻ tổng quan → Lọc theo gian hàng/thời gian (nếu có).
- **Xem thêm:** Đơn hàng, Báo cáo.

### Task 5: `support_doc/03-gian-hang.md` — Gian hàng (kết nối sàn)
- **Source:** `Modules/Channels` + `Integrations/Channels` (TikTok, Lazada; Shopee chờ duyệt) · React `pages/ChannelsPage`, `oauthPopup.ts`.
- **Live:** menu **Gian hàng**; nút **Kết nối TikTok / Kết nối Lazada**; trạng thái kết nối; nút đồng bộ.
- **Dàn ý:** Gian hàng là gì → Kết nối TikTok (qua cửa sổ đăng nhập sàn) → Kết nối Lazada → Shopee (đang chờ duyệt) → Đồng bộ đơn/sản phẩm → Ngắt/kết nối lại.
- **Dữ kiện:** dữ liệu sàn là nguồn chuẩn; luôn có đồng bộ định kỳ dự phòng; đồng bộ chạy lại an toàn (không nhân đôi).
- **Lỗi thường gặp:** kết nối lại sau khi ngắt (hệ thống tự khôi phục kết nối cũ — không tạo trùng); Lazada chat 0 tin = app thiếu quyền nhóm tin nhắn trên sàn (mô tả bằng lời, hướng dẫn báo CSKH).
- **Xem thêm:** Sản phẩm & SKU, Tin nhắn.

### Task 6: `support_doc/04-don-hang.md` — Đơn hàng & giao hàng
- **Source:** `Modules/Orders` (StandardOrderStatus, đơn thủ công) · `Modules/Fulfillment` (vận đơn, in tem, quét, lô lấy hàng) · React `pages/OrdersPage` + chi tiết đơn.
- **Live:** menu **Đơn hàng**; bộ lọc; thanh thao tác (theo bộ nhớ: toolbar luôn hiện dưới bộ lọc, validate-by-disable); nút **Chuẩn bị hàng**; tạo **đơn thủ công**; tạo vận đơn; **in tem/phiếu**; **quét mã** đóng gói; **lô lấy hàng**.
- **Dàn ý:** Trạng thái đơn (Chờ thanh toán → Chờ xử lý → Đang xử lý → Chờ bàn giao → Đang vận chuyển → Đã giao → Hoàn tất; nhánh phụ: Giao thất bại, Đang/Đã trả-hoàn, Đã huỷ) → Lọc & chọn đơn → **Chuẩn bị hàng** (chuyển Chờ xử lý→Đang xử lý; bị chặn nếu có mã hàng âm kho) → Tạo đơn thủ công → Giao hàng: tạo vận đơn (sàn hoặc đơn vị riêng), in tem (đúng file gốc), in phiếu lấy hàng (gom theo mã) / phiếu đóng gói (theo đơn), quét mã đóng gói, bàn giao.
- **Dữ kiện:** trừ tồn khi sang **Đang vận chuyển** (không phải lúc "đã đóng gói"); đơn lùi trạng thái bất thường → gắn cờ "có vấn đề".
- **Lỗi thường gặp:** "có mã hàng âm kho nên không Chuẩn bị hàng được"; sản phẩm sàn chưa ghép SKU → đơn "có vấn đề".
- **Xem thêm:** Tồn kho, Sản phẩm & SKU, Hoàn & Hủy.

### Task 7: `support_doc/05-hoan-huy.md` — Hoàn & Hủy
- **Source:** `Modules/Returns` (hoặc trong Orders) · React `pages/ReturnsPage`.
- **Live:** menu **Hoàn & Hủy**; xử lý yêu cầu hoàn/huỷ.
- **Dàn ý:** Khi nào đơn vào Hoàn/Hủy → Xem yêu cầu → Xác nhận hàng về (cộng tồn lại) → Ảnh hưởng tới đối soát/lợi nhuận.
- **Dữ kiện:** huỷ trước giao → nhả tồn; hoàn sau giao & hàng về → cộng tồn lại.
- **Xem thêm:** Đơn hàng, Tồn kho, Đối soát.

### Task 8: `support_doc/06-khach-hang.md` — Khách hàng
- **Source:** `Modules/Customers` · React `pages/CustomersPage`.
- **Live:** menu **Khách hàng**; sổ khách; điểm uy tín.
- **Dàn ý:** Sổ khách là gì → Tìm/xem khách → Hiểu điểm uy tín → Lịch sử đơn của khách.
- **Xem thêm:** Đơn hàng, Tin nhắn.

### Task 9: `support_doc/07-san-pham-sku.md` — Sản phẩm & SKU
- **Source:** `Modules/Catalog`/`Products` + ghép SKU, combo · React `pages/ProductsPage`.
- **Live:** menu **Sản phẩm & SKU**; tạo mã sản phẩm (SKU); **ghép SKU** với sản phẩm trên sàn (listing); tạo **combo**.
- **Dàn ý:** Phân biệt **mã sản phẩm (SKU)** vs **sản phẩm trên sàn (listing)** → Tạo SKU → Ghép SKU với listing (vì sao bắt buộc) → Combo (tồn = số đóng được ít nhất từ thành phần).
- **Dữ kiện:** SKU là đơn vị tồn, nguồn chuẩn; chưa ghép → không đẩy tồn + đơn "có vấn đề".
- **Xem thêm:** Tồn kho, Đơn hàng, Gian hàng.

### Task 10: `support_doc/08-ton-kho.md` — Tồn kho
- **Source:** `Modules/Inventory` (tồn khả dụng, giữ/nhả, đẩy tồn, giá vốn FIFO) · React `pages/InventoryPage`.
- **Live:** menu **Tồn kho**; chỉnh tồn thực/tồn an toàn; xem tồn khả dụng; đẩy tồn lên sàn; lớp giá vốn.
- **Dàn ý:** Công thức **Tồn khả dụng = Tồn thực − Đang giữ cho đơn − Tồn an toàn** (số đẩy lên sàn) → Diễn biến giữ/nhả/trừ/cộng theo trạng thái đơn → Đẩy tồn lên sàn → Giá vốn nhập-trước-xuất-trước (chốt khi giao).
- **Xem thêm:** Sản phẩm & SKU, Mua hàng, Đối soát.

### Task 11: `support_doc/09-mua-hang.md` — Mua hàng
- **Source:** `Modules/Procurement` (nhà cung cấp, đơn mua, nhận hàng, đề xuất nhập) · React `pages/procurement/*`.
- **Live:** **Đề xuất nhập hàng**; **Nhà cung cấp** (tạo NCC); **Đơn mua hàng** (tạo PO, nhận hàng).
- **Dàn ý:** Đề xuất nhập (dựa nhu cầu) → Tạo nhà cung cấp → Tạo đơn mua → **Nhận hàng** (cộng tồn + tạo lớp giá vốn, tự lên sổ kế toán).
- **Gói:** Pro trở lên (mua hàng, giá vốn FIFO).
- **Xem thêm:** Tồn kho, Kế toán.

### Task 12: `support_doc/10-tin-nhan.md` — Tin nhắn
- **Source:** `Modules/Messaging` + `Integrations/Messaging` (Facebook; chat Shopee/TikTok/Lazada) · React `pages/MessagingPage`, components/messaging/*.
- **Live:** **Hộp thư** (hợp nhất); **Kết nối kênh**; **Mẫu tin**; **Tự động trả lời**; **Kịch bản tự động**; **AI training**; nhắn riêng từ bình luận.
- **Dàn ý:** Hộp thư hợp nhất → Kết nối kênh (Facebook + chat sàn) → Mẫu tin → Tự động trả lời (4 kiểu: theo lịch / theo trạng thái đơn / chưa trả lời sau N phút / tin đầu tiên; có chống spam) → Kịch bản tự động (Business) → AI training & trả lời AI (mặc định gợi ý, nhân viên duyệt; chế độ tự gửi chặn tin nhạy cảm: khiếu nại/hoàn tiền/gấp/pháp lý/thô tục).
- **Dữ kiện Facebook:** chỉ gửi tự do trong **24 giờ** kể từ tin cuối của khách; quá hạn cần **thẻ tin nhắn**; nhắn riêng một bình luận chỉ **1 lần/bình luận** ("đã nhắn rồi" là bình thường); nút **Thích** bình luận cần Trang được cấp quyền tương tác.
- **Gói:** AI tự trả lời & kịch bản = Business; hộp thư = Pro.
- **Xem thêm:** Khách hàng, Cài đặt.

### Task 13: `support_doc/11-quang-cao.md` — Quảng cáo
- **Source:** `Modules/Marketing` (Facebook Ads authoring/publish — nhánh hiện tại) · React `pages/marketing/*`.
- **Live:** menu **Quảng cáo**; tạo nội dung quảng cáo (trang/bài đăng), nhắm đối tượng, xem trước, đăng. *(Chỉ ghi tính năng đã có UI; tính năng đang dở thì bỏ qua, không bịa.)*
- **Dàn ý:** Quảng cáo dùng để làm gì → Tạo nội dung → Nhắm đối tượng → Xem trước → Đăng.
- **Xem thêm:** Tin nhắn.

### Task 14: `support_doc/12-doi-soat-loi-nhuan.md` — Đối soát & lợi nhuận
- **Source:** `Modules/Finance` (settlements, 10 nhóm phí) · React `pages/finance/SettlementsPage`.
- **Live:** menu **Đối soát sàn**; đối chiếu phí thật theo đơn; xem lợi nhuận.
- **Dàn ý:** Đối soát là gì → Xem phí thật theo đơn (10 nhóm phí chuẩn) → Công thức **Lợi nhuận = doanh thu − giá vốn − phí − ship − giảm − khác**.
- **Gói:** Pro.
- **Xem thêm:** Báo cáo, Kế toán.

### Task 15: `support_doc/13-bao-cao.md` — Báo cáo
- **Source:** `Modules/Reports` (read-only) · React `pages/ReportsPage`.
- **Live:** menu **Báo cáo**; chọn loại báo cáo; lọc thời gian; xuất (nếu có).
- **Dàn ý:** Các báo cáo có sẵn → Lọc & đọc → Xuất file (nếu có).
- **Xem thêm:** Đối soát, Bảng điều khiển.

### Task 16: `support_doc/14-ke-toan.md` — Kế toán (TT133)
- **Source:** `Modules/Accounting` (sổ kép TT133, kỳ, bút toán, COA, AR/AP, quỹ) · React `pages/accounting/*`.
- **Live:** **Khởi tạo hệ thống tài khoản theo TT133**; Sổ nhật ký; Hệ thống TK; Cân đối phát sinh; Công nợ phải thu/phải trả; Quỹ & Ngân hàng; Báo cáo tài chính; Kỳ kế toán.
- **Dàn ý:** Lần đầu bấm **Khởi tạo hệ thống tài khoản theo TT133** → Sổ nhật ký (bút toán cố định; sửa = ghi đảo) → Hệ thống TK → Cân đối phát sinh → Công nợ AR/AP → Quỹ & Ngân hàng → Báo cáo tài chính → Kỳ kế toán (mở → đóng → khoá).
- **Dữ kiện:** tự lên sổ khi nhận hàng/chuyển kho/kiểm kê; VND, năm dương lịch.
- **Lỗi thường gặp:** "Kỳ kế toán đã đóng nên không ghi thêm được" → mở lại kỳ hoặc ghi vào kỳ đang mở.
- **Gói:** kế toán cơ bản = Pro; nâng cao = Business.
- **Xem thêm:** Mua hàng, Đối soát.

### Task 17: `support_doc/15-goi-thanh-toan.md` — Gói thuê bao & thanh toán
- **Source:** `Modules/Billing` (gói, trial, grace, over-quota) · React `pages/settings/billing` + `OverQuotaBanner`.
- **Live:** trang gói trong **Cài đặt**; nâng/hạ gói; thanh toán (SePay/VNPay).
- **Dàn ý:** Các gói & giới hạn (bảng: Dùng thử/Starter, Pro 199k, Business 399k; số gian hàng 2/5/10) → Trả theo năm = 10 tháng → Nâng/hạ gói → Thanh toán (SePay chuyển khoản, VNPay; MoMo đang phát triển) → Hết hạn: 7 ngày grace rồi về dùng thử miễn phí vĩnh viễn, **dữ liệu không bị khoá**; vượt số gian hàng quá 2 ngày sau khi hạ gói → tạm khoá thao tác ghi.
- **Vai trò:** chỉ **Chủ sở hữu** thanh toán.
- **Xem thêm:** Bắt đầu, Cài đặt.

### Task 18: `support_doc/16-nhat-ky-dong-bo.md` — Nhật ký đồng bộ
- **Source:** sync logs (Channels/Orders jobs) · React `pages/SyncLogsPage`.
- **Live:** menu **Nhật ký đồng bộ**; xem lần đồng bộ, thành công/lỗi.
- **Dàn ý:** Nhật ký đồng bộ cho biết gì → Đọc trạng thái từng lần → Khi thấy lỗi thì làm gì (thử đồng bộ lại; báo CSKH nếu lặp lại).
- **Xem thêm:** Gian hàng.

### Task 19: `support_doc/17-cai-dat.md` — Cài đặt
- **Source:** `SettingsLayout` + các trang con (Nhân sự, Tích hợp, Hồ sơ…) · `system_setting()`.
- **Live:** menu **Cài đặt**; các tab con; đổi hồ sơ/mật khẩu; nhân sự; tích hợp vận chuyển/thanh toán (nếu user chỉnh được).
- **Dàn ý:** Tổng quan các mục Cài đặt → Hồ sơ & đổi mật khẩu → Nhân sự & phân quyền (trỏ về bài Bắt đầu) → Các cấu hình khác user gặp.
- **Xem thêm:** Bắt đầu, Gói thuê bao.

### Task 20: `support_doc/18-tro-giup-cskh.md` — Trợ giúp & CSKH
- **Source:** `Modules/Support` (HelpAssistant RAG, conversations) · React `components/support/HelpChatWidget`, `CskhTab`.
- **Live:** nút **Trợ giúp** nổi (góc màn hình); tab **Hỏi AI**; tab **Hỏi CSKH** (gửi tin + đính kèm ảnh/video/tệp, tối đa 5 tệp).
- **Dàn ý:** Mở widget Trợ giúp → **Hỏi AI** (hỏi cách dùng, có nguồn tham khảo) → **Hỏi CSKH** (chat nhân viên, đính kèm tệp; có chuông báo tin mới) → Khi nào nên hỏi AI vs CSKH.
- **Xem thêm:** tất cả.

> **Mỗi task 3–20 kết thúc bằng commit:**
> ```bash
> git add support_doc/<file>.md support_doc/images/<slug>-*.png
> git commit -m "docs(support): <khu vực>"
> ```

---

## Task 21: Cập nhật `docs_user/` (tham khảo sâu)

**Files:** Modify: `docs_user/what-the-system-does.md`, `system-overview.md`, `business-rules.md`, `user-manual.md`, `frontend-guide.md`, `faq.md`, `troubleshooting.md`, `agent_context.md`

- [ ] **Step 1: Rà từng file** đối chiếu với `_feature-map.md` (Task 2) + 18 bài support_doc. Bổ sung tính năng còn thiếu (đặc biệt **Quảng cáo**), sửa chỗ lệch với hiện trạng.
- [ ] **Step 2: Soát quy tắc §1–6** (không URL/endpoint/bảng/class/mã lỗi trần).
- [ ] **Step 3: Commit**

```bash
git add docs_user/*.md
git commit -m "docs(user): cập nhật docs_user khớp tính năng hiện tại"
```

---

## Task 22: Sinh lại `docs_user/rag_chunks.jsonl` + kiểm tra index

**Files:** Modify: `docs_user/rag_chunks.jsonl`

- [ ] **Step 1: Sinh chunks** — mỗi dòng JSONL hợp lệ: `{"title":..., "module":..., "screen":..., "question":..., "answer":..., "keywords":[...]}`. Tối thiểu cần `title` + `answer`. Phủ Q&A cho **mọi** khu vực §3 của spec (mỗi bài support_doc → vài Q&A: "Làm sao …?", "Tại sao …?"). `answer` tuân thủ §1–6.
- [ ] **Step 2: Kiểm tra parse + index (local, không nhắm prod)**

Run: `cd D:/cmb_core_seller/app; php artisan help:index --fresh`
Expected: in ra "Xong: N chunk…" **không lỗi**. Nếu báo thiếu embedding → bình thường (keyword fallback), miễn không crash.

- [ ] **Step 3: Commit**

```bash
git add docs_user/rag_chunks.jsonl
git commit -m "docs(user): sinh lại rag_chunks.jsonl phủ toàn bộ tính năng"
```

---

## Task 23: Soát cuối & nghiệm thu (Definition of Done)

- [ ] **Step 1: Soát tuân thủ §1–6 toàn bộ** — quét `support_doc/` và `docs_user/` tìm dấu hiệu vi phạm:

Run (gợi ý): tìm URL/endpoint/mã lỗi lọt lưới — rà tay các pattern `/api`, `http`, dấu `/` đầu route, TÊN_HOA_GACH_DUOI, tên bảng/class. Sửa thành tên menu/nút + lời mô tả.

- [ ] **Step 2: Kiểm liên kết** — mọi link "Xem thêm" trỏ đúng file tồn tại; mọi ảnh `images/<…>.png` tồn tại.
- [ ] **Step 3: Đối chiếu DoD** (spec §8) — tick đủ 18 bài + 8 file docs_user + rag_chunks + ảnh + đã đối chiếu live.
- [ ] **Step 4: Dọn tạm** — cân nhắc xoá `docs/superpowers/plans/_feature-map.md` nếu không cần giữ.
- [ ] **Step 5: Commit cuối**

```bash
git add -A support_doc docs_user
git commit -m "docs(support): hoàn thiện & soát cuối tài liệu user/support"
```

---

## Self-review của kế hoạch (đã thực hiện khi viết)

- **Phủ spec:** §3 khu vực ↔ Task 3–20 (18 bài) + Task 21–22 (docs_user + RAG). Đủ.
- **Không placeholder:** dữ kiện nghiệp vụ nhúng sẵn từng task; prose cuối là bản chất việc viết tài liệu (đọc source+live khi thực thi), không phải TODO.
- **Nhất quán tên:** tên menu/nút bám đúng `AppLayout.tsx` + agent_context.md.
- **Ngoài phạm vi:** trang FE "Trung tâm trợ giúp", re-index prod, đa ngôn ngữ — đợt sau (spec §7).
