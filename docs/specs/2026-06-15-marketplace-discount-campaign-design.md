# Phương án: Chương trình giảm giá nhiều SKU trên Shopee / Lazada / TikTok

- Date: 2026-06-15
- Status: Draft (chờ chốt scope) — phương án thiết kế, CHƯA code.
- Nguồn: tài liệu `tailieuapi_itiktok_shopee_lazada/{shopee,lazada,tiktok}/` + kiến trúc
  `app/app/Integrations/Channels` + module `Products`.

## 1. Mục tiêu

Cho seller tạo **một chương trình giảm giá** trong hệ thống, chọn **nhiều SKU** (lọc theo
gian hàng), đặt mức giảm + thời gian, rồi **đẩy lên sàn** tương ứng. Quản lý vòng đời:
nháp → đẩy → đang chạy → kết thúc/sửa. Mỗi chương trình thuộc **một gian hàng** (vì
discount là theo shop trên cả 3 sàn).

## 2. Khác biệt cơ chế 3 sàn (điểm mấu chốt)

| | Shopee | TikTok | Lazada |
|---|---|---|---|
| Có "program object" trên sàn? | **Có** (`promotion_id`) | **Có** (`activity_id`) | **KHÔNG** (chỉ giá sale theo SKU) |
| Tạo chương trình | `POST /api/v2/discount/add_discount` | `POST /promotion/202309/activities` (FIXED_PRICE \| DIRECT_DISCOUNT) | — (không có) |
| Thêm SKU | `add_discount_item` (item_id+model_id, `promotion_price` tuyệt đối) | `PUT /activities/{id}/products` (sku.id + `activity_price_amount` HOẶC `discount` %) | `POST /product/price_quantity/update` đặt `SalePrice`+`SaleStartDate/SaleEndDate` theo SKU |
| Kiểu giảm | giá tuyệt đối | **% hoặc giá cố định** | giá tuyệt đối (SalePrice) |
| Sửa/xoá SKU | `update/delete_discount_item` | `PUT/DELETE .../products` | update lại / xoá = clear SalePrice |
| Kết thúc | `end_discount` / `delete_discount` | `DELETE /activities/{id}` (deactivate) | clear SalePrice (set null + xoá ngày sale) |
| Giới hạn batch | tài liệu không nêu → chunk thận trọng (~50) | **300 SKU/call, 10.000/activity** | **≤50 SKU/call (khuyến nghị 20)** |
| Thời gian | start tương lai, unix ts | begin>now, có min/max | `SaleStartDate/EndDate` (chỉ NGÀY, không giờ) |

**Hệ quả thiết kế:** mẫu số chung là **giá-sale-tuyệt-đối theo SKU + cửa sổ thời gian**.
% chỉ TikTok hỗ trợ native; với Shopee/Lazada ta **tự quy đổi % → giá tuyệt đối** từ giá
hiện tại của SKU. Lazada **không có đối tượng chương trình** ⇒ "chương trình" là khái niệm
**chỉ tồn tại trong DB của ta**, khi đẩy thì rải `SalePrice` lên từng SKU; khi kết thúc thì
gỡ `SalePrice`.

## 3. Trừu tượng hoá (theo Connector + Registry, luật extensibility)

Thêm **trục mới `PromotionConnector`** (interface tách biệt — KHÔNG nhồi vào
`ProductPublishingConnector`), 3 publisher hiện có implement thêm interface này; resolve qua
`PublisherRegistry` (cùng instance provider) + capability map. Core không biết tên sàn.

```php
interface PromotionConnector {
    /** Tạo chương trình (Shopee add_discount / TikTok create activity / Lazada: no-op trả id rỗng). */
    public function createPromotion(AuthContext $a, PromotionDraftDTO $d): PromotionResultDTO;
    /** Thêm/đặt giảm giá cho 1 batch SKU (đã chunk theo giới hạn sàn). */
    public function putPromotionItems(AuthContext $a, string $extId, array $items): void; // items: PromotionItemDTO[]
    /** Gỡ SKU khỏi chương trình. */
    public function removePromotionItems(AuthContext $a, string $extId, array $skuRefs): void;
    /** Kết thúc/huỷ chương trình (Lazada: clear SalePrice các SKU). */
    public function endPromotion(AuthContext $a, string $extId): void;
    /** Giới hạn batch + tính năng (max_items_per_call, supports_percent, has_program_object). */
    public function promotionCapabilities(): array;
}
```

DTO chuẩn (giá = integer VND; mức giảm chuẩn hoá ở core, connector tự quy đổi):
- `PromotionDraftDTO{ title, startAt, endAt, items: PromotionItemDTO[] }`
- `PromotionItemDTO{ externalProductId, externalSkuId, sellerSku, basePrice, discountType(percent|fixed), discountValue, salePrice(computed) }`
- `PromotionResultDTO{ externalPromotionId|null, rawStatus }`

Connector tự lo đặc thù: Shopee map `item_id/model_id`+`promotion_price`; TikTok map
`activity_type` + `sku.id`+(`activity_price_amount`|`discount`); Lazada bỏ `createPromotion`
(trả id rỗng) và `putPromotionItems` → gọi `price_quantity/update` đặt `SalePrice`.

## 4. Mô hình dữ liệu (module Products)

```
channel_promotions
  id, tenant_id, channel_account_id, provider,
  external_promotion_id (nullable; Lazada luôn null),
  title, discount_type (percent|fixed default), -- mặc định toàn chương trình, item override được
  starts_at, ends_at,
  status (draft|pushing|live|ended|failed), last_error json, pushed_at,
  timestamps + softDeletes
  unique (channel_account_id, external_promotion_id) where not null

channel_promotion_skus
  id, promotion_id, channel_listing_id (FK -> channel_listings),
  external_product_id, external_sku_id, seller_sku,
  base_price, discount_type, discount_value, sale_price (computed/đẩy),
  push_status (pending|ok|failed), error, timestamps
```

SKU lấy từ `ChannelListing` (đã có theo shop) — chọn đa SKU như trang "Đã có trên sàn".

## 5. Luồng nghiệp vụ

1. Tạo nháp chương trình (chọn gian hàng + tiêu đề + thời gian + kiểu giảm mặc định).
2. Chọn nhiều SKU từ `channel_listings` của shop (filter/tìm), đặt mức giảm (đồng loạt hoặc
   từng dòng); hệ thống tính `sale_price` từ `base_price` (đặc biệt cho Shopee/Lazada).
3. Validate: thời gian tương lai, `sale_price < base_price`, không trùng chương trình đang
   chạy/sắp chạy chứa cùng SKU (chặn sớm ở DB ta), % hợp lệ.
4. **Đẩy bất đồng bộ** (`PushPromotionJob`, Horizon, idempotent):
   - `createPromotion` (Shopee/TikTok) → lưu `external_promotion_id`.
   - `putPromotionItems` theo **batch chunk** (TikTok 300, Lazada 20, Shopee ~50).
   - Lazada: bỏ create, rải `SalePrice` từng batch.
   - Cập nhật `push_status` từng SKU; lỗi 1 batch không chặn batch khác (đánh dấu failed + retry).
5. Theo dõi trạng thái (get_discount / get activity / suy từ push_status). Webhook Shopee
   (push code 7/9 — `item_promotion_push`, `promotion_update_push`) đồng bộ ngược trạng thái.
6. Kết thúc/huỷ: `endPromotion`. Sửa: ưu tiên upcoming; đang chạy thì giới hạn (TikTok
   flashsale không sửa item khi ongoing; Shopee chỉ sửa giá item).

## 6. Ràng buộc & gotcha (bắt buộc xử lý)

- **Chồng lấn:** 1 SKU không ở 2 chương trình đang/sắp chạy cùng lúc (Shopee; TikTok
  17029022). Ta chặn ở tầng DB + báo lỗi rõ; nếu sàn trả lỗi chồng lấn → surface.
- **Khoá giá:** TikTok khoá giá khi SKU đang trong promotion (12052038) → tính năng "sửa
  giá trên sàn" phải cảnh báo/không đụng SKU đang có chương trình. Ngược lại tạo chương
  trình cho SKU thì đọc `base_price` từ giá hiện tại tại thời điểm tạo.
- **% → tuyệt đối:** Shopee/Lazada cần giá tuyệt đối ⇒ snapshot `base_price` lúc đẩy; nếu
  giá gốc đổi sau đó, sale_price không tự đổi (ghi chú cho user).
- **Lazada chỉ có NGÀY** (không giờ) cho SalePrice ⇒ cảnh báo UI; cửa sổ giờ chỉ Shopee/TikTok.
- **Batch limit khác nhau** ⇒ `promotionCapabilities().max_items_per_call` quyết định chunk.
- **Idempotent:** job re-run không tạo trùng (guard theo `external_promotion_id` + push_status).
- **Múi giờ:** theo chuẩn dự án (lưu UTC, hiển thị/đẩy theo giờ VN — xem timezone memory).

## 7. HTTP & FE

Backend (module Products):
- `POST /api/v1/channel-promotions` (tạo nháp), `PATCH /{id}`, `POST /{id}/skus` (đặt SKU+mức),
  `POST /{id}/push`, `POST /{id}/end`, `GET /{id}` + `GET /channel-promotions` (list),
  `GET /{id}/push-status`.
- Service `PromotionService` (lifecycle, tính sale_price, validate), `PushPromotionJob`.

Frontend (features/products):
- Trang **"Chương trình giảm giá"** (list) + trang tạo/sửa: chọn shop → bảng chọn nhiều SKU
  (tái dùng pattern `OnChannelPage` + checkbox), đặt mức giảm đồng loạt/từng dòng, thời gian,
  nút "Lưu nháp" + "Đẩy lên sàn" + modal tiến trình (như `PushProgressModal`).

## 8. Plan-gate / capability

- Capability: chỉ hiện/đẩy cho sàn có `promotionCapabilities` (cả 3 đều có ở mức tối thiểu).
- Cân nhắc **plan-gate** (feature key `discount_campaigns`) như các tính năng nâng cao —
  cần chốt (xem §10). Khai đồng bộ 4 nơi như memory marketing-plan-gate nếu bật.

## 9. Giai đoạn triển khai (phased)

1. **P1 — Lõi + Lazada + TikTok** (cơ chế rõ ràng, tài liệu đầy đủ): model/migration,
   PromotionConnector + DTO, service/job, endpoints, FE list + tạo + chọn SKU + đẩy.
2. **P2 — Shopee**: bổ sung connector Shopee discount (cần đối chiếu schema live vì tài
   liệu bundle chỉ liệt kê tên endpoint, thiếu request/response chi tiết).
3. **P3 — Đồng bộ ngược + sửa khi đang chạy + webhook Shopee** (code 7/9), kết thúc sớm,
   xử lý chồng lấn nâng cao.

## 10. Quyết định cần chốt

1. **Phạm vi P1**: làm trước **Lazada + TikTok** (khuyến nghị) rồi Shopee sau, hay cả 3 cùng lúc?
2. **Kiểu giảm**: hỗ trợ cả **% và giá cố định** (khuyến nghị) hay chỉ giá cố định cho đồng nhất 3 sàn?
3. **Lazada**: chấp nhận mô hình "chương trình ảo" (rải SalePrice, không có id sàn) đúng như
   tài liệu, hay chỉ làm Shopee/TikTok (có program thật) ở giai đoạn đầu?
4. **Plan-gate**: có khoá tính năng sau gói không (feature `discount_campaigns`)?
5. **Phạm vi loại chương trình**: chỉ "giảm giá sản phẩm/SKU" (Discount/Direct/Fixed) —
   KHÔNG làm flash-sale/voucher/combo ở giai đoạn này (khuyến nghị), đúng không?
