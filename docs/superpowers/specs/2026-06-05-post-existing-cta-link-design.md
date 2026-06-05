# Hiển thị CTA/đường dẫn hiện hữu của bài viết khi chọn — sub-feature D (phần 1) — Design

> Khi chọn bài viết ở bước Nội dung, hiển thị ngay "đích" mà bài viết đang gắn: **đường link đã gắn sẵn** hoặc **nút "Gửi tin nhắn"** — để người dùng thấy CTA hiện hữu trước khi xuất bản.
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog `_ads-manager-parity-backlog.md`, chủ uỷ quyền). Làm trên `main`.

## 1. Phạm vi
- **Trong phạm vi (phần 1 — yêu cầu rõ của chủ):** đọc CTA/đường dẫn hiện hữu của post và hiển thị trên thẻ "Bài viết đã chọn" (badge "Đường dẫn: …" hoặc "Hành động: Gửi tin nhắn").
- **Ngoài phạm vi (phần 2 — follow-up D):** Pixel + sự kiện chuyển đổi (conversion event) đầy đủ. Ghi nhận để làm sau; không đụng ở phần này.

## 2. Dòng dữ liệu hiện tại (pass-through)
Graph `published_posts` → `PagePostDTO` → `AdAuthoringController::pagePosts` (mảng) → FE `AdPagePost` → `PagePostPickerModal` (`PickResult`) → `StepCreative` (`PickedPostSummary`, thẻ "Bài viết đã chọn").

## 3. Connector (chỉ thêm field, không phá vỡ)
`FacebookAdsConnector::listPagePosts` — mở rộng chuỗi `fields`:
- thêm `attachments{media_type,media,target,unshimmed_url,type,title}` (đang có `attachments{media_type,media}`) và `call_to_action{type,value}`.
- Trích **generic** (tên field Graph chỉ ở connector):
  - `linkUrl` = ưu tiên `call_to_action.value.link`; nếu trống → `attachments.data[0].target.url`; nếu trống → `attachments.data[0].unshimmed_url`; còn lại `null` (post ảnh thuần không có link → null).
  - `ctaType` = `call_to_action.type` nếu có (vd `MESSAGE_PAGE`, `LEARN_MORE`, `SHOP_NOW`), else `null`.
- Giữ "core không biết sàn": `attachments`, `call_to_action`, `target` chỉ xuất hiện trong connector.

## 4. DTO `PagePostDTO` (thêm 2 field cuối, có default)
Thêm sau `shares`, trước `raw`:
- `public ?string $linkUrl = null` — đường dẫn đích hiện hữu của bài.
- `public ?string $ctaType = null` — loại CTA hiện hữu (generic, mã FB).
(Mọi nơi khởi tạo DTO khác — không có — nên thêm có default an toàn.)

## 5. Controller (thêm 2 khoá vào mảng)
`AdAuthoringController::pagePosts` map thêm: `'link_url' => $p->linkUrl`, `'cta_type' => $p->ctaType`.

## 6. Frontend
- `AdPagePost` (`lib/adWizard.tsx`): thêm `link_url?: string | null; cta_type?: string | null`.
- `PagePostPickerModal`: `PickResult` thêm `link_url` + `cta_type`; `handlePick` truyền qua.
- `StepCreative`:
  - `PickedPostSummary` thêm `link_url` + `cta_type`.
  - Thẻ "Bài viết đã chọn": render badge —
    - nếu `cta_type === 'MESSAGE_PAGE'` **hoặc** `objective === 'messages'` → `<MessageOutlined/> Hành động: Gửi tin nhắn` (xanh lá).
    - else nếu `link_url` có → `<LinkOutlined/> Đường dẫn: <link rút gọn, ellipsis>` (xanh dương), bấm mở link `target="_blank" rel="noreferrer"`.
    - else → không badge.
  - Icons @ant-design/icons (`MessageOutlined`, `LinkOutlined`). Không emoji.

## 7. Bất biến / không đụng
- DTO mở rộng có default ⇒ không vỡ chữ ký. Mapper/connector createAd không đổi (đây là đọc post, không phải tạo).
- Pass-through; không thêm bảng; không tiền tệ.

## 8. Testing
- BE connector: post có `call_to_action.value.link` → `linkUrl` = link đó + `ctaType` đúng; post chỉ có `attachments[0].target.url` → `linkUrl` lấy từ attachments; post ảnh thuần → `linkUrl`/`ctaType` = null. (Http::fake, không DB.)
- BE controller feature test: response posts có khoá `link_url`, `cta_type`.
- FE: typecheck + lint + build.

## 9. Giới hạn / nối tiếp
- Pixel + conversion event (phần 2 của D) — follow-up riêng.
- Không sửa CTA của post tại đây (chỉ hiển thị); chỉnh CTA khi xuất bản vẫn theo `creative.cta` sẵn có.
