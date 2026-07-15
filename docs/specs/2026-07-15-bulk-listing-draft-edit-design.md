# SPEC: Chỉnh sửa hàng loạt bản nháp đăng sàn trước khi đẩy

- **Trạng thái:** Design
- **Phase:** —
- **Module backend liên quan:** Products (`ListingDraftController`, `ListingDraftService`, `ListingTaxonomyController`), Integrations (`ShopeeListingValidator`)
- **Tác giả / Ngày:** lilyrisa · 2026-07-15
- **Liên quan:** `2026-06-15-marketplace-listing-edit-rework-design.md` (trang soạn nháp đơn lẻ hiện có — tái dùng gần như mọi component con), `2026-07-14-channel-listing-grouping-and-bulk-clone.md` (mục 2 "Ngoài phạm vi" của spec đó nêu rõ *"Không làm bulk cho Sửa trên sàn — phạm vi lần này chỉ sao chép hàng loạt"* — spec này là phần tiếp theo được deferred đó, nhưng áp dụng cho **bản nháp** `listing_drafts`, không phải listing đã live `channel_listings`), phiên điều tra 2026-07-15 (tenant Enko Store, commit `95c58bac` — tự chọn kho mặc định TikTok + kế thừa category/brand/attributes/logistics khi tạo nháp thứ 2 cùng nền tảng).

## 1. Vấn đề & mục tiêu

Sau commit `95c58bac`, tạo nháp thứ 2+ cho **cùng sản phẩm, cùng nền tảng** (vd 2 shop TikTok) đã tự kế thừa category/brand/thuộc tính/vận chuyển/khối lượng — phần lớn trường hợp cùng-sàn có thể đẩy thẳng mà không cần sửa gì. Nhưng còn 2 khoảng trống:

1. **Người bán vẫn có thể muốn sửa** một số trường trước khi đẩy dù đã kế thừa xong (vd đổi giá theo shop, sửa tiêu đề khác biệt) — hiện tại phải mở từng nháp một qua trang soạn đơn lẻ (`ListingDraftEditorPage.tsx`), rất chậm khi có hàng chục nháp (case Enko Store: 16 nháp kẹt).
2. **Sao chép khác nền tảng** (TikTok → Shopee) hiện chỉ copy nội dung dùng chung (mô tả/ảnh/SKU) rồi mở editor đơn lẻ bắt soạn lại ngành hàng/thương hiệu/thuộc tính/vận chuyển **từng nháp một** — không có cách nào xử lý hàng loạt.

Mục tiêu: 1 màn hình dạng bảng cho phép sửa nhiều bản nháp **cùng nền tảng** cùng lúc — kể cả trường hợp đã sẵn sàng đẩy (chỉ muốn chỉnh nhẹ) lẫn trường hợp thiếu nhiều trường (mới clone khác sàn) — với cơ chế "áp dụng cho tất cả" để tránh nhập lại các trường giống nhau ở nhiều dòng, rà soát lỗi rõ theo từng ô/từng SKU, rồi Lưu hoặc Lưu & đẩy thẳng.

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Endpoint `GET /listings/bulk` (lấy nhiều nháp đầy đủ) và `PUT /listings/bulk` (lưu nhiều nháp, mỗi nháp xử lý độc lập).
  - Trang mới `BulkListingEditPage.tsx` (`/marketplace/listings/bulk-edit`) — bảng sửa tiêu đề, mô tả, ngành hàng, thương hiệu, thuộc tính bắt buộc, khối lượng/kích thước, vận chuyển, và bảng con sửa giá/mã từng SKU.
  - Nút "Áp dụng cho tất cả" cho các trường không-theo-từng-SKU (ngành hàng/thương hiệu/thuộc tính/khối lượng-kích thước/vận chuyển).
  - Mở rộng checkbox chọn dòng trong `ListingDraftsTable.tsx` sang cả trạng thái Nháp/Lỗi (hiện chỉ Sẵn sàng), thêm nút "Chỉnh sửa hàng loạt".
  - Thêm giới hạn ký tự tiêu đề cho Shopee vào `ShopeeListingValidator` (hiện thiếu hẳn) + mở rộng `config('integrations.listing_limits.*')` với `title_min_length`/`title_max_length`, phơi qua endpoint `listing-limits` có sẵn.
- **Ngoài (làm sau / không đổi):**
  - Không đổi luồng sửa/sao chép cho listing **đã live** trên sàn (`channel_listings`, `MarketplaceCloneService`, `OnChannelPage.tsx`) — spec này chỉ áp dụng cho bản nháp `listing_drafts` (`ready`, `draft`, `failed`).
  - Không đổi `ListingDraftEditorPage.tsx` (soạn 1 nháp) — vẫn giữ nguyên làm lối vào chi tiết/khắc phục lỗi sâu (vd chọn ảnh, ghép master SKU) mà bảng hàng loạt không phủ hết.
  - Không tự động chọn danh mục/thương hiệu bằng AI khi khác sàn — người bán vẫn phải tự chọn qua `CategoryPicker`, chỉ khác là chọn 1 lần rồi "Áp dụng cho tất cả" thay vì lặp lại ở từng trang.
  - Không hỗ trợ chọn nháp thuộc nhiều nền tảng khác nhau trong 1 lượt sửa hàng loạt (xem mục 4).
  - Không thêm tích hợp gọi API `get_item_limit` thời gian thực của Shopee — dùng số tĩnh cấu hình được (xem mục 5), vì tài liệu Shopee Open Platform không công bố số cố định và pattern hiện có (`max_images`) đã dùng config tĩnh.

## 3. Luồng chính

1. Người bán vào "Chờ đẩy lên sàn" (`ListingDraftsTable`), tick chọn nhiều dòng Nháp/Sẵn sàng/Lỗi **cùng 1 provider**. Nút "Chỉnh sửa hàng loạt (N)" sáng lên; nếu các dòng đang chọn không cùng provider, nút xám + tooltip "Chỉ chọn được các listing cùng 1 sàn".
2. Bấm nút → điều hướng `/marketplace/listings/bulk-edit`, truyền danh sách ID qua `navigate(path, { state: { ids } })`. Trang tự `GET /listings/bulk?ids=...` lấy dữ liệu đầy đủ. Không có `state` (tải lại trang) → quay về danh sách kèm thông báo "Vui lòng chọn lại các nháp cần sửa".
3. Bảng hiện từng dòng = 1 nháp, sửa tại chỗ (xem mục 6). Đổi ngành hàng/thương hiệu/thuộc tính/khối lượng-kích thước/vận chuyển ở 1 dòng → nút "Áp dụng cho tất cả" cạnh ô đó ghi đè giá trị lên mọi dòng khác đang có trong bảng.
4. Bấm "Lưu" hoặc "Lưu & đẩy" → `PUT /listings/bulk` gửi toàn bộ thay đổi 1 lần, mỗi nháp validate + lưu độc lập (1 nháp lỗi không chặn nháp khác). Kết quả trả về cập nhật lại trạng thái/lỗi từng dòng ngay trên bảng.
5. "Lưu & đẩy": sau khi lưu, tự lọc ID có `status === 'ready'`, gọi `POST /listings/bulk-push` có sẵn, mở `PushProgressModal` có sẵn theo dõi tiến trình đẩy.
6. Hoàn tất (Lưu xong hoặc đẩy xong) → quay về "Chờ đẩy lên sàn", toast tổng kết "Đã lưu X, đẩy thành công Y, còn Z dòng thiếu thông tin". Z dòng vẫn còn trong danh sách "Chờ đẩy" để sửa tiếp (không mất dữ liệu đã nhập).

## 4. Hành vi & quy tắc nghiệp vụ

- **Ràng buộc cùng-provider:** `GET /listings/bulk` kiểm tra tất cả ID thuộc cùng `provider` (và cùng `tenant_id` — tự động qua `BelongsToTenant`), trả `422` nếu trộn provider (phòng trường hợp FE bị bypass, vd gọi thẳng API).
- **"Áp dụng cho tất cả"** chỉ có ở: ngành hàng (`category_id`), thương hiệu (`brand_id`), thuộc tính bắt buộc (`attributes`), khối lượng/kích thước (`logistics.package_weight`/`package_dims` — TikTok/Shopee ở cấp listing; Lazada ở cấp SKU nên áp dụng sẽ ghi đè xuống **mọi SKU con của mọi dòng**), vận chuyển (`logistics` còn lại: kho TikTok/kênh Shopee). **Không có** ở tiêu đề, mô tả, giá, và các trường khác của SKU con (mã/tồn) — các trường này luôn khác nhau giữa các sản phẩm nên áp dụng hàng loạt là vô nghĩa/nguy hiểm (vd ghi đè trùng `seller_sku` giữa các SKU sẽ vỡ unique constraint).
- **Lưu độc lập từng dòng** (không phải 1 transaction chung): dòng nào đạt `ready` sau validate thì cập nhật, dòng nào thiếu/sai thì giữ `draft` + trả `validation_errors` — đúng triết lý đã có ở `PushListingJob`/`MarketplaceCloneService::bulkCloneToShops()` (lỗi 1 phần tử không chặn lô).
- **Validate hiển thị theo từng ô/từng SKU:** map thẳng `validation_errors` (dạng `{field: message}`, đã có sẵn từ `revalidate()`) vào đúng ô trên bảng — `category_id`/`brand_id`/`logistics.package_weight` → ô cấp listing; `skus.N.warehouse_id`/`skus.N.package_weight` → dòng con SKU thứ N. Badge tổng "Thiếu N trường" ở đầu dòng, bấm cuộn tới ô lỗi đầu tiên.
- **Giới hạn tiêu đề theo provider:** TikTok 25–255 ký tự (đã validate), Lazada ≤255 (đã validate), Shopee **thêm mới** ≤ `config('integrations.listing_limits.shopee.title_max_length', 100)`. FE đếm ký tự sống trong ô Input, đỏ khi vượt — nhưng nguồn sự thật vẫn là validator backend khi Lưu.
- **Mô tả** sửa qua Modal chứa `RichTextEditor` có sẵn (không nhúng thẳng vào ô bảng) — giữ nguyên định dạng HTML hiện có, không đổi cách lưu.

## 5. Dữ liệu

- **Không có migration** — dùng nguyên schema `listing_drafts`/`listing_draft_skus` hiện có.
- **`config/integrations.php`**: thêm khóa `listing_limits.<provider>.title_min_length`/`title_max_length` (TikTok: 25/255, Shopee: 0/100, Lazada: 0/255) cạnh `max_images`/`max_videos` đã có.
- Không phát domain event mới — tái dùng luồng `revalidate()`/`update()` hiện có của `ListingDraftService`.

## 6. API & UI

### Backend

- **`GET /api/v1/listings/bulk?ids=1,2,3`** (`ListingDraftController::bulkShow`) — validate `ids` (array int, ≤50, giống giới hạn `bulk-push`/`bulk-clone-to-shops` hiện có), load `ListingDraft::with(['skus','product'])->whereIn('id', $ids)`, kiểm tra cùng `provider` (422 nếu không), trả mảng `ListingDraftResource`.
- **`PUT /api/v1/listings/bulk`** (`ListingDraftController::bulkUpdate`) — nhận `{ items: array<{id:int, ...UpdateListingPayload}> }` (validate qua `FormRequest` mới, tối đa 50 item), thêm `ListingDraftService::bulkUpdate(array $items): array` lặp gọi `update($item['id'], $item)` trong `try/catch` từng phần tử (theo đúng pattern `bulkCloneToShops`), trả `list<array{id:int, status:string, validation_errors:array|null}>`.
- **`ShopeeListingValidator`**: thêm nhánh kiểm tra độ dài `title` (hiện chỉ check rỗng, thiếu hẳn max-length).
- **`ListingTaxonomyController::listingLimits`**: bổ sung `title_min_length`/`title_max_length` vào response.
- Quyền: giống các endpoint listing hiện có (`products.manage`), tenant tự scope qua `BelongsToTenant`.
- Route `listings/bulk` (GET+PUT) không đụng độ với `listings/{id}` hiện có nhờ `whereNumber('id')` đã áp cho route động đó (`"bulk"` không khớp numeric) — không cần quan tâm thứ tự khai báo trong `routes.php`.

### Frontend

- `features/products/api.ts`: thêm `getListingsBulk()`, `updateListingsBulk()`; `hooks.ts`: `useListingsBulk(ids)`, `useBulkUpdateListings()`.
- `ListingDraftsTable.tsx`: `getCheckboxProps` bỏ điều kiện `status !== 'ready'` (cho chọn cả `draft`/`failed`, vẫn loại `live`/`published`/`pushing`/`reviewing`); thêm state kiểm tra "các dòng đang chọn cùng provider" để bật/tắt nút mới; nút "Chỉnh sửa hàng loạt (N)" điều hướng kèm `state`.
- Trang mới `pages/marketplace/BulkListingEditPage.tsx`: bảng cha-con (Ant `Table` + `expandable`), tái dùng nguyên `CategoryPicker`, `AttributeForm`, `ShippingSection`/`TikTokShipping`/`ShopeeShipping`, `RichTextEditor` (trong Modal), `PushProgressModal` từ `ListingDraftEditorPage.tsx`/`features/products/`. Route đăng ký cạnh `/marketplace/listings/:id/edit` trong router app.

## 7. Edge case & lỗi

- Chọn dòng rồi provider của 1 dòng đổi giữa chừng (hiếm, do đồng bộ nền) → BE vẫn kiểm tra lại cùng-provider ở `GET /listings/bulk`, trả lỗi rõ nếu lệch, FE báo "Danh sách đã thay đổi, vui lòng chọn lại".
- 1 nháp bị xóa bởi tab khác trong lúc đang sửa hàng loạt → `PUT /listings/bulk` bỏ qua ID không tìm thấy (không throw), trả kết quả thiếu ID đó, FE báo "1 nháp đã bị xóa, không lưu được".
- Sản phẩm không có SKU biến thể (1 SKU duy nhất) → dòng cha không hiện nút mở rộng, sửa giá/mã trực tiếp ở dòng cha luôn (nhất quán với `ListingDraftEditorPage.tsx` hiện tại: `singleSku` bỏ `sale_props`).
- "Áp dụng cho tất cả" khi các dòng khác đã có giá trị khác (vd đã tự sửa riêng ngành hàng ở dòng 3) → ghi đè không hỏi lại (rõ ràng theo tên nút), nhưng có Popconfirm xác nhận trước khi ghi đè để tránh bấm nhầm mất dữ liệu đã sửa tay.
- Lưu thành công nhưng đẩy thất bại ở bước gọi API sàn (lỗi mạng/token) → không thuộc phạm vi spec này, tái dùng nguyên cơ chế báo lỗi của `PushListingJob`/`PushProgressModal` hiện có.

## 8. Bảo mật & dữ liệu cá nhân

Không có PII mới. Cả 2 endpoint mới scope theo `tenant_id` qua `BelongsToTenant` như mọi query `ListingDraft` hiện có — không cần thêm kiểm tra thủ công. Quyền yêu cầu giống các thao tác listing khác trong module (`products.manage`).

## 9. Kiểm thử

- **Backend (Feature test):** `GET /listings/bulk` trả đúng nhiều nháp kèm SKU, 422 khi trộn provider, không lọt tenant khác. `PUT /listings/bulk` với 1 item hợp lệ + 1 item thiếu category → cả 2 đều lưu, item hợp lệ chuyển `ready`, item thiếu vẫn `draft` kèm `validation_errors`, response không throw giữa chừng. `ShopeeListingValidator`: title vượt `title_max_length` → lỗi rõ.
- **Frontend:** không có JS test runner — verify thủ công: chọn nhiều nháp Nháp/Lỗi cùng provider → mở bảng hàng loạt, sửa 1 dòng rồi "Áp dụng cho tất cả" lan đúng sang các dòng khác (kể cả SKU con với Lazada); sửa mô tả qua Modal RichTextEditor lưu đúng; Lưu & đẩy → dòng ready được đẩy thật, dòng thiếu thông tin vẫn hiện lỗi tại đúng ô; chọn trộn provider → nút xám đúng tooltip.

## 10. Tiêu chí hoàn thành

- [ ] `GET /listings/bulk`, `PUT /listings/bulk` hoạt động đúng, lỗi từng phần tử không chặn lô.
- [ ] `ShopeeListingValidator` có giới hạn độ dài tiêu đề cấu hình được.
- [ ] `ListingDraftsTable.tsx` cho chọn Nháp/Sẵn sàng/Lỗi, nút "Chỉnh sửa hàng loạt" hoạt động đúng luật cùng-provider.
- [ ] `BulkListingEditPage.tsx` sửa được tiêu đề/mô tả/ngành hàng/thương hiệu/thuộc tính/khối lượng-kích thước/vận chuyển/giá-mã SKU con, "Áp dụng cho tất cả" đúng phạm vi đã nêu.
- [ ] Lưu & đẩy trả về đúng tổng kết X/Y/Z, quay về danh sách sau khi xong.
- [ ] `docs/05-api/endpoints.md` cập nhật 2 endpoint mới.

## 11. Câu hỏi mở

- Không còn — đã chốt qua trao đổi với chủ repo ngày 2026-07-15 (phạm vi chọn: 1 provider/lượt; áp dụng hàng loạt: mọi dòng đang chọn; lưu một phần: vẫn lưu + đẩy riêng dòng hợp lệ; mô tả: Modal RichTextEditor có sẵn; trạng thái chọn được: mọi trạng thái trừ Live).
