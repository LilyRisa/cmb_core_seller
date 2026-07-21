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

---
