# Yêu cầu API tạo listing sản phẩm — Shopee / TikTok Shop / Lazada

> **Nguồn & nguyên tắc (req #8):** Toàn bộ dữ liệu dưới đây lấy **chỉ từ tài liệu Open Platform chính thức** của từng sàn, có trích dẫn URL ở mục **Sources** của mỗi phần. **Không suy đoán, không lấy từ blog/SDK bên thứ ba.** Những trang không truy cập được bằng công cụ được đánh dấu rõ `INACCESSIBLE` thay vì bịa nội dung. Tài liệu này là **reference cho engineering** khi bổ sung capability `product.create` cho từng `ChannelConnector` ở backend `cmb_core_seller`.
>
> **Ngày thu thập:** 2026-06-11. Các giới hạn số (độ dài title, số ảnh, khoảng giá…) phần lớn **phụ thuộc market/seller-type và phải đọc runtime** từ chính API của sàn (vd Shopee `get_item_limit`), không hardcode.
>
> **Hệ quả thiết kế quan trọng (đọc trước):** Một "sản phẩm hệ thống" (master) **không thể đẩy thẳng** lên bất kỳ sàn nào — mỗi sàn bắt buộc dữ liệu riêng mà master không có sẵn: `category_id` lá theo cây danh mục **của từng sàn**, các **thuộc tính bắt buộc theo danh mục**, `brand_id` theo danh sách brand **của từng sàn**, **ảnh phải upload lên CDN của sàn trước** (Shopee `image_id`, Lazada URL CDN, TikTok `uri`), và **cấu hình logistics/vận chuyển**. Đây chính là lý do tồn tại trạng thái "nháp cần user sửa" (req #1).

---

## Shopee

> **Cơ sở nguồn:** Shopee Open Platform Open API v2. Trang reference field-level của `v2.product.add_item` (`open.shopee.com/documents/v2/v2.product.add_item`) **INACCESSIBLE** qua công cụ — field-level dựng lại từ các trang developer-guide chính thức (209/211/217/219/221/223) vốn mô tả đúng request/example của `add_item`. Trong repo `cmb_core_seller` còn có bản mirror nguyên văn ở `tailieuapi_itiktok_shopee_lazada/shopee/` (mỗi file giữ URL gốc `open.shopee.com/developer-guide/NN`).

### 1. Luồng tạo & endpoint
Tạo sản phẩm là luồng nhiều bước; điều kiện tiên quyết: shop đã uỷ quyền app, gọi bằng `access_token` + `shop_id` per-shop.

| Thứ tự | Endpoint (`/api/v2`) | Mục đích | Bắt buộc? |
|---|---|---|---|
| 1 | `/product/get_category` | Lấy cây danh mục; chọn `category_id` **lá** | Bắt buộc |
| 2 | `/product/get_attribute_tree` | Thuộc tính bắt buộc/optional theo danh mục lá | Bắt buộc (để biết attr bắt buộc) |
| 3 | `/product/get_brand_list` | `brand_id` theo danh mục | Bắt buộc nếu danh mục yêu cầu brand |
| 4 | `/product/get_item_limit` | Giới hạn độ dài tên/khoảng giá/days-to-ship | Khuyến nghị |
| 5 | `/logistics/get_channel_list` | `logistics_channel_id`/`size_id`/`fee_type` | Bắt buộc |
| 6 | `/media_space/upload_image` | Upload ảnh trước → `image_id` | **Bắt buộc** |
| 6b | `/media_space/init_video_upload` → `upload_video_part` → `complete_video_upload` → `get_video_upload_result` | Upload video (optional) → `video_upload_id` | Optional |
| 7 | **`/product/add_item`** | **Tạo sản phẩm**, trả `item_id` | Bắt buộc |
| 8 | `/product/init_tier_variation` | Thêm biến thể + model (sau add_item) | Optional (nếu có biến thể) |

Khác: `category_recommend`, `get_recommend_attribute`, `get_weight_recommendation`, `support_size_chart`, `register_brand`, `unlist_item`, `delete_item`, `update_price`, `update_stock`, `add_model`, `update_tier_variation`, `get_model_list`, `update_item` (KHÔNG sửa giá/stock/model/size_chart).

### 2. Field BẮT BUỘC cho `add_item`
Mọi item phải có: `category_id` (lá), ảnh, các thuộc tính bắt buộc, giá, stock, ≥1 logistics channel. Weight/dimension bắt buộc tuỳ `fee_type`.

| Field | Bắt buộc? | Kiểu | Ghi chú |
|---|---|---|---|
| `category_id` | **Bắt buộc** | int | Phải **lá** (`has_children=false`) |
| `original_price` | **Bắt buộc** | float/int | SG/MY/BR/MX/PL chỉ integer; market khác cho 2 thập phân. Khoảng giá theo `get_item_limit` |
| `image` (`image_id_list`) | **Bắt buộc** | array `image_id` | Từ `upload_image`. **Không nhận URL — phải upload file trước** |
| stock (`normal_stock`/`seller_stock`) | **Bắt buộc** | int | Cho item không biến thể |
| `logistic_info` | **Bắt buộc** | array | ≥1 channel `enabled=true` từ `get_channel_list` |
| `weight` | Bắt buộc (điều kiện) | float | Bắt buộc với channel `SIZE_INPUT` |
| `dimension` (L/W/H) | Bắt buộc (điều kiện) | object int | Bắt buộc với channel `SIZE_INPUT` |
| `attribute_list` | Bắt buộc (điều kiện) | array | Mọi attr `mandatory=true` từ `get_attribute_tree` |
| `brand` (`brand_id`) | Bắt buộc (điều kiện) | int | Khi `get_brand_list` trả `is_mandatory=true`. `brand_id:0` = No Brand |
| `item_name`/`name` | Bắt buộc (thực tế) | string | Độ dài theo `get_item_limit` |
| `description` | Bắt buộc (thực tế) | string | Whitelist mới dùng `extended_description` |
| `item_sku` | Optional | string | — |
| `video_upload_id` | Optional | string | — |
| `condition` | Optional | enum | NEW/USED |
| `tier_variation` | Optional | — | **KHÔNG phải field của add_item** — tách ra `init_tier_variation` sau khi tạo |
| `pre_order`/`days_to_ship` | Điều kiện | object/int | Theo `get_item_limit` |
| `size_chart` | Optional | image_id | Chỉ khi `support_size_chart`=true |

### 3. Danh mục (`get_category`)
Node: `category_id`, `parent_category_id` (0=gốc), `display_category_name`, `original_category_name`, `has_children`. **Bắt buộc lá**. `category_recommend` gợi ý từ tên+ảnh.

### 4. Thuộc tính (`get_attribute_tree`)
Chỉ lấy được ở danh mục lá. `mandatory:true`=bắt buộc. `input_type`: 1 SINGLE_DROP_DOWN, 2 SINGLE_COMBO_BOX, 3 FREE_TEXT, 4 MULTI_DROP_DOWN, 5 MULTI_COMBO_BOX. `input_validation_type`: 0 none,1 INT,2 STRING,3 FLOAT,4 DATE(Unix ts). `format_type`: 2=phải gửi `value_unit`. Mỗi attr cần `value_id`; custom → `value_id:0` + `original_value_name`. `max_value_count` giới hạn multi-select. Parent/child: gửi child phải kèm parent.

### 5. Brand (`get_brand_list`)
Yêu cầu danh mục lá. `is_mandatory:true`=bắt buộc. `status:1`=brand Shopee; `status:2`=brand bạn submit chờ duyệt. Thiếu → `register_brand`. Không brand → `brand_id:0` (No Brand).

### 6. Ảnh/Media (`upload_image`)
Phải upload **trước** lên Media Space → URL Shopee + `image_id` dùng trong `add_item`. **Chỉ upload file, không nhận URL.** Ảnh ≤**10MB**, JPG/JPEG/PNG; `scene=normal` (ảnh sản phẩm, ép vuông), `scene=desc` (ảnh mô tả). Video ≤**30MB**, **10–60s**, mp4, ≤1280×1280; 4 bước; chỉ `SUCCEEDED` mới có `video_upload_id`.

### 7. Biến thể / model / tier_variation
Làm **sau** `add_item`. `init_tier_variation` với `item_id`, `tier_variation` (tên + `option_list`), `model[]`. Tối đa **2 tier**, **≤50** biến thể. Mỗi model: `tier_index`, `original_price`, stock, SKU (`model_sku`). Ảnh biến thể: chỉ tier-1 có ảnh; nếu 1 option có thì tất cả option tier-1 phải có. Nên chờ ~5s sau add_item. Đọc `model_id` qua `get_model_list`. CNSC/KRSC dùng GlobalProduct API.

### 8. Logistics / vận chuyển
`logistic_info` bắt buộc, ≥1 channel `enabled=true` (dùng `enabled=true` & `mask_channel_id=0`). `fee_type`: SIZE_SELECTION→gửi `size_id`; SIZE_INPUT→gửi `weight`+`dimension`; FIXED_DEFAULT_PRICE→phí cố định; CUSTOM_PRICE→gửi `shipping_fee`. `is_free=true`=seller chịu phí.

### 9. Trạng thái listing
`item_status`: **NORMAL, DELETED, BANNED, UNLIST**. Tạo thành công = **NORMAL** (không có "draft" trong luồng create). `unlist_item` (unlist/relist), `delete_item` (status deleted, giữ data 90 ngày).

### 10. Quyền / scope / host / ký
- **Permission module:** **Product (module 89)** + MediaSpace (91) + Logistics (95). GlobalProduct (90) cho CNSC/KRSC.
- **Shop auth:** Product là Shop API — cần `access_token` + `shop_id` sau khi seller uỷ quyền (auth link → `code` → token). Uỷ quyền tối đa 365 ngày. **`access_token` sống 4 giờ** (refresh thường xuyên).
- **Host prod:** `https://partner.shopeemobile.com/` (SG/VN region). Sandbox riêng.
- **Ký:** common params `partner_id`, `timestamp` (trong 5'), `sign`. Base Shop API = `partner_id + api_path + timestamp + access_token + shop_id`; `sign` = **HMAC-SHA256(base, partner_key)** hex. POST: common params trên query, payload trong JSON body.

### Sources (Shopee)
- https://open.shopee.com/developer-guide/209 · /211 · /217 · /219 · /221 · /223 · /16 (ký) · /20 (auth) · https://open.shopee.com/documents/v2/ (index module 89/90/91/95). Trang field `v2.product.add_item` **INACCESSIBLE**.

---

## TikTok Shop

> **API version:** **202309**. **Host:** `https://open-api.tiktokglobalshop.com`. **VN:** category tree **v2 (7 cấp)**, `category_version=v2` **bắt buộc**; `package_dimensions` **optional cho VN/ID/TH**; có `is_cod_allowed` & `delivery_option_ids`; SKU tối đa **100**; nội dung tiếng Việt.

### 1. Luồng tạo & endpoint
| Bước | Method & Path | Mục đích / scope |
|---|---|---|
| Pre-flight | `GET /product/202309/prerequisites` | Kiểm tra điều kiện listing. `seller.product.basic` |
| Danh mục | `GET /product/202309/categories` | Cây danh mục; chọn lá (`is_leaf:true`); VN truyền `category_version=v2` |
| (opt) Gợi ý | `POST /product/202309/categories/recommend` | Gợi ý danh mục từ tên |
| Rule danh mục | `GET /product/202309/categories/{id}/rules` | Chứng nhận bắt buộc, size-chart, dimensions, COD |
| Thuộc tính | `GET /product/202309/categories/{id}/attributes` | Attr; cái nào `is_required` |
| Brand | `GET /product/202309/brands` | brand_id; có thể cần authorization |
| Upload ảnh | `POST /product/202309/images/upload` | Mỗi ảnh → `uri` cho `main_images` |
| (opt) Validate | `POST` Check Product Listing | Dry-run payload trước khi tạo |
| **Tạo** | **`POST /product/202309/products`** | **Tạo/list sản phẩm. `seller.product.write`** |
| Sửa full | `PUT /product/202309/products/{id}` | Thay toàn bộ |
| Sửa 1 phần | `POST` Partial Edit Product | Chỉ vài field |
| Kích hoạt | `POST /product/202309/products/activate` | Re-activate → về Pending |
| Đọc | `GET /product/202309/products/{id}` | Có `return_draft_version`/`return_under_review_version` |

**Draft vs publish:** Create có `save_mode` = **`AS_DRAFT`** hoặc **`LISTING`** (mặc định `LISTING`). Sau tạo → **TikTok audit**; theo dõi qua webhook "Product status change". Publish 1 draft = edit lại với `save_mode=LISTING`.

### 2. Field BẮT BUỘC cho Create Product
Query bắt buộc: `app_key`, `sign`, `timestamp`, `shop_cipher`; header `content-type: application/json`, `x-tts-access-token`.

| Field | Bắt buộc? | Kiểu | Format/limit |
|---|---|---|---|
| `title` | **Bắt buộc** | string | **VN: [25,255]** ký tự; tiếng địa phương |
| `description` | **Bắt buộc** | string | HTML, **≤10.000 ký tự**, ≤30 `<img>` (mỗi `<img>` dùng URL ảnh TikTok, <4000px) |
| `category_id` | **Bắt buộc** | string | **Lá**, khớp `category_version` |
| `main_images` | **Bắt buộc** | []`{uri}` | **Tối đa 9**, [300×300, 4000×4000]px; `uri` từ Upload Image |
| `skus` | **Bắt buộc** | []object | VN tối đa **100** SKU (xem §6) |
| `package_weight` | **Bắt buộc (default)** | `{value,unit}` | Trừ Virtual Products; **không được 0** |
| `brand_id` | Optional* | string | Một số danh mục có brand **bắt buộc authorization** |
| `product_attributes` | Bắt buộc điều kiện | []object | Phải gửi mọi attr `is_required:true` từ Get Attributes |
| `certifications` | Bắt buộc điều kiện | []object | ≤10; theo Get Category Rules |
| `package_dimensions` | Bắt buộc điều kiện | `{l,w,h,unit}` | **Optional cho VN/ID/TH** |
| `size_chart` | Bắt buộc điều kiện | object | Theo category rules |
| `is_cod_allowed` | Optional | bool | VN có; phải false nếu danh mục không hỗ trợ |
| `save_mode` | Optional | string | `AS_DRAFT`/`LISTING` (default LISTING) |
| `category_version` | Bắt buộc thực tế (VN) | string | **VN phải `v2`** |
| `video` | Optional | object | 1:1, ≥720p, 20–60s |
| `delivery_option_ids` | Optional | []string | VN có; override default kho |

**Response:** `data.{product_id, skus:[{id, seller_sku, sales_attributes, ...}]}`.

### 3. Danh mục
`GET /categories` (query `category_version`, `shop_cipher` bắt buộc). Node: `{id, parent_id, local_name, is_leaf, permission_statuses[]}` (AVAILABLE/INVITE_ONLY/NON_MAIN_CATEGORY). Chỉ **lá** (err 12052024). `/{id}/rules`: `product_certifications[].{is_required,...}` + size-chart/dimensions/COD.

### 4. Thuộc tính
`GET /categories/{id}/attributes`: `{id, name, type, is_required, values[], value_data_format, is_customizable}`. Mọi `is_required:true` là bắt buộc trong `product_attributes`. (Lưu ý: example JSON có typo `is_requried`.)

### 5. Brand
`GET /brands` (query `category_id`, `is_authorized`). Brand không hard-required, nhưng nhiều danh mục cần **brand authorization** (Seller Center → Qualification Center). `POST` Create Custom Brands cho brand chưa có.

### 6. Ảnh/Media
`POST /images/upload` (multipart). Body: `data` (file) + `use_case`. JPG/JPEG/PNG/WEBP/HEIC/BMP, ≤**10MB**. `MAIN_IMAGE`: [300×300, 4000×4000]px. `use_case`: MAIN_IMAGE/ATTRIBUTE_IMAGE/DESCRIPTION_IMAGE/CERTIFICATION_IMAGE/SIZE_CHART_IMAGE. Response `data.uri` → dùng trong `main_images[].uri`, `skus[].sku_img.uri`, `<img>` mô tả.

### 7. SKU / biến thể
`skus[]`: `sales_attributes:[{id,value_id,value_name,sku_img:{uri}}]` (trục biến thể), `seller_sku`, `price:{amount:"<string>",currency,sale_price}` (**amount là string**), `inventory:[{warehouse_id, quantity, ...}]`. **Warehouse bắt buộc** mỗi SKU (err 12019022). Lấy warehouse từ Logistics API.

### 8. Logistics / package
`package_weight` bắt buộc (trừ Virtual), không 0. `package_dimensions` optional VN. Non-US không dùng đơn vị imperial. `delivery_option_ids`/`shipping_template_id` optional; trống = kế thừa default kho.

### 9. Trạng thái (state machine)
8 trạng thái: **Draft, Pending, Failed, Activate, Seller_deactivated, Platform_deactivated, Freeze, Deleted**. Luồng: Create(LISTING)→Pending→(audit)→Activate/Failed. Theo dõi qua webhook. Giới hạn upload: seller mới **100 sp/ngày**, graduated **1000/ngày**.

### 10. Quyền / scope / ký
- **Scope:** Create/Edit/Activate/Delete = **`seller.product.write`**; read = **`seller.product.basic`**.
- **Shop auth:** header **`x-tts-access-token`** + query **`shop_cipher`** (từ Get Authorized Shop).
- **Ký:** query `app_key`, `timestamp` (UTC), `sign` (HMAC theo thuật toán signature của TikTok trên path + sorted params).

### Key error codes
`12019022` SKU thiếu warehouse · `12052024` danh mục không phải lá · `12052028` thiếu main image · `12052104/105` thiếu attr/qualification bắt buộc · `12052181` package weight = 0 · `12052201/208` brand authorization · `12052217` phải dùng v2 · `12052223/226` danh mục restricted.

### Sources (TikTok Shop — `partner.tiktokshop.com/docv2`)
- create-product-202309 · get-product-202309 · edit-product-202309 · activate-product-202309 · check-listing-prerequisites-202309 · get-categories-202309 · get-category-rules-202309 · get-attributes-202309 · get-brands-202309 · upload-product-image-202309 · update-inventory-202309 · update-price-202309 · products-api-overview. (Các trang Recommend Category/Create Custom Brands/Check Listing/Partial Edit có trong sidebar chính thức nhưng bảng field không transcribe từng cái.)

---

## Lazada

> **lazop Product API.** Cần `app key/secret` + `access_token` per-seller mỗi call. Docs là SPA JS-render; nội dung lấy verbatim qua content API same-origin chính thức (`open.lazada.com/handler/share/doc/getDocDetail.json`).

### 1. Luồng tạo & endpoint
| Bước | API | Path | Mục đích |
|---|---|---|---|
| 0 | GetCategorySuggestion | `/product/category/suggestion/get` | Gợi ý danh mục từ tên (khuyến nghị) |
| 1 | GetCategoryTree | `/category/tree/get` | Cây danh mục; tìm **lá** |
| 2 | GetCategoryAttributes | `/category/attributes/get` | Attr available/required/variant |
| 3 | GetBrandByPages | `/category/brands/query` | `brand_id` (theo quốc gia) |
| 4 | Upload/Migrate Image | `/image/upload`, `/image/migrate`, `/images/migrate` | Đổi ảnh → URL CDN Lazada (bắt buộc trước create) |
| 5 (opt) | Video | `/media/video/...` | `video` id (status `AUDIT_SUCCESS`) |
| 6 | **CreateProduct** | `/product/create` | Tạo sản phẩm / thêm SKU |
| — | UpdateProduct | `/product/update` | Sửa (SellerSku không đổi được) |
| — | UpdatePriceQuantity | `/product/price_quantity/update` | Sửa giá + tồn |
| — | GetProducts | `/products/get` | Đọc lại |

**Method:** POST, payload trong body. **`payload`** là JSON hoặc XML, root `Request > Product`: `PrimaryCategory`, `Images>Image[]`, `Attributes{name, description, short_description, brand_id, ...attr...}`, `Skus>Sku[]{SellerSku, saleProp{}, price, special_price, quantity, package_*, Images}`. Response: `data.{item_id, sku_list:[{shop_sku, seller_sku, sku_id}]}`.

### 2. Field BẮT BUỘC cho CreateProduct
| Field | Vị trí | Bắt buộc | Format/limit |
|---|---|---|---|
| `PrimaryCategory` | Product | **Bắt buộc** | category_id **lá** |
| `name` | Attributes | **Bắt buộc** | **≤255 ký tự** |
| `brand_id` | Attributes | **Bắt buộc** | Từ GetBrandByPages; theo quốc gia; "No Brand" id khác mỗi nước |
| attr `is_mandatory=1` | Attributes | **Bắt buộc** | Theo danh mục |
| `SellerSku` | Sku | **Bắt buộc** | Unique trong item; **không sửa được sau** |
| `price` | Sku | **Bắt buộc** | Giá niêm yết |
| `quantity` | Sku | **Bắt buộc** | Tồn |
| `package_height/length/width` | Sku | **Bắt buộc** | ≤2 thập phân; **cm** |
| `package_weight` | Sku | **Bắt buộc** | ≤2 thập phân; **kg** |
| `description` | Attributes | Optional | ≤25000 ký tự; HTML; **chỉ URL ảnh Lazada** |
| `short_description` | Attributes | Optional | Chỉ `<ul><li>`/`<ol><li>` |
| `Images>Image[]` | Product/Sku | Optional* | Array; **≤8 ảnh/Image field**; ngoài Sku=SPU, trong Sku=variant. (QC loại nếu thiếu ảnh) |
| `special_price` (+ from/to date) | Sku | Optional | Có date thì special_price bắt buộc |
| `saleProp` (color_family, size...) | Sku | Optional / **bắt buộc khi nhiều SKU** | — |
| `video` | Attributes | Optional | status `AUDIT_SUCCESS` |

Gotcha: `Images.Image` & `Skus.Sku` **phải là array** (else err 1001); **>50 SKU timeout — batch ~20**; không sửa được SellerSku.

### 3. Danh mục — `/category/tree/get`
Chỉ **lá** mới tạo được. Dùng GetCategorySuggestion map tên→danh mục trước (sai danh mục bị deactivate).

### 4. Thuộc tính — `/category/attributes/get`
- **`is_mandatory=1`** = bắt buộc.
- **`is_sale_prop=1`** = thuộc tính biến thể (sinh SKU); value vào `saleProp{}` mỗi Sku.
- **`attribute_type`**: `normal` (vào `Attributes`) / `sku` (vào `Sku`).
- **`input_type`**: 1 singleselect, 2 multiselect, 3 enuminput, 4 multienuminput, 5 text, 6 numeric, 7 date, 8 richText, 9 img.
- Danh mục không có variant chuẩn → **Custom sales attributes** (Variation1–4, `customize=true`).

### 5. Brand — `/category/brands/query`
Brand **bắt buộc**; dùng `brand_id` (field `brand` text đã deprecated). Tên/ID brand **khác nhau theo quốc gia**.

### 6. Ảnh — `/image/upload`, `/image/migrate`, `/images/migrate`
**Ảnh phải nằm trên CDN Lazada trước khi create/update** — "Only Lazada image Urls can be used". URL ngoài không dùng trực tiếp. UploadImage (binary), MigrateImage (1 URL), MigrateImages (≤**8** URL → `batch_id` → GetResponse). Giới hạn: ≤**3MB**, **330–5000px** mỗi chiều, jpg/png, timeout tải 5s, chỉ port 80/443, không nhận link IP.

### 7. SKU / biến thể
Mỗi Sku: `SellerSku` (bắt buộc, unique, immutable), `saleProp{}` (value của attr `is_sale_prop=1`), `price` (bắt buộc), `quantity` (bắt buộc), optional `special_price`+date, `package_*` (bắt buộc), optional `Images`. saleProp bắt buộc khi nhiều SKU.

### 8. Vận chuyển / package
`package_weight` (kg) + `package_length/width/height` (cm) **bắt buộc mỗi SKU**. Payload create không cần shipping_provider; delivery option chọn sau qua Fulfillment API.

### 9. Trạng thái & QC
QC status (webhook docId 120205): **PENDING / APPROVED / REJECTED / LIVE_REJECTED / LOCK**. Đọc lại qua GetProducts `/products/get`. Chất lượng ảnh + đúng danh mục ảnh hưởng duyệt.

### 10. Quyền / auth / ký / gateway
- **Gateway VN:** `https://api.lazada.vn/rest`. Endpoint = gateway + path (vd `https://api.lazada.vn/rest/product/create`).
- **Permission:** Product API thuộc 1 permission group; group Active (mặc định) hoặc Inactive (phải apply kèm lý do).
- **Seller auth:** OAuth2 code→token tại `https://auth.lazada.com/oauth/authorize` → GenerateAccessToken. **1 access_token = 1 store.** access_token ≈ **10 ngày**, refresh_token ≈ **50 ngày**. Token khác app_key không dùng chéo.
- **Ký:** system params `app_key`, `timestamp`, `sign_method=sha256`, `sign` + `access_token`. Thuật toán: sort param theo ASCII, nối `key+value`, **prepend API path**, HMAC-SHA256 bằng **App Secret**, output **hex hoa**.

### Sources (Lazada)
- Product API Overview docId 120945 · Create a product docId 120949 · Get category attributes docId 120946 · Image Upload docId 120947 · Custom sales attributes docId 120259 · Create/Update Q&A docId 121320 · Webhook QC docId 120205 · API Reference Product docId 108146 · Gateway docId 108065 · Signature docId 108068 · Request permission docId 108131 · Seller authorization docId 108260. (Tất cả `open.lazada.com/apps/doc/doc?...`.)

---

## Tổng hợp xuyên sàn — "field master cần map sang mỗi sàn"

| Yêu cầu | Shopee | TikTok Shop | Lazada |
|---|---|---|---|
| Danh mục lá riêng | `category_id` (get_category) | `category_id` v2 (get categories) | `PrimaryCategory` (category/tree) |
| Thuộc tính bắt buộc theo danh mục | get_attribute_tree | get attributes | category/attributes (is_mandatory) |
| Brand | brand_id (mandatory tuỳ danh mục) | brand_id (authorization tuỳ danh mục) | **brand_id bắt buộc** |
| Ảnh phải re-upload lên CDN sàn | image_id (upload_image) | uri (images/upload) | URL CDN (image/upload/migrate) |
| Giá | original_price | skus[].price.amount (string) | Sku.price |
| Tồn | normal_stock / model stock | skus[].inventory[].quantity (+warehouse) | Sku.quantity |
| Biến thể | tier_variation sau add_item | skus[].sales_attributes | Sku.saleProp |
| Vận chuyển/cân nặng | logistic_info + weight/dimension (SIZE_INPUT) | package_weight (+dimensions optional VN) + warehouse | package_weight/length/width/height mỗi SKU |
| Trạng thái sau tạo | NORMAL (không draft) | Draft/Pending/Activate... (audit) | PENDING→APPROVED/REJECTED (QC) |
| Token sống | access_token **4h** | access_token (refresh) | access_token **~10 ngày** |
| Ký | HMAC-SHA256(partner_key) | HMAC (app signature) | HMAC-SHA256(App Secret) |

**Kết luận engineering:** mỗi sàn cần một `ChannelConnector` bổ sung capability:
`product.read_category_tree`, `product.read_attributes`, `product.read_brands`, `media.upload`, `product.create`, `product.update`, `product.update_price_stock`, `product.read` (đọc trạng thái QC/audit), và (Phase 3) `product.list` (kéo listing). Validate payload theo bảng "field bắt buộc" trước khi gọi để chặn lỗi sớm (req: trạng thái nháp "cần user sửa" chính là bước hoàn thiện các field bắt buộc này).
