# Spec — Bổ sung `docs_user/` & tạo `support_doc/` (nội dung trang support)

> Ngày: 2026-06-04 · Trạng thái: đã duyệt thiết kế, chờ review spec
> Phạm vi đã chốt với người dùng: **Phương án A** (làm NỘI DUNG trước; trang FE "Trung tâm trợ giúp" để đợt sau) · **có ảnh chụp màn hình từng bước**.

## 1. Mục tiêu

Tạo bộ tài liệu hướng dẫn sử dụng **đầy đủ, chính xác, cực dễ hiểu** cho người dùng cuối (người bán / nhân viên, đa số không rành kỹ thuật) của CMBcoreSeller, gồm hai phần:

1. **Cập nhật `docs_user/`** — nguồn tham khảo sâu + dữ liệu nuôi trợ lý "Hỏi AI" (RAG). Phải phủ **100%** tính năng đang có.
2. **Tạo `support_doc/`** — bộ **bài viết hướng dẫn cho người dùng đọc**, tổ chức theo đúng menu, kèm **ảnh chụp màn hình thật** từng bước. Đây là nội dung sẽ dùng để dựng "trang support" ở một đợt sau.

Nội dung phải **khớp với luồng logic trong source code** và **khớp với giao diện thật** tại https://app.cmbcore.com (xác minh bằng thao tác trực tiếp trên tài khoản demo `owner@demo.local`).

## 2. Nguyên tắc bất di bất dịch (PR-blocking về nội dung)

Theo quy tắc đã có của hệ thống (xem `docs_user/agent_context.md §0` và bộ nhớ dự án):

- **Chỉ chỉ đường bằng tên menu + nhãn nút tiếng Việt** mà người dùng thấy trên màn hình. VD: *"Vào menu **Gian hàng** → bấm **Kết nối TikTok**"*.
- **TUYỆT ĐỐI KHÔNG** nhắc: đường dẫn URL (kiểu `/orders`), endpoint/API, tên bảng dữ liệu, tên hàm/lớp, hay mã lỗi viết hoa trần.
- **Lỗi** mô tả bằng lời dễ hiểu (VD "Kỳ kế toán đã đóng nên không ghi thêm được"), không đọc mã lỗi.
- Tính năng nâng cao: nhắc **gói cần có** (Pro/Business) và **vai trò cần có** bằng tên tiếng Việt.
- Câu ngắn, từng bước, xưng "bạn". Không bịa; không chắc thì mời dùng **Trợ giúp → Hỏi CSKH**.

## 3. Khu vực tính năng cần phủ (xương sống = menu thật)

Lấy từ `app/resources/js/components/AppLayout.tsx` (đã đối chiếu live):

- **Tổng quan**: Bảng điều khiển.
- **Bán hàng**: Đơn hàng · Hoàn & Hủy · Khách hàng · Tin nhắn (Hộp thư / Kết nối kênh / Mẫu tin / Tự động trả lời / Kịch bản tự động / AI training) · Gian hàng · Sản phẩm & SKU.
- **Kho & Mua hàng**: Tồn kho · Đề xuất nhập hàng · Nhà cung cấp · Đơn mua hàng.
- **Báo cáo & Kế toán**: Báo cáo · Quảng cáo · Đối soát sàn · Sổ nhật ký · Hệ thống TK · Cân đối phát sinh · Công nợ phải thu · Công nợ phải trả · Quỹ & Ngân hàng · Báo cáo tài chính · Kỳ kế toán.
- **Hệ thống**: Nhật ký đồng bộ · Cài đặt (gồm Nhân sự/phân quyền, Gói & thanh toán, Tích hợp…).
- **Trợ giúp**: widget nổi "Hỏi AI" + "Hỏi CSKH".

Ngoài menu, phủ thêm các luồng nằm trong trang chi tiết: **Chuẩn bị hàng**, vận đơn/in tem/quét/lô lấy hàng (trong Đơn hàng/Giao hàng), ghép SKU & combo (trong Sản phẩm/Tồn kho), nhận hàng & giá vốn FIFO (trong Mua hàng), khởi tạo hệ thống tài khoản TT133 (trong Kế toán).

## 4. Deliverable 1 — Cập nhật `docs_user/`

Rà & cập nhật cho khớp 100% tính năng hiện tại (gồm cả **Quảng cáo / Facebook Ads** đang phát triển ở nhánh hiện thời, ở mức tính năng đã có UI):

- `what-the-system-does.md`, `system-overview.md`, `business-rules.md`, `user-manual.md`, `frontend-guide.md`, `faq.md`, `troubleshooting.md`, `agent_context.md`.
- Sinh lại **`docs_user/rag_chunks.jsonl`**: mỗi dòng JSONL = `{title, module, screen, question, answer, keywords[]}` (định dạng do `HelpIndexer` yêu cầu; chỉ cần `title` + `answer` là tối thiểu). Phủ Q&A cho mọi khu vực ở §3.
- **Kiểm tra parse**: chạy `php artisan help:index --fresh` ở local (SQLite, keyword fallback) để chắc file JSONL hợp lệ và index không lỗi. *Re-index trên prod là bước khi deploy — KHÔNG chạy lệnh nhắm vào prod trong task này.*

## 5. Deliverable 2 — Tạo `support_doc/`

### 5.1 Cấu trúc thư mục

```
support_doc/
  README.md                  # mục lục các bài (dùng dựng trang sau)
  images/                    # ảnh chụp màn hình thật, tên: <slug-bài>-<số-bước>.png
  01-bat-dau.md
  02-bang-dieu-khien.md
  03-gian-hang.md
  04-don-hang.md
  05-hoan-huy.md
  06-khach-hang.md
  07-san-pham-sku.md
  08-ton-kho.md
  09-mua-hang.md
  10-tin-nhan.md
  11-quang-cao.md
  12-doi-soat-loi-nhuan.md
  13-bao-cao.md
  14-ke-toan.md
  15-goi-thanh-toan.md
  16-nhat-ky-dong-bo.md
  17-cai-dat.md
  18-tro-giup-cskh.md
```

### 5.2 Khuôn chuẩn mỗi bài (markdown)

```
---
title: <Tiêu đề ngắn, theo tên khu vực>
slug: <kebab-case khớp tên file>
menu: <đường dẫn menu tiếng Việt, vd "Bán hàng → Đơn hàng">
plan: <Miễn phí | Pro | Business>      # gói tối thiểu, nếu có
roles: [<vai trò tiếng Việt cần có>]    # nếu có
---

# <Tiêu đề>

**Việc này giúp gì:** 1–2 câu.

**Bạn cần:** (gói + vai trò, nếu có; bỏ qua nếu ai cũng dùng được).

## Các bước
1. <Một câu mệnh lệnh, bắt đầu bằng động từ> 
   ![mô tả ảnh](images/<slug>-1.png)
2. ...

## Mẹo
- ...

## Lỗi thường gặp & cách xử lý
- **Triệu chứng (mô tả bằng lời):** cách xử lý.

## Xem thêm
- [<Bài liên quan>](<file>.md)
```

Quy ước viết:
- Mỗi bước **một hành động**, bắt đầu bằng động từ ("Bấm…", "Chọn…", "Nhập…").
- Tên nút/menu **in đậm** đúng chữ trên màn hình.
- Ảnh đặt ngay dưới bước tương ứng; chỉ chèn ảnh cho bước có ý nghĩa trực quan (không cần ảnh cho mỗi câu).

### 5.3 Quy ước ảnh chụp

- Chụp từ tài khoản demo thật, độ phân giải ổn định (đặt viewport cố định, vd 1440×900).
- Tên: `images/<slug-bài>-<số-bước>.png` (vd `04-don-hang-3.png`).
- Che/ô tránh lộ dữ liệu nhạy cảm nếu có (số điện thoại khách… cân nhắc khi gặp).

## 6. Quy trình xác minh (bắt buộc cho từng bài)

Với mỗi khu vực:
1. **Đọc source** module tương ứng (Controllers/Services/Requests + trang React) để hiểu đúng luồng, điều kiện, nút, gói/quyền.
2. **Đăng nhập demo**, thao tác **đầy đủ** tới màn hình đó — kể cả **tạo/sửa thử** (đã được cho phép) để xác minh luồng end-to-end.
3. **Đối chiếu** nhãn nút + hành vi giữa source ↔ giao diện thật; ghi lại đúng chữ tiếng Việt.
4. **Chụp ảnh** các bước → lưu `support_doc/images/`.
5. Viết bài theo khuôn §5.2.

Lưu ý kỹ thuật khi thao tác live:
- Trình duyệt (Playwright) chạy **tuần tự** một phiên → phần duyệt live không song song được; phần **đọc/đối chiếu source có thể chạy song song** bằng nhiều agent để lập "bản đồ tính năng" trước.
- Tài khoản demo là môi trường prod-demo: thao tác tạo/sửa giữ ở mức **vừa đủ minh hoạ**, ưu tiên không phá dữ liệu mẫu quan trọng.

## 7. Ngoài phạm vi (đợt sau)

- Trang "Trung tâm trợ giúp" trong SPA (React route `/support`, render bài viết + tìm kiếm, link menu/header/widget). Đã thống nhất làm ở **đợt riêng** sau khi nội dung xong.
- Re-index RAG trên prod (thuộc quy trình deploy).
- Đa ngôn ngữ (chỉ tiếng Việt).

## 8. Tiêu chí hoàn thành (Definition of Done)

- [ ] 8 file `docs_user/*.md` được rà & cập nhật khớp tính năng hiện tại.
- [ ] `docs_user/rag_chunks.jsonl` sinh lại, phủ mọi khu vực §3; `php artisan help:index --fresh` chạy **không lỗi** ở local.
- [ ] `support_doc/` có README mục lục + 18 bài theo §5.1, đúng khuôn §5.2, **không vi phạm** §2 (không URL/endpoint/bảng/class/mã lỗi trần).
- [ ] Mỗi bài có ảnh chụp thật cho các bước trực quan, lưu đúng quy ước §5.3.
- [ ] Mỗi bài đã được **đối chiếu với giao diện thật** (đã đăng nhập & thao tác).
- [ ] Spell-check tiếng Việt cơ bản; câu từ đơn giản, từng bước.

## 9. Rủi ro & giảm thiểu

- **UI đổi làm ảnh cũ:** chấp nhận; ảnh chỉ minh hoạ, chữ là chính (bám tên menu/nút).
- **Khối lượng lớn (18 bài + 8 file + ảnh):** chia theo khu vực, làm tuần tự theo nhóm; có thể chạy nhiều phiên, spec này giúp resume.
- **Lộ dữ liệu nhạy cảm trong ảnh:** rà nhanh trước khi chèn.
- **Thao tác tạo/sửa trên demo prod:** giữ tối thiểu, tránh xoá dữ liệu mẫu.
