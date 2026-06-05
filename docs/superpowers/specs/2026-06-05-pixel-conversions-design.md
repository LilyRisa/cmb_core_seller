# Pixel + sự kiện chuyển đổi (objective Chuyển đổi) — sub-feature D (pt.2) — Design

> Thêm mục tiêu **Chuyển đổi** tối ưu theo **sự kiện Pixel** (offsite conversion), kèm chọn Pixel + sự kiện.
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog, chủ uỷ quyền). Làm trên `main`. Nối tiếp D pt.1.

## 1. Objective map (đơn vị mở rộng)
`FacebookObjectiveMap` thêm khoá `promoted_object` (`'page'` | `'pixel'` | null) cho mỗi entry và mục mới **`conversions`**:
- objective `OUTCOME_SALES`, optimization `OFFSITE_CONVERSIONS`, billing `IMPRESSIONS`, `needs_promoted_object=true`, `promoted_object='pixel'`, CTA `[SHOP_NOW, LEARN_MORE]`.
- messages giữ `promoted_object='page'`; engagement/traffic = null.

## 2. Connector
- `createAdSet`: dựng `promoted_object` qua `buildPromotedObject($kind, $spec)` — `'pixel'` ⇒ `{ pixel_id, custom_event_type }` (mặc định `PURCHASE`), khác ⇒ `{ page_id }`. Thiếu pixel ⇒ ném `requires pixelId`.
- `listPixels(token, account)` (mới, trên `AdsWriteConnector`): Graph `/{account}/adspixels?fields=id,name` → `AdPixelDTO{id,name}` (name rỗng → id).
- `AdSetSpecDTO` thêm `?pixelId`, `?conversionEvent` (default null). Tên field Graph chỉ ở connector.

## 3. Mapper + Controller + Route
- `AdDraftSpecMapper::adSet`: đọc `node.conversion.pixel_id` / `node.conversion.custom_event_type` → `pixelId`/`conversionEvent`.
- `AdAuthoringController::pixels` (GET `ad-accounts/{id}/pixels`, Gate `marketing.view`).

## 4. Frontend
- `AdObjective` += `conversions` (cập nhật đủ các Record exhaustive: StepObjective options/alerts, StepCreative CTA, StepReview labels).
- `AdSetNode.conversion?: { pixel_id?; custom_event_type? }`. Hook `useAdPixels(accountId, enabled)`.
- StepBudget: khi objective=`conversions`, hiện **Pixel** (Select tìm kiếm — tập động hợp lệ) + **Sự kiện tối ưu** (Segmented: PURCHASE/ADD_TO_CART/INITIATE_CHECKOUT/LEAD/COMPLETE_REGISTRATION/VIEW_CONTENT), ghi vào `node.conversion`. Cảnh báo khi chưa chọn Pixel. Icons @ant-design/icons.

## 5. Bất biến / Testing
- Pass-through: `node.conversion` → mapper → DTO → promoted_object ở connector. Không bảng mới.
- BE: createAdSet conversions (pixel+event, default event, thiếu pixel→throw); listPixels map; mapper map conversion.
- FE: typecheck + lint + build.

## 6. Giới hạn / nối tiếp
- Tạo Pixel mới / cài đặt sự kiện trên web → ngoài phạm vi (cấu hình ở Meta).
- Custom conversions (ngoài standard event) → follow-up.
