# Bộ tài liệu API: Shopee · Lazada · TikTok Shop

Bộ tài liệu phát triển (developer documentation) **đầy đủ** của ba sàn thương mại điện tử, được cào tự động
bằng **trình duyệt Playwright (Chromium)**. Cả ba trang gốc đều chặn fetch HTTP thông thường / AI fetch,
nên toàn bộ nội dung được lấy bằng cách render bằng trình duyệt thật rồi đi qua **tất cả các trang trong menu**
(không chỉ trang ở link gốc), sau đó chuyển sang Markdown.

> Ngày thực hiện: **2026-05-21**. Công cụ: Node.js 24 + Playwright + Turndown (HTML→Markdown).

## Tổng quan

| Thư mục | Nguồn | Số trang | Dung lượng | Nội dung |
| --- | --- | --- | --- | --- |
| [`shopee/`](./shopee/README.md) | open.shopee.com/developer-guide | **83** | ~0.86 MB | Developer Guide (48) + Push Mechanism (34) + chỉ mục API Reference |
| [`lazada/`](./lazada/README.md) | open.lazada.com/apps/doc/api | **383** | ~1.63 MB | Toàn bộ 383 API endpoint trên 34 nhóm danh mục (full param/response/error/code mẫu) |
| [`tiktok/`](./tiktok/README.md) | partner.tiktokshop.com/docv2 | **628** | ~4.48 MB | API Reference (305) + Changelog (147) + Partner Guide (85) + Webhooks (43) + Developer Guide (37) + FAQs (11) |
| **Tổng** | | **1.094** | ~6.97 MB | |

Mỗi thư mục có:
- `INDEX.md` — danh mục đầy đủ mọi trang (nhóm theo chuyên mục), kèm link tới từng file.
- `README.md` — tổng quan riêng của sàn đó.
- `_manifest.json` (và `_paths.json`/`_entries.json`) — dữ liệu máy đọc.
- Một file `.md` cho mỗi trang tài liệu, có header chuẩn: `Source` (URL gốc), `Category`/`Section`, thời điểm cào.

## Chi tiết từng sàn

### 🛒 Shopee — `shopee/`
Trang Developer Guide dùng sidebar điều hướng bằng `data-ts-content_id` (không phải thẻ `<a>`), nên crawler
bung toàn bộ danh mục rồi thu thập từng `content_id` → `/developer-guide/{id}`. Push Mechanism là SPA một-URL,
được lấy bằng cách click từng mục sự kiện. API Reference (28 module / 406 method) được lập chỉ mục trong
`shopee/API-REFERENCE-MODULES.md`.

### 📦 Lazada — `lazada/`
Trang API Explorer có cây danh mục có thể bung. Crawler bung **toàn bộ** cây → thu được 383 endpoint trên
34 nhóm (FBL, Logistics, Product, LazPay, Order, Finance...). Mỗi endpoint gồm: mô tả, Service Endpoints,
Common/Request/Response Parameters, Error Codes và code mẫu (Java/PHP/.NET/Ruby/Python/cURL). Bảng đã được
làm sạch để hiển thị đúng định dạng Markdown.

### 🎵 TikTok Shop — `tiktok/`
Tài liệu chia thành nhiều tab/chuyên mục. Crawler duyệt tất cả tab điều hướng, bung sidebar từng mục và thu
628 trang. Trang API Reference (spec endpoint) render ở container riêng (`#scrollIntersectionCenter` / `api-doc-*`)
nên được trích xuất riêng để lấy đủ bảng tham số request/response. 6 trang gần như rỗng ngay trên trang gốc
(chỉ là placeholder) được giữ phần tiêu đề + URL.

## Cách tái tạo / cào lại
Các script đặt trong [`scrape/`](./scrape/):
- `crawl.js` — crawler tổng quát theo config (`scrape/configs/*.json`).
- `crawl-shopee.js` — crawler riêng cho Shopee Developer Guide.
- `crawl-lazada-clean.js` — crawler Lazada (bung cây + làm sạch bảng).
- `crawl-tiktok*.js` — crawler TikTok (duyệt mọi tab + API Reference).

Chạy (PowerShell, cần Node trong PATH):
```powershell
$env:Path = "C:\Program Files\nodejs;" + $env:Path
cd C:\Users\admin\Desktop\app_cmb_ko_xoa\scrape
node crawl-shopee.js
node crawl-lazada-clean.js
node crawl.js --config configs/tiktok.json
```

> Tài liệu sinh tự động phục vụ tham khảo/offline. Nguồn chính thức luôn là trang developer của từng sàn.
