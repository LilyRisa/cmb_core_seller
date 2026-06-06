# Báo cáo khả thi: "Báo cáo sàn" (sức khỏe shop · điểm chỉ số · điểm phạt/"sao quả tạ" · vi phạm)

- **Ngày:** 2026-06-06 · **Tác giả:** lilyrisa
- **Nguồn:** CHỈ tài liệu chính thức — `open.lazada.com`, `partner.tiktokshop.com`, `open.shopee.com` (Shopee lấy trực tiếp qua trình duyệt vì trang render JS).
- **Phạm vi:** đánh giá *đọc được gì qua API* cho một app seller đã kết nối, **không** phải thiết kế tính năng.

---

## 0. Tóm tắt nhanh (TL;DR)

Mỗi sàn lộ ra **mảng dữ liệu khác nhau** — không có một khuôn chung:

| Hạng mục | 🟠 Lazada | 🔴 Shopee | ⚫ TikTok Shop |
|---|:---:|:---:|:---:|
| **Điểm hiệu suất / scorecard** | ✅ Đầy đủ (có target đạt/không) | ✅ Đầy đủ (rating 1–4 + metric) | ⚠️ Chỉ doanh thu/traffic, **không có score tổng** |
| **Điểm phạt "sao quả tạ"** | ❌ Không có API | ✅ **Đầy đủ** (điểm + bậc phạt + webhook) | ❌ **UI-only** (không API) |
| **Vi phạm listing** | ⚠️ mức sản phẩm | ✅ Đầy đủ (API + webhook) | ❌ UI-only |
| **Đánh giá khách hàng** | ✅ | ✅ | ✅ |
| **Trạng thái tài khoản** | ✅ ACTIVE/INACTIVE | ✅ (suspend/limit) | ⚠️ UI-only |

**Đọc nhanh:**
- **Lazada** → mạnh nhất cho **"điểm hiệu suất shop"** (scorecard + ngưỡng mục tiêu). *Không* có điểm phạt qua API.
- **Shopee** → mạnh nhất cho **"điểm phạt / sao quả tạ + vi phạm"** (đúng thứ người bán lo nhất), và cũng có scorecard. *Nhưng* connector Shopee trong dự án đang "pending API" + endpoint cần được Shopee cấp quyền.
- **TikTok** → chỉ có **hiệu suất doanh thu/traffic + CSKH + review**; **không** có điểm phạt/sức khỏe shop qua API (chỉ xem trong Seller Center).

---

## 1. 🟠 LAZADA

### 1.1 Điểm hiệu suất shop — ✅ ĐẦY ĐỦ

**`GET/POST /seller/performance/get`** — *Category: Seller API*
Nguồn: https://open.lazada.com/apps/doc/api?path=/seller/performance/get

Trả `data.indicators[]`, mỗi chỉ số gồm:

| Field | Ý nghĩa |
|---|---|
| `type` | Mã chỉ số: `POSITIVE_SELLER_RATING`, `PRODUCT_RATING_COVERAGE`, … |
| `name`, `tip` | Tên + mô tả (theo ngôn ngữ — hỗ trợ `vi-VN`) |
| `score`, `score_format` | Giá trị + định dạng (`PERCENTAGE`/`MINUTES`/`HOURS`/`INTEGER`/`DOUBLE`) |
| `target`, `target_format`, `formatted_target` | **Ngưỡng mục tiêu** (vd `≥ 85%`) |
| `target_respected` | **true/false — đạt hay không đạt mục tiêu** |
| `action_url` | Link sang Seller Center để xử lý |

→ Dựng được **bảng "Sức khỏe shop": từng chỉ số · giá trị · mục tiêu · đạt/không**.

**`GET /seller/metrics/get`** — bản gọn (KPI lõi)
Nguồn: https://open.lazada.com/apps/doc/api?path=/seller/metrics/get
Trả thẳng: `positive_seller_rating`, `ship_on_time`, `response_rate`, `response_time`, `main_category_name`.

### 1.2 Trạng thái tài khoản — ✅
**`GET /seller/get`**: `status` = `ACTIVE`/`INACTIVE`/`DELETED`, `verified`, `cb` (cross-border), tên/logo/location. *Không có cờ vi phạm/sức khỏe.*

### 1.3 Đánh giá khách hàng — ✅
- **`/review/seller/history/list`** → trả danh sách **ID review** (cửa sổ lịch sử ~90 ngày, mỗi query tối đa 7 ngày).
- **`/review/seller/list/v2`** (tối đa 10 ID/lần) → nội dung review + **`ratings`: `overall_rating`, `product_rating`, `seller_rating`, `logistics_rating`** (1–5), ảnh/video, `seller_reply`, `can_reply`.
- **`/review/seller/reply/add`** → trả lời review.

### 1.4 Điểm phạt / vi phạm seller — ❌ KHÔNG có API
Quét toàn bộ danh mục Seller API: **không có** endpoint trả điểm phạt, sanction, account health/governance. Chỉ có:
- `violationDetail` ở **mức sản phẩm** (trong Product API) — không phải điểm phạt cấp shop.
- `/sellercenter/msg/list` — thông báo Seller Center dạng tự do (title/description), **không có cấu trúc điểm phạt**.
→ Điểm phạt Lazada nằm ở **UI Seller Center**, không expose qua Open API.

---

## 2. 🔴 SHOPEE

> Lưu ý dự án: CLAUDE.md ghi Shopee **"pending API"** (connector có nhưng chưa chạy thật). Ngoài ra module AccountHealth có lỗi `error_api_permission: This app type has no permission to this API` ⇒ **app cần được Shopee cấp quyền** module 103 trước khi gọi.

### 2.1 Điểm hiệu suất shop — ✅ ĐẦY ĐỦ
**`GET /api/v2/account_health/get_shop_performance`** (module 103)
Nguồn: https://open.shopee.com/documents/v2/v2.account_health.get_shop_performance?module=103&type=1

| Field | Ý nghĩa |
|---|---|
| `overall_performance.rating` | **Xếp hạng tổng: 1=Poor · 2=ImprovementNeeded · 3=Good · 4=Excellent** |
| `fulfillment_failed` / `listing_failed` / `custom_service_failed` | Số metric **không đạt** theo từng nhóm |
| `metric_list[]` | Danh sách chỉ số chi tiết |
| └ `metric_type` | 1=Fulfillment · 2=Listing · 3=Customer Service |
| └ `metric_id` | Mã chỉ số (vd 1=Late Shipment Rate, 3=Non-Fulfilment Rate, 4=Preparation Time, 11=Chat Response Rate, 22=Shop Rating, 42=Cancellation Rate, 43=Return-refund Rate, 95=Customer Satisfaction…) |
| └ `current_period`, `last_period` | Giá trị kỳ này / kỳ trước |
| └ `unit` | 1=Number · 2=Percentage · 3=Second · 4=Day · 5=Hour |
| └ `target.value`, `target.comparator` | Ngưỡng mục tiêu + toán tử (`<`,`<=`,`>`,`>=`,`=`) |

→ Dựng được "Sức khỏe shop" còn **chi tiết hơn Lazada** (rating tổng + đếm số chỉ số fail + so kỳ trước).

Bổ trợ: **`get_metric_source_detail`** (chi tiết nguồn của 1 metric), **`get_listings_with_issues`** (listing đang dính lỗi), **`get_late_orders`** (đơn giao trễ).

### 2.2 Điểm phạt "sao quả tạ" — ✅ ĐẦY ĐỦ (đúng trọng tâm)
**`GET /api/v2/account_health/get_penalty_point_history`**
Nguồn: https://open.shopee.com/documents/v2/v2.account_health.get_penalty_point_history?module=103&type=1

Tham số: `page_no`, `page_size` (1–100), `violation_type` (lọc). Trả `response.penalty_point_list[]`:

| Field | Ý nghĩa |
|---|---|
| `original_point_num` / `latest_point_num` | **Điểm phạt gốc / sau khiếu nại** của bản ghi |
| `violation_type` | Loại vi phạm (bộ mã 5–4130: trễ giao, non-fulfilment, hàng giả/IP, spam, return/refund…) |
| `issue_time` | Thời điểm bị phạt |
| `reference_id` | ID tham chiếu bản ghi |
| `total_count` | Tổng số bản ghi |

→ **`latest_point_num` chính là "sao quả tạ"**. Lưu ý: bản ghi tính theo **quý hiện tại** (điểm phạt reset theo quý).

### 2.3 Bậc trừng phạt / hạn chế — ✅
**`GET /api/v2/account_health/get_punishment_history`**
Nguồn: https://open.shopee.com/documents/v2/v2.account_health.get_punishment_history?module=103&type=1

Tham số: `punishment_status` (1=Ongoing, 2=Ended). Trả `punishment_list[]`:

| Field | Ý nghĩa |
|---|---|
| `punishment_type` | Loại phạt: 103–108 (ẩn listing khỏi tìm kiếm/danh mục, cấm tạo/sửa listing, cấm tham gia KM, mất trợ giá ship), **109=Tài khoản bị treo**, 1109–1112=giảm giới hạn listing, 2008=giới hạn đơn |
| `reason` | Bậc: 1–5 = Tier 1–5 (+ các bậc Listing Limit) |
| `start_time` / `end_time` | Thời gian hiệu lực |
| `listing_limit` / `order_limit` | Giá trị giới hạn cụ thể (khi áp dụng) |

### 2.4 Webhook real-time — ✅ (đã có spec trong repo)
- **`shop_penalty_update_push` (code 28)**: báo ngay khi điểm phạt/bậc phạt đổi — `action_type` (1 cấp điểm/2 gỡ điểm/3 đổi bậc), `issued_points`/`removed_points`, `violation_type`, `old_tier`/`new_tier`, `removed_reason`. Nguồn: https://open.shopee.com/push-mechanism/5
- **`violation_item_push` (code 16)**: listing bị BANNED/deboost — `item_status`, `violation_type`, `violation_reason`, `suggestion`, `fix_deadline_time`.

### 2.5 Đánh giá khách hàng — ✅
- **`v2.product.get_comment`** / **`v2.product.reply_comment`** (module Product). Shop Rating cũng nằm trong `get_shop_performance` (metric_id 22).

---

## 3. ⚫ TIKTOK SHOP

### 3.1 Hiệu suất doanh thu / traffic — ✅ (KHÔNG phải sức khỏe)
Module **Analytics ("Compass")**, scope `data.shop_analytics.public.read`:
- **`GET /analytics/202509/shop/performance`** — `sales.gmv`, `orders`, `items_sold` (+ breakdown theo loại nội dung).
- Product/SKU performance: `/analytics/202509/shop_products/...`, `/shop_skus/...`.
- Trend: `gmv_trend`, `traffic`, `view_trend`, `interactive_trend` (202502); Live/Video performance (202509–202512); `get-shop-performance-per-hour` (theo giờ).

### 3.2 Chất lượng CSKH — ✅
**`GET /customer_service/202407/performance`** (scope `seller.customer_service`):
`support_session_count`, `response_percentage`, `satisfaction_percentage`, `response_time_mins`, `conversion_rate`, `cs_guided_gmv`.

### 3.3 Đánh giá khách hàng — ✅
**`POST /review_rating/202605/product_reviews/search`**: `rating` (1–5), `title`, `content`, `review_media`, lọc theo product/order/SKU + khoảng thời gian.

### 3.4 Sức khỏe shop / điểm phạt / vi phạm — ❌ UI-ONLY (không API)
Nguồn: https://partner.tiktokshop.com/docv2/page/... (trang **"Shop Health"** = tính năng **Partner/Seller Center UI**, không có endpoint API):
- **AHR** (Shop Health Rating 0–1000), **SPS** (Shop Performance Score 0–5), **CHR** (Account Health Rating 0–1000) — **chỉ xem trên UI**.
- **Hồ sơ vi phạm** (loại, số điểm, trạng thái khiếu nại) — **chỉ Seller Center UI**.
- Không có API cho cancellation rate %, return rate %, late dispatch %, NDR % (chỉ tự tổng hợp thủ công từ đơn/return records).
- "Violation Management Regulations for TikTok Shop Partners" là **văn bản chính sách**, không phải API.

---

## 4. Kết luận khả thi & gợi ý hướng triển khai

**Một "báo cáo sàn" nên thiết kế theo CARD năng lực (capability) per-sàn**, mỗi sàn chỉ hiện thẻ có dữ liệu thật:

| Card | Lazada | Shopee | TikTok |
|---|---|---|---|
| **Sức khỏe & điểm chỉ số** | ✅ `/seller/performance/get` | ✅ `get_shop_performance` | ⚠️ ghép từ Compass + CSKH (không có score tổng) |
| **Điểm phạt / sao quả tạ** | ❌ deep-link Seller Center | ✅ `get_penalty_point_history` + `get_punishment_history` + webhook | ❌ deep-link Seller Center |
| **Vi phạm listing** | ⚠️ mức sản phẩm | ✅ `get_listings_with_issues` + `violation_item_push` | ❌ deep-link |
| **Đánh giá khách hàng** | ✅ review/seller/list/v2 | ✅ get_comment | ✅ product_reviews/search |
| **Trạng thái tài khoản** | ✅ /seller/get | ✅ get_punishment_history | ⚠️ deep-link |

**Khuyến nghị thứ tự làm (theo công sức/giá trị):**
1. **Lazada scorecard** — nhẹ, dữ liệu sạch, làm ngay (chỉ thêm endpoint vào `LazadaClient`).
2. **Shopee health + penalty** — giá trị cao nhất ("sao quả tạ"), nhưng cần (a) bật connector Shopee (đang pending), (b) xin Shopee cấp quyền module 103.
3. **TikTok hiệu suất (Compass) + CSKH + review** — làm "báo cáo hiệu suất", **không** gọi là "sức khỏe/điểm phạt" để tránh hiểu nhầm (không có API cho phần đó).

**Điểm cần lưu ý trước khi build:**
- Lazada/Shopee đều cần **app được duyệt đúng category/permission** cho nhóm seller-performance / account_health.
- Mọi sàn dùng pattern Connector + capability map: chỉ số nào sàn không có ⇒ `UnsupportedOperation`, UI ẩn card hoặc deep-link sang Seller Center. **Core không biết tên sàn.**
- Điểm phạt Shopee tính theo **quý**; rating Lazada theo **8 tuần gần nhất**; chú thích rõ chu kỳ khi hiển thị.
```
