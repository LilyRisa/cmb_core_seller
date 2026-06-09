# SPEC 2026-06-09 — Tách màn TikTok Ads riêng khỏi Facebook (FE)

**Mục tiêu:** Tách màn hình quảng cáo TikTok thành trang riêng, không dùng chung trang với Facebook (trang chung khó mở rộng vì FB có drafts/monitors/AI, TikTok phát triển hướng khác). Backend giữ nguyên (provider-agnostic).

## Thay đổi (chỉ frontend)

### Định tuyến & menu
- Nhóm menu cha **"Quảng cáo"** với 2 con: **Quảng cáo Facebook** (`/marketing`, icon FacebookFilled) + **Quảng cáo TikTok** (`/marketing/tiktok`, icon TikTokOutlined). (`AppLayout.tsx`, `BASE_KEYS`).
- Route mới `/marketing/tiktok` → `TikTokAdsDashboardPage` (`app.tsx`).

### Trang mới `pages/TikTokAdsDashboardPage.tsx` (read-only)
Orchestration riêng, không if-provider:
- Kết nối (`useConnectTikTokAds` + popup OAuth, xử lý `connected/error=tiktok_marketing`).
- Chọn tài khoản: Select advertiser TikTok (lọc `accounts.provider==='tiktok'`; TikTok không có BM).
- Báo cáo: tab cấp campaign/adgroup(→adset)/ad + khoảng ngày; view "Bảng phẳng" (bảng read-only) & "Cây phân cấp" (tái dùng `ReportTree` với `canMonitor={false}`).
- Đối soát: `useAdReconciliation` + bảng theo ngày.
- Không sửa inline / giám sát / drafts / AI (connector TikTok read-only).

### Component dùng chung trung tính
- Tách formatter thuần ra `pages/marketing/format.ts`: `money/num/pct/dec`, `LABELS`, `STATUS_VI/statusVi` (+ ENABLE/DISABLE TikTok), `OBJECTIVE_VI/objectiveVi` (+ objective TikTok), `ALL_COLUMNS/DEFAULT_COLUMNS/COL_TITLE/COL_HELP`. Cả 2 trang import (không lặp).
- `ReportTree` tái dùng nguyên trạng (monitor đã gated bởi `canMonitor`).
- `ConnectionManagerDrawer` thêm prop `provider` để lọc tài khoản hiển thị theo provider.

### Trang Facebook `MarketingDashboardPage.tsx`
- Lọc `accounts.provider==='facebook'` (TikTok không lẫn vào nhóm BM).
- Gỡ phần TikTok đã chèn trước đó: nút/handler/ADS_ERRORS/icon TikTok, `readOnlyProvider/canEdit` → trả về `canConnect`.
- Dùng formatter chung từ `format.ts` (gỡ định nghĩa cục bộ). `ConnectionManagerDrawer provider="facebook"`.

## Backend
**Không đổi.** Route đọc dùng chung vẫn resolve theo `account.provider`; mỗi trang FE lọc client-side theo provider.

## Verify
Không có FE test runner — verify bằng `tsc` (pass), `eslint` (0 error), `vite build` (pass).

## Ngoài phạm vi
Write cho TikTok (tạo/sửa quảng cáo), AI/monitor cho TikTok — sẽ là tính năng riêng của trang TikTok về sau (nay đã tách nên dễ mở rộng).
