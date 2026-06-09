# Tài liệu TikTok Marketing API (Ads) — bản local đầy đủ (v1.3)

Bản sao đầy đủ và chính xác toàn bộ tài liệu hướng dẫn (guide docs) của
**TikTok API for Business / Marketing API** dùng để phát triển tính năng
**quản lý quảng cáo TikTok** trong CMBcoreSeller.

- **Nguồn gốc:** https://business-api.tiktok.com/portal/docs/about-the-guide/v1.3
- **Phiên bản API:** v1.3 (ngôn ngữ nội dung: ENGLISH — bản gốc)
- **Tổng số trang:** 1143 trang (tải đầy đủ, không thiếu trang nào)
- **Cách tải:** lấy trực tiếp từ API nội dung chính thức của TikTok
  (`/gateway/api/doc/client/platform/tree/get` để lấy cây điều hướng và
  `/gateway/api/doc/client/node/get` để lấy nội dung từng trang), nên nội dung
  **đúng nguyên văn** với trang web, không qua chuyển đổi HTML→text gây mất mát.

## Cách dùng

- Mở [`INDEX.md`](INDEX.md) để xem **mục lục đầy đủ** theo đúng thứ tự và phân cấp
  như trên trang web (có link tới từng file).
- Cây thư mục phản chiếu 1-1 cây điều hướng của tài liệu gốc. Mỗi mục có con sẽ là
  một **thư mục** chứa `README.md` (nội dung trang cha) + các trang con. Số thứ tự
  `00-`, `01-`… ở đầu tên giữ đúng thứ tự hiển thị trên web.
- Mỗi file có **frontmatter** ghi `title`, `doc_id` (ID gốc trên TikTok) và `path`
  (đường dẫn breadcrumb) để truy vết.

## Bản đồ thư mục cấp 1

| Thư mục | Nội dung |
|---|---|
| `00-About-the-Guide.md` | Giới thiệu cách đọc tài liệu |
| `01-Overview` | Versioning, V1.3 updates, Migrate to v1.3, What's New |
| `03-Get-Started` | Tạo tài khoản, đăng ký developer, **Authorization/Authentication**, rate limits, permissions, sandbox, Postman |
| `05-Use-Cases` | Hướng dẫn tạo từng loại quảng cáo (Traffic, Lead Gen, Shopping/Video Shopping, GMV Max, Smart+, Search Ads, Spark Ads…) — **rất hữu ích để dựng flow tạo ads** |
| `06-Marketing-API` | **Lõi chính:** Business Center, Creatives, Catalog Management, TikTok Store, **Campaign Management** (Campaign/Ad Group/Ad), Audience, Targeting, Reporting, Tools, Bid & Optimization, Identity, Comments… mỗi mục có Overview + Guides + **API reference** |
| `07-Organic-API` | API nội dung organic (ngoài quảng cáo) |
| `08-Business-Messaging-API` | API nhắn tin doanh nghiệp |
| `09-API-Reference` | **Đặc tả endpoint chi tiết** (request/response, tham số) gom theo nhóm |
| `10-API-Playground` | Hướng dẫn dùng playground |
| `13-Appendix` | **Bảng tra cứu quan trọng:** Return codes, HTTP status, Enumerations, Permission scope, Location IDs/code, Time zone, Interest category, Industries… |

## Quy ước cú pháp trong nội dung (giữ nguyên từ TikTok)

Nội dung giữ đúng cú pháp khối tùy biến của trình xem tài liệu TikTok. **Không** chỉnh
sửa để tránh mất thông tin. Khi đọc cần hiểu:

- ```` ```xtable ```` — một bảng (bên trong là bảng Markdown chuẩn). Trong tiêu đề cột,
  `{30%}` là gợi ý độ rộng cột; `{Required}` / `{Optional}` đứng sau tên trường là cờ
  bắt buộc/tùy chọn. Dòng bắt đầu bằng `#|` là **trường con lồng nhau** (thuộc object/array
  của trường ngay phía trên).
- ```` ```xcodeblock ```` — khối ví dụ code; bên trong dạng `(code <lang>) … (/code)`.
- ```` ```xquote ```` — khối trích dẫn/ghi chú.
- Trong ô bảng có thể chứa HTML inline (`<br>`, `<strong>`, `<span style=…>`) và link
  Markdown — đây là nguyên văn từ nguồn.

## Lưu ý phát triển (liên hệ kiến trúc CMBcoreSeller)

- Đây là **integration**, không phải core: connector TikTok Ads phải nằm ở
  `app/app/Integrations/Channels/` (hoặc trục phù hợp), map payload thô → DTO chuẩn,
  **core không được biết tên "tiktok"** (xem `docs/01-architecture/extensibility-rules.md`).
- Mọi tính năng Marketing/ads phải **plan-gated** (xem memory dự án).
- Base URL endpoint: `https://business-api.tiktok.com/open_api/v1.3/...`
- Bắt đầu đọc: `03-Get-Started/Authorization` + `Authentication`, sau đó
  `06-Marketing-API/Campaign-Management` và `09-API-Reference`.
