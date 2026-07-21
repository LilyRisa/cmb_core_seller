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

## Khung layout chung {#khung-layout-chung}

Áp dụng cho **mọi trang** trong app người dùng — Sider + `AppHeader` dùng chung `AppLayout.tsx`. Test trên Dashboard, đại diện cho mọi trang khác (cùng 1 component khung).

### 1. `AppHeader` tràn ngang — mất quyền truy cập menu tài khoản (High)

**File liên quan:** `resources/js/components/AppHeader.tsx` (dòng ~22: `<div style={{ display: 'flex', justifyContent: 'space-between', ... }}>`, không có `flexWrap`, không có xử lý overflow, các con không có `min-width: 0`/ẩn bớt theo độ rộng).

| Viewport | Mức độ | Mô tả |
|---|---|---|
| 1280×800 | OK | Header hiển thị đủ 1 hàng: chọn gian hàng, badge lượt AI, nút Mua/Gia hạn gói, quà tặng, tiện ích Chrome, tải app di động, chuông thông báo, avatar+tên. |
| 1024×768 | **High** | Avatar + tên "Chủ shop demo" (menu tài khoản, chứa cả nút **Đăng xuất**) biến mất hoàn toàn khỏi vùng nhìn thấy — không tràn có scrollbar hiện, mà bị đẩy ra ngoài khung nhìn. Xác nhận qua `browser_snapshot`: phần tử vẫn tồn tại trong DOM (không phải bị ẩn), chỉ là bị tràn ra ngoài viewport. |
| 900×600 | **High (nặng hơn)** | Ngoài avatar/tên, thêm cả icon chuông thông báo, tải app di động, tiện ích Chrome cũng biến mất — chỉ còn icon quà tặng bị cắt nửa. Xác nhận `document.body.scrollWidth` (1090px) > `window.innerWidth` (900px) → có tràn ngang thật. Test cuộn ngang toàn trang (`window.scrollTo`) thì các icon bị ẩn HIỆN RA — nhưng đồng thời **toàn bộ Sidebar điều hướng bên trái cũng bị cuộn mất theo** (vì Sider không sticky theo trục ngang, chỉ sticky theo trục dọc), và tên "Chủ shop demo" bị dồn xuống 3 dòng xấu. |

**Kết luận:** ở tablet ngang chuẩn (1024×768) trở xuống, người dùng **không có cách nào thực tế để đăng xuất hoặc vào Cài đặt qua menu tài khoản** trừ khi biết mẹo cuộn ngang toàn trang (thao tác không trực quan trên tablet/touch, và khi làm vậy sẽ mất luôn điều hướng). Đây là lỗi chặn thao tác thật, không phải giả định.

### 2. Sider cố định 236px không tự thu gọn theo màn hình (Medium)

**File liên quan:** `resources/js/components/AppLayout.tsx` dòng 156 (`<Sider ... width={236} collapsedWidth={64} ... collapsed={collapsed} ...>` — `collapsed` chỉ đổi qua nút bấm thủ công, không có breakpoint tự động).

Ở 900×600, Sider chiếm ~26% chiều rộng màn hình, khiến vùng nội dung Dashboard bị bóp còn 2 cột thẻ số liệu/hàng thay vì 4 cột như ở 1280×800. Chưa "vỡ" hẳn (không tràn, không chồng lấn) nhưng rõ ràng lãng phí diện tích quý giá trên màn hình nhỏ — có nút thu gọn thủ công (`MenuFoldOutlined`) nhưng người dùng phải tự biết bấm, không tự động theo kích thước màn hình như một app tối ưu tablet nên có.

**Ảnh chụp:** `dashboard-1280x800.png`, `dashboard-1024x768.png`, `dashboard-900x600.png`, `dashboard-900x600-scrolled.png` (thư mục `.playwright-mcp`/`tablet-audit` cục bộ, không commit).

**Ghi chú phương pháp:** từ mục này trở đi, do lỗi khung layout ở trên áp dụng cho MỌI trang, các trang sau chỉ audit trọng tâm ở 1024×768 (tablet ngang chuẩn) và chỉ chụp thêm 1280×800/900×600 khi phát hiện khác biệt đáng chú ý — thay vì lặp lại đủ 3 viewport × mọi trang.

## Đơn hàng & Hoàn hủy {#đơn-hàng--hoàn-hủy}

### `/orders?tab=pending` — Danh sách đơn hàng

| Viewport | Mức độ | Mô tả |
|---|---|---|
| 1280×800 | Low | Thanh nút hành động (Chuẩn bị hàng/Nhận phiếu/In phiếu/Sẵn sàng bàn giao/Liên kết SKU/Hủy đơn) đã wrap 2 hàng ngay ở 1280 — hơi sớm nhưng vẫn đọc được, không chồng lấn. |
| 1024×768 | Medium | Thanh tab trạng thái đơn ("Tất cả/Chờ xử lý/Đang xử lý/Chờ bàn giao/Đang giao/Đã giao") bị AntD tự cắt bớt, chữ "Đang giao" hiển thị cụt thành "Đang g" ngay cạnh nút "..." (xem thêm) — nút "..." có tồn tại và hoạt động (xác nhận qua DOM: `.ant-tabs-nav-more` tồn tại, `display:block`) nhưng độ tương phản quá thấp, dễ bị hiểu nhầm là chữ bị lỗi/cắt hỏng thay vì "còn tab ẩn, bấm vào xem". |
| 900×600 | Low | Tương tự nhưng nút "..." rõ hơn do có nhiều khoảng trắng quanh; ô "Tuỳ chỉnh từ → đến" (bộ lọc ngày) bị cắt bớt phần "đến" sát mép phải. |

### `/orders/new` — Tạo đơn thủ công

| Viewport | Mức độ | Mô tả |
|---|---|---|
| 1024×768 | OK | Bố cục 3 cột (Sản phẩm / Thông tin / Thanh toán+Ghi chú+Khách hàng) vẫn giữ được, chỉ tiêu đề panel "Khách hàng" bị cắt thành "Khách ..." — cosmetic. |
| 900×600 | Low | Tự xếp lại 1 cột dọc hợp lý, không vỡ. Nút "Lưu" ở thanh dưới cùng bị cắt phím tắt (hiện "F" thay vì đầy đủ) — cosmetic. |

### `/orders/:id` — Chi tiết đơn (test đơn `23119`)

| Viewport | Mức độ | Mô tả |
|---|---|---|
| 1280×800 | OK | Bảng sản phẩm hiển thị tên SP wrap 2 dòng bình thường, tỉ lệ cột hợp lý. |
| **1024×768** | **High** | **Cột "SẢN PHẨM" trong bảng bị bóp quá hẹp** so với 3 cột còn lại (ĐƠN GIÁ/SL/THÀNH TIỀN) — tên sản phẩm dài ("WEAH-3003 Mạch phân tần 3 đường tiếng bass treble mid công suất 250W...") bị bẻ dòng từng 1-2 từ/dòng, kéo dài thành ~17 dòng dù bên phải bảng còn thừa khoảng trắng ngang rõ rệt. Bảng rõ ràng không dùng tỉ lệ cột linh hoạt theo độ rộng khả dụng — chỉ xảy ra khi màn thu hẹp xuống ~1024px (1280px vẫn ổn), đúng loại lỗi "chỉ lộ ra ở tablet" mà audit này nhắm tới. |

### `/returns` — Đơn Hoàn & Hủy

| Viewport | Mức độ | Mô tả |
|---|---|---|
| **1024×768** | **High** | Cột "ĐƠN" (mã đơn) bị bóp hẹp, mã đơn dài bị bẻ dòng từng cụm 5-6 ký tự (giống lỗi ở trang chi tiết đơn). Nghiêm trọng hơn: **nút hành động thứ 2 cạnh nút "Duyệt" (khả năng là "Từ chối") bị tràn ra ngoài biên card trắng**, chỉ còn thấy 1 viền mảnh sát mép phải card — xác nhận qua DOM: `.ant-table-content` có `scrollWidth` (789px) > `clientWidth` (702px) và `overflow-x: visible` (không phải `hidden`/`auto`) → nội dung tràn ra ngoài khung một cách "vô hình", không có scrollbar hay dấu hiệu nào cho biết còn nút bị che. Người dùng khả năng không thao tác được nút này ở độ rộng này. |

**Ảnh chụp:** `orders-1280x800.png`, `orders-1024x768.png`, `orders-900x600.png`, `orders-new-1024x768.png`, `orders-new-900x600.png`, `order-detail-1280x800.png`, `order-detail-1024x768.png`, `returns-1024x768.png`.

---

## Khách hàng, Gian hàng, Sản phẩm {#khách-hàng-gian-hàng-sản-phẩm}

### `/customers` — Danh sách khách hàng

| Viewport | Mức độ | Mô tả |
|---|---|---|
| 1280×800 VÀ 1024×768 | Medium (không phải lỗi responsive) | Cột "KHÁCH HÀNG" bị bẻ ký tự dọc thành ~20 dòng, 1 dòng bảng chiếm hết chiều cao viewport — xảy ra **giống hệt nhau ở cả 1280 lẫn 1024**, tức đây KHÔNG phải lỗi do màn hẹp mà là lỗi thiếu `ellipsis`/xử lý chuỗi dài không khoảng trắng ở cột tên khách hàng (dữ liệu thật: tên khách nối liền không dấu cách kiểu "KelangbatCodon0923..."). Vẫn đáng sửa vì ảnh hưởng UX nghiêm trọng, nhưng cần phân loại đúng là lỗi robustness chung, không phải lỗi "chỉ lộ ở tablet". |

### `/channels` — Gian hàng

| Viewport | Mức độ | Mô tả |
|---|---|---|
| 1024×768 | OK | Layout dạng card (không phải Table), hiển thị đầy đủ, không tràn/vỡ. |

### `/products` → redirect `/inventory?tab=skus` — Sản phẩm & SKU

| Viewport | Mức độ | Mô tả |
|---|---|---|
| **1024×768** | **High** | Bảng danh mục SKU bị cắt cột "THỰC C(òn)..." ngay giữa chữ ở mép phải, còn nhiều cột nữa bên phải không thấy được. Xác nhận qua DOM: `.ant-table-content` có `overflow-x: visible` và `scrollWidth` (1034px) vượt `clientWidth` (702px) tới 332px — **đúng cùng 1 nguyên nhân gốc với lỗi ở `/returns`**: Table không cấu hình `scroll={{ x: ... }}`, nên phần tràn không có scrollbar riêng, chỉ có thể thấy bằng cách cuộn ngang CẢ TRANG (kéo theo mất sidebar, xem mục Khung layout chung). |

**Ảnh chụp:** `customers-1024x768.png`, `customers-1280x800.png`, `channels-1024x768.png`, `products-1024x768.png`.

---

## Tin nhắn {#tin-nhắn}

### `/messaging` — Hộp thư

| Viewport | Mức độ | Mô tả |
|---|---|---|
| 1024×768 | OK | Layout 2 cột (danh sách hội thoại + khung chat) hoạt động tốt, mở 1 hội thoại vẫn đọc được, không vỡ. Thanh tab trên cùng (Hộp thư/Kết nối kênh/.../Cài đặt AI) hơi tràn nhẹ ở mục cuối "Cài đặt AI" — Low, không chặn thao tác (vẫn là link điều hướng riêng theo route, không phải nội dung ẩn). |

### `/messaging/flows` — Kịch bản tự động

| Viewport | Mức độ | Mô tả |
|---|---|---|
| **1024×768** | **High** | **Cùng lỗi bảng ở trên nhưng lần này xảy ra ngay với TIÊU ĐỀ CỘT**: header "TÊN"/"KỊCH BẢN..." và "CẬP NHẬT" bị bẻ ký tự dọc thành cột chữ cao ngất, chiếm phần lớn chiều cao bảng dù cột "PHẠM VI TRANG"/"TRẠNG THÁI" bên cạnh còn dư khoảng trắng. Xác nhận thêm 1 bảng nữa có cùng gốc bệnh — tần suất xuất hiện cao dần cho thấy đây là lỗi **toàn hệ thống** ở component Table dùng chung, không phải lỗi riêng từng trang. |

### `/messaging/templates` — Mẫu tin

| Viewport | Mức độ | Mô tả |
|---|---|---|
| 1024×768 | OK | Bảng cột ngắn (MÃ/TÊN/NỘI DUNG/ẢNH/BẬT), không đủ dài để kích hoạt lỗi wrap — củng cố giả thuyết: lỗi chỉ lộ ra khi có cột tiêu đề/nội dung nhiều từ. |

### `/messaging/auto-rules` — Tự động trả lời

| Viewport | Mức độ | Mô tả |
|---|---|---|
| **1024×768** | **High** | Header "TÊN" và "NỘI DUNG" bị bẻ ký tự dọc y hệt `/messaging/flows` — thêm 1 bằng chứng nữa cho lỗi hệ thống. |

**Chưa audit sâu trong đợt này** (do đã đủ bằng chứng cho lỗi hệ thống, ưu tiên thời gian cho các nhóm trang khác): `/messaging/channels`, `/messaging/utility-templates`, `/messaging/knowledge`.

**Ảnh chụp:** `messaging-1024x768.png`, `messaging-conv-1024x768.png`, `messaging-flows-1024x768.png`, `messaging-templates-1024x768.png`, `messaging-autorules-1024x768.png`.

---

## Đăng bán sàn {#đăng-bán-sàn}

Cả 4 trang đều là Table — 3/4 trang dính lỗi wrap ký tự dọc giống hệt các mục trên (cột "THƯƠNG HIỆU"/"NGÀNH HÀNG"/"GIAN HÀNG"/"CHIẾN DỊCH"). Riêng `on-channel` code đã làm ĐÚNG (ellipsis) — bằng chứng cho thấy cách sửa đã tồn tại sẵn trong codebase, chỉ cần áp dụng nhất quán.

| Trang | Viewport | Mức độ | Mô tả |
|---|---|---|---|
| `/marketplace/products` | 1024×768 | **High** | Header "THƯƠNG HIỆU", "NGÀNH HÀNG" bẻ ký tự dọc; nghi ngờ còn cột/nút bị tràn ẩn bên phải (thấy 1 vạch chia cắt ở mép). |
| `/marketplace/to-push` | 1024×768 | **High** | Cột "GIAN HÀNG" chứa tên gian hàng dài ("CMB audio - lộc phát") bẻ ký tự dọc, kéo 1 hàng cao hơn 250px; nút xoá ở cuối hàng bị cắt mất 1 phần. |
| `/marketplace/on-channel` | 1024×768 | **OK (mẫu tốt)** | Cột "SẢN PHẨM" dùng ellipsis đúng cách ("Mạch loa D10 Bluetooth 5.0 hát Karaoke công s..."), không wrap vỡ dòng — nên dùng làm mẫu tham chiếu khi sửa các bảng khác. |
| `/marketplace/promotions` | 1024×768 | **High** | Header "CHIẾN DỊCH" bẻ ký tự dọc y hệt các bảng trên (trang test không có dữ liệu chiến dịch nên chưa thấy hàng dữ liệu thật, nhưng lỗi header đã đủ rõ). |

**Ảnh chụp:** `mp-products-1024x768.png`, `mp-topush-1024x768.png`, `mp-onchannel-1024x768.png`, `mp-promotions-1024x768.png`.

---
