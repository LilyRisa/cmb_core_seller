# Làm lại trang "Sửa sản phẩm đã có trên sàn" theo trải nghiệm trang nháp

- Date: 2026-06-15
- Status: Approved (đang triển khai)
- Liên quan: `ListingDraftEditorPage` (trang nháp), `MarketplaceEditPage` (trang live),
  `MarketplaceListingEditService`, `ProductDescriptionService`.

## Mục tiêu

Trang **"Sản phẩm đã có trên sàn"** (`MarketplaceEditPage`) hiện chỉ sửa thô (tiêu đề/mô tả/
ảnh dán-URL/giá). Làm lại để **giao diện & trải nghiệm sửa ảnh/mô tả giống trang nháp**
(`ListingDraftEditorPage`), nhưng **giới hạn ở các trường sàn cho sửa live**.

## Phạm vi (đã chốt)

**Cho sửa:** tiêu đề, mô tả (kèm **AI gợi ý mô tả**), ảnh (tile + tải lên + resize +
**sửa nâng cao ở TRANG RIÊNG**, **bỏ hẳn ô dán URL**), giá theo SKU.

**KHÔNG cho sửa:** tồn kho (đẩy theo master SKU — có ghi chú trên UI). Cũng chưa làm:
ngành hàng/thương hiệu/thuộc tính/video/SKU đầy đủ/vận chuyển (cần mở rộng connector 3 sàn,
một số trường sàn chặn sửa sau khi đăng — để sau).

## Ràng buộc kiến trúc & quyết định

1. **Sửa ảnh dùng TRANG RIÊNG (route), KHÔNG modal.** Giữ trang nháp như cũ
   (`/marketplace/listings/:id/images/edit`), thêm trang tương tự cho live
   (`/marketplace/on-channel/:id/images/edit`).
2. **Gom thay đổi → đẩy loạt.** Sửa ảnh xong **không** đẩy lên sàn ngay; mọi thay đổi nằm
   chờ ở trạng thái cục bộ, chỉ khi bấm **một nút "Đẩy thay đổi lên sàn"** mới đẩy theo loạt.
3. **Giữ trạng thái khi qua lại trang sửa ảnh.** Trang nháp giữ được vì nháp lưu DB; trang
   live **không có nháp DB** → thêm **kho tạm phía client (Zustand)** giữ thay đổi đang dở
   theo `channel_listing_id`. Đẩy thành công ⇒ xóa kho.

## Frontend

- `components/AdvancedImageEditor.tsx` — **component dùng chung**: bọc `FilerobotImageEditor`
  + `dataUrlToFile` + upload `/media/image`. Props: `source`, `onSaved(url)`, `onClose`.
  Không biết gì về listing (nơi gọi quyết định lưu ở đâu).
- `pages/marketplace/AdvancedImageEditorPage.tsx` (nháp) — refactor dùng component chung;
  giữ hành vi: lưu `media_refs` vào nháp qua `updateListing`, quay lại trang nháp.
- `pages/marketplace/MarketplaceImageEditorPage.tsx` (**mới**, live) — dùng component chung;
  lưu xong **ghi URL mới vào kho tạm** (thay ảnh nguồn), quay lại `/marketplace/on-channel/:id/edit`.
  Không đẩy lên sàn.
- `lib/marketplace/editStore.ts` (**mới**, Zustand) — `{ id, baseline, draft{title,description,
  images,prices}, touched }`; `init(id, baseline)` (chỉ khi đổi id / rỗng), `patch`,
  `replaceImage(old,new)`, `clear`.
- `pages/marketplace/MarketplaceEditPage.tsx` — làm lại bố cục thẻ:
  - **Thông tin:** Tiêu đề + Mô tả + nút "AI gợi ý mô tả" (modal xem trước/chấp nhận).
  - **Hình ảnh:** tile vuông + nút "sửa nâng cao" (→ điều hướng trang sửa ảnh live) + nút xóa
    + ô "+ Tải ảnh" + "Tải & resize ảnh"; đếm `n/max` theo sàn. **Bỏ ô dán URL.**
  - **Giá theo SKU:** chỉ sửa giá + `Alert` ghi chú "tồn đẩy theo master SKU".
  - Đọc/ghi kho tạm; một nút **"Đẩy thay đổi lên sàn"** → diff vs baseline →
    `updateMarketplaceListing` → xóa kho → quay lại.
  - *Provider* (cho giới hạn ảnh): suy từ `channel_account_id` qua `useChannelAccounts`.
- `app.tsx`: thêm route trang sửa ảnh live (lazy như route nháp).
- `features/products/api.ts` + `hooks.ts`: thêm `aiSuggestMarketplaceDescription(id, description)`
  + `useAiSuggestMarketplaceDescription`.

## Backend

- `ChannelListingController::aiDescription` → `POST /api/v1/channel-listings/{id}/ai-description`
  (quyền `products.manage`); nhận `description` tùy chọn (mô tả hiện tại để AI cải thiện).
- `ProductDescriptionService`: tách phần chung (`buildPromptFromParts` + `generate`) để
  `suggest(ListingDraft)` (giữ nguyên kết quả) và `suggestForListing(ChannelListing, ?desc)`
  dùng chung; vẫn khóa theo gói + trừ 1 lượt ví AI.

## Nghiệm thu

- `MarketplaceEditPage`: sửa tiêu đề/mô tả/ảnh/giá; sang trang sửa ảnh rồi quay lại **không
  mất** các sửa đổi khác; chỉ "Đẩy thay đổi lên sàn" mới gọi `updateMarketplaceListing` (1 lần,
  theo loạt). Không có ô dán URL. Không có ô sửa tồn.
- AI gợi ý mô tả hoạt động trên trang live (trừ lượt ví AI; gói không AI ⇒ 402).
- `typecheck`/`lint`/`build` xanh; test backend AI description cho channel-listing pass.
