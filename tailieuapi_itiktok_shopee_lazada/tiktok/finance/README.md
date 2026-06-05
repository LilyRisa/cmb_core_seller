# TikTok Shop — Finance API (Tài liệu tổng hợp)

> Nguồn: TikTok Shop Partner Center `docv2` (lấy bằng trình duyệt 2026‑06‑06) **đối chiếu** với SDK chính chủ trong repo `sdk_tiktok_seller/` (api `financeV202309Api.ts`, `financeV202501Api.ts`, `financeV202507Api.ts` + `model/finance/V*`).
> Bản scrape thô từng trang nằm ở `./_raw_scraped/` để tương lai đối chiếu lại khi TikTok cập nhật.
>
> Phạm vi tài liệu này (đúng các trang được yêu cầu):
> 1. [Get Statements — 202309](#1-get-statements-202309)
> 2. [Get Payments — 202309](#2-get-payments-202309)
> 3. [Get Withdrawals — 202309](#3-get-withdrawals-202309)
> 4. [Get Transactions by Statement — 202501](#4-get-transactions-by-statement-202501)
> 5. [Get Transactions by Order — 202501](#5-get-transactions-by-order-202501)
> 6. [Get Unsettled Transactions — 202507](#6-get-unsettled-transactions-202507)
>
> Phụ lục: [Breakdown phí/thuế/doanh thu/ship dùng chung](#phụ-lục-a--breakdown-dùng-chung-202501202507) · [Loại điều chỉnh (adjustment types)](#phụ-lục-b--adjustment-types)

---

## 0. Tổng quan (Finance API overview)

### 0.1 Khái niệm
- **Statement (sao kê):** bản ghi mô tả tập các đơn **đã đối soát** (settled). Mỗi shop mỗi ngày có **1 statement**, sinh lúc **00:00 UTC**, chốt lúc 00:00 ngày hôm sau; sau khi chốt sẽ tự phát hành và khởi tạo payment. **1 statement ↔ 1 payment** (ngoại lệ: nhiều statement có thể gộp thành 1 payment khi số tiền quá nhỏ chưa đủ điều kiện chi trả).
- **Payment (chi trả):** bản ghi mô tả giao dịch chi tiền ra (payout). Dùng để đối soát với tài khoản ngân hàng.
- **Transaction (giao dịch):** chi tiết bên trong 1 statement — có thể là giao dịch đơn (ORDER), điều chỉnh (adjustment) hoặc liên quan reserve.
- Đơn **chưa đối soát** sẽ **không** xuất hiện ở các API statement/transaction; dùng **Get Unsettled Transactions** để xem ước tính trước khi đối soát.

### 0.2 Gợi ý luồng dùng (theo official)
- **Giao dịch đã đối soát mức ĐƠN:** `Get Statements` → lấy `statement_id` → `Get Transactions by Statement`.
- **Giao dịch đã đối soát mức SKU:** `Get Statements` → `statement_id` → `Get Transactions by Statement` (lấy `order_id`) → `Get Transactions by Order`.
- **Giao dịch CHƯA đối soát:** `Get Unsettled Transactions` (trả `order_id` + `adjustment_id`, **số tiền ước tính**).
- **Đối soát dòng tiền vào bank:** `Get Payments` (+ `Get Withdrawals`).

### 0.3 Common (áp dụng cho mọi endpoint)
- **Host:** `https://open-api.tiktokglobalshop.com`
- **Method:** tất cả endpoint Finance đều là **GET**.
- **Scope bắt buộc:** `seller.finance.info`. Loại token: **seller access_token** (`user_type = 0`).
- **Header bắt buộc:**
  | Header | Bắt buộc | Mô tả |
  |---|---|---|
  | `content-type` | ✔ | `application/json` |
  | `x-tts-access-token` | ✔ | Seller access_token (lấy từ Get Access Token, `user_type=0`). |
- **Common query bắt buộc cho mọi request** (TikTok signing):
  | Query | Bắt buộc | Mô tả |
  |---|---|---|
  | `app_key` | ✔ | App key của ứng dụng. |
  | `timestamp` | ✔ | Unix timestamp GMT (UTC+00:00). |
  | `sign` | ✔ | Chữ ký HMAC‑SHA256 (xem `TikTokSigner` trong repo). |
  | `shop_cipher` | ✔ | Định danh shop (lấy từ *Get Authorization Shop*). Sai/thiếu với shop xuyên biên giới sẽ trả kết quả sai. |
  > Trong repo, toàn bộ phần ký + `app_key/timestamp/sign/shop_cipher` được `TikTokClient::shopGet()` xử lý tự động (`app/app/Integrations/Channels/Tiktok/TikTokClient.php`).
- **Phân trang (cursor):** `page_size` (mặc định 20, khoảng `[1,100]`), `page_token` (lấy từ `data.next_page_token` của trang trước; trang đầu để trống).
- **Envelope phản hồi (chung):**
  | Field | Type | Mô tả |
  |---|---|---|
  | `code` | int | `0` = thành công; khác `0` = lỗi. |
  | `message` | string | Thông điệp thành công/thất bại. |
  | `request_id` | string | Mã log request (dùng khi báo lỗi với TikTok). |
  | `data` | object | Dữ liệu trả về (chi tiết theo từng endpoint). |
- **Tiền tệ:** mọi field số tiền là **string**, đơn vị theo `currency` (ISO 4217). Số âm = khoản trừ/hoàn.
- **Error code chung:** `36009003` = Internal error (thử lại; nếu lặp lại liên hệ TikTok). Các lỗi đặc thù ghi ở từng endpoint.

---

## 1. Get Statements (202309)

Lấy danh sách **statement** của shop theo khoảng thời gian hoặc theo trạng thái thanh toán — dùng để xem tổng quan sao kê theo ngày và biết statement nào đã/chưa được chi trả. Chi tiết giao dịch: dùng *Get Transactions by Statement / by Order*.

- **GET** `/finance/202309/statements`
- **Áp dụng:** seller mọi khu vực. Chỉ có dữ liệu **sau 2023‑07‑01**.
- **Scope:** `seller.finance.info`

**Query (ngoài common ở §0.3):**
| Query | Bắt buộc | Type | Mô tả |
|---|---|---|---|
| `sort_field` | ✔ | string | Chỉ hỗ trợ `statement_time`. |
| `sort_order` | | string | `ASC` (mặc định) \| `DESC`. |
| `statement_time_ge` | | int | Lọc statement sinh **từ** mốc này (Unix). |
| `statement_time_lt` | | int | Lọc statement sinh **trước** mốc này (Unix). |
| `payment_status` | | string | `PAID` \| `FAILED` \| `PROCESSING`. Mặc định: trả mọi trạng thái. |
| `page_size`, `page_token` | | | Phân trang. |

> **Ghi chú thời gian:** `statement_time_ge` + `statement_time_lt` tạo thành điều kiện lọc; nếu chỉ điền 1 đầu, đầu còn lại mặc định (current time / earliest shop time). Statement sinh hằng ngày 00:00 UTC. Ví dụ lấy Oct 5→Oct 10: `statement_time_ge` = 00:00 Oct 6 (hoặc bất kỳ giờ Oct 5 trừ 00:00), `statement_time_lt` = bất kỳ giờ Oct 11 (trừ 00:00).

**Response `data`:** `next_page_token` (string), `statements` (array).

**`data.statements[]`** (`GetStatementsResponseDataStatements`):
| Field | Type | Mô tả |
|---|---|---|
| `id` | string | Statement ID. |
| `statement_time` | int | Thời điểm sinh statement (Unix). Sinh hằng ngày 00:00 UTC, gồm toàn bộ giao dịch của ngày trước. |
| `settlement_amount` | string | Số tiền đối soát (settlement). |
| `currency` | string | Mã tiền tệ ISO 4217. |
| `revenue_amount` | string | Doanh thu cuối tại thời điểm đối soát. Áp dụng mọi khu vực **trừ** UK và US. |
| `net_sales_amount` | string | Doanh thu sau khi trừ chiết khấu người bán. Chỉ áp dụng local seller **ngoài** khu vực SEA. |
| `fee_amount` | string | Phí TikTok Shop thu khi đối soát. (Không gồm chi phí ship, trừ local seller SEA thì có gồm.) |
| `shipping_cost_amount` | string | Phí ship. Chỉ áp dụng local seller ngoài SEA. |
| `adjustment_amount` | string | Số tiền điều chỉnh (lý do xem *Get Transactions by Statement*). |
| `payment_status` | string | `PAID` \| `FAILED` \| `PROCESSING`. |
| `payment_id` | string | Payment ID. |
| `payment_time` | int | Thời điểm chi trả (Unix). |

**Ví dụ response:**
```json
{
  "code": 0,
  "message": "Success",
  "data": {
    "next_page_token": "6AsPQsUMvH3RkchN...",
    "statements": [{
      "id": "7238804564097517339", "statement_time": 1685548800,
      "settlement_amount": "100", "currency": "GBP",
      "revenue_amount": "200", "fee_amount": "-30", "adjustment_amount": "-70",
      "payment_status": "PAID", "payment_id": "3459275187040258849",
      "net_sales_amount": "-70", "shipping_cost_amount": "-70", "payment_time": 1685548800
    }]
  }
}
```
**Error code đặc thù:** `36009003`.

---

## 2. Get Payments (202309)

Lấy danh sách **payment** (chi trả tự động) của shop theo khoảng thời gian, gồm trạng thái hiện tại. Dùng để đối soát payment với giao dịch trong tài khoản ngân hàng của seller.

- **GET** `/finance/202309/payments`
- **Áp dụng:** **Không** khả dụng cho thị trường SEA.
- **Scope:** `seller.finance.info`

**Query (ngoài common):**
| Query | Bắt buộc | Type | Mô tả |
|---|---|---|---|
| `sort_field` | ✔ | string | Chỉ hỗ trợ `create_time`. |
| `sort_order` | | string | `ASC` (mặc định) \| `DESC`. |
| `create_time_ge` | | int | Lọc payment xảy ra **từ** mốc này (Unix). |
| `create_time_lt` | | int | Lọc payment xảy ra **trước** mốc này (Unix). |
| `page_size`, `page_token` | | | Phân trang. |

> `create_time_ge` + `create_time_lt` tạo điều kiện lọc; nếu chỉ điền 1 đầu, đầu còn lại mặc định (current time / earliest shop time).

**Response `data`:** `next_page_token` (string), `payments` (array).

**`data.payments[]`** (`GetPaymentsResponseDataPayments`):
| Field | Type | Mô tả |
|---|---|---|
| `id` | string | Payment ID. |
| `create_time` | int | Thời điểm khởi tạo payment (Unix). |
| `paid_time` | int | Thời điểm payment xử lý thành công (Unix). |
| `status` | string | `PAID` \| `FAILED` \| `PROCESSING`. |
| `bank_account` | string | Số tài khoản ngân hàng (đã che, chỉ lộ 4 số cuối). |
| `exchange_rate` | string | Tỷ giá (6 chữ số thập phân). |
| `amount` | object | Số tiền payment cuối — xem dưới. |
| `settlement_amount` | object | Số tiền settlement — `{ value, currency }`. |
| `reserve_amount` | object | Số tiền giữ lại (reserve) — `{ value, currency }`. |
| `payment_amount_before_exchange` | object | Số tiền gốc trước quy đổi — `{ value, currency }`. |

**`amount` / `settlement_amount` / `reserve_amount` / `payment_amount_before_exchange`:**
| Field | Type | Mô tả |
|---|---|---|
| `value` | string | Giá trị tiền (amount=payment cuối; settlement=settlement; reserve=reserved; before_exchange=gốc). |
| `currency` | string | Mã tiền tệ ISO 4217 (amount = tiền sau quy đổi; còn lại = tiền gốc). |

**Ví dụ response (rút gọn):**
```json
{ "code": 0, "data": { "next_page_token": "6AsP...", "payments": [{
  "id": "3458767051733897992", "create_time": 1636105796, "status": "PAID",
  "amount": { "value": "100", "currency": "GBP" },
  "settlement_amount": { "value": "130", "currency": "GBP" },
  "reserve_amount": { "value": "-30", "currency": "GBP" },
  "payment_amount_before_exchange": { "value": "...", "currency": "..." }
}]}}
```
**Error code đặc thù:** `36009003`.

---

## 3. Get Withdrawals (202309)

Lấy danh sách bản ghi **rút tiền/giao dịch dòng tiền** (khi seller rút tiền từ TikTok Shop) theo khoảng thời gian và **loại giao dịch**.

- **GET** `/finance/202309/withdrawals`
- **Scope:** `seller.finance.info`

**Query (ngoài common):**
| Query | Bắt buộc | Type | Mô tả |
|---|---|---|---|
| `types` | ✔ | []string | Loại giao dịch (CSV). `WITHDRAW` (seller rút settlement về thẻ) · `SETTLE` (platform settle cho seller) · `TRANSFER` (trợ giá/khấu trừ theo chính sách) · `REVERSE` (rút thất bại do sai thẻ). |
| `create_time_ge` | | int | Mốc bắt đầu khoảng thời gian (Unix). |
| `create_time_lt` | | int | Mốc kết thúc khoảng thời gian (Unix). |
| `page_size`, `page_token` | | | Phân trang (page_size mặc định 20, [1‑100]). |

**Response `data`:** `next_page_token` (string), `total_count` (int), `withdrawals` (array).

**`data.withdrawals[]`** (`GetWithdrawalsResponseDataWithdrawals`):
| Field | Type | Mô tả |
|---|---|---|
| `id` | string | ID giao dịch rút tiền (do TTS sinh). |
| `type` | string | `WITHDRAW` \| `SETTLE` \| `TRANSFER` \| `REVERSE` (như trên). |
| `amount` | string | Số tiền rút. |
| `currency` | string | Mã tiền tệ ISO 4217. |
| `status` | string | `PROCESSING` (đang xử lý) \| `SUCCESS` (đã chuyển cho seller) \| `FAILED` (thất bại). |
| `create_time` | int | Thời điểm tạo (Unix). |

**Ví dụ response:**
```json
{ "code": 0, "message": "Success", "request_id": "2022...",
  "data": { "next_page_token": "6AsP...", "total_count": 1, "withdrawals": [{
    "id": "EFASDFSAFDA23432DFAFDSA", "type": "WITHDRAW", "amount": "100",
    "currency": "IDR", "status": "PROCESSING", "create_time": 1623812664 }]}}
```
**Error code đặc thù:** `22005007` (merchant không tồn tại), `36009003`.

---

## 4. Get Transactions by Statement (202501)

Lấy chi tiết 1 statement gồm danh sách **transaction** (đơn ORDER, điều chỉnh adjustment, hoặc reserve). Muốn chi tiết mức **SKU** của 1 đơn → dùng *Get Transactions by Order*.

- **GET** `/finance/202501/statements/{statement_id}/statement_transactions`
- **Áp dụng:** seller mọi khu vực, dữ liệu sau 2023‑07‑01 (US cross‑border: dữ liệu **trước 2025‑04‑30 không có**).
- **Scope:** `seller.finance.info`

**Path:** `statement_id` (string, bắt buộc) — ID statement.

**Query (ngoài common):**
| Query | Bắt buộc | Type | Mô tả |
|---|---|---|---|
| `sort_field` | ✔ | string | Chỉ hỗ trợ `order_create_time`. |
| `sort_order` | | string | `ASC` (mặc định) \| `DESC`. |
| `page_size`, `page_token` | | | Phân trang. |

**Response `data`** (`GetTransactionsbyStatementResponseData`):
| Field | Type | Mô tả |
|---|---|---|
| `id` | string | Statement ID. |
| `create_time` | int | Thời điểm sinh statement (Unix), 00:00 UTC. |
| `status` | string | Trạng thái statement. Chỉ hỗ trợ `SETTLED`. |
| `currency` | string | Mã tiền tệ ISO 4217. |
| `payable_amount` | string | Số tiền chi trả cuối sau khi tính reserve. `= total_settlement_amount + total_reserve_amount`. |
| `total_reserve_amount` | string | Tổng tiền giữ lại theo Reserve Policy (chỉ UK/US local). (+) đã release; (−) đang giữ. |
| `total_settlement_amount` | string | Tổng settlement. `= total_revenue − total_shipping_cost − total_fee_tax − total_adjustment`. |
| `total_settlement_breakdown` | object | Phân rã tổng settlement — xem dưới. |
| `total_count` | int | Số bản ghi transaction. |
| `next_page_token` | string | Cursor trang sau. |
| `transactions` | array | Danh sách transaction — xem dưới. |

**`data.total_settlement_breakdown`:**
| Field | Type | Mô tả |
|---|---|---|
| `total_revenue_amount` | string | Tổng doanh thu tại thời điểm đối soát (= net sales). |
| `total_shipping_cost_amount` | string | Tổng chi phí ship tại thời điểm đối soát. |
| `total_fee_tax_amount` | string | Tổng phí + thuế (không gồm chi phí ship). |
| `total_adjustment_amount` | string | Tổng điều chỉnh (theo chính sách — xem `transactions.type`). |

**`data.transactions[]`** (`GetTransactionsbyStatementResponseDataTransactions`):
| Field | Type | Mô tả |
|---|---|---|
| `id` | string | Transaction ID. |
| `type` | string | Loại giao dịch / điều chỉnh — **xem [Phụ lục B](#phụ-lục-b--adjustment-types)** (ORDER, RESERVE, và đầy đủ các loại adjustment). |
| `order_id` | string | Order ID (mỗi transaction gắn với order_id **hoặc** adjustment_id). |
| `order_create_time` | int | Thời điểm tạo đơn (Unix). |
| `adjustment_id` | string | Adjustment ID (nếu là điều chỉnh). |
| `adjustment_order_id` | string | Order ID liên quan điều chỉnh (nếu có). |
| `settlement_amount` | string | Settlement của đơn. `= revenue − shipping_cost − fee_tax − adjustment`. |
| `revenue_amount` | string | Doanh thu (= tổng `revenue_breakdown`). |
| `shipping_cost_amount` | string | Chi phí ship (= tổng đóng góp trong `shipping_cost_breakdown`). |
| `fee_tax_amount` | string | Tổng phí + thuế (= tổng đóng góp trong `fee_tax_breakdown`; không gồm ship). |
| `adjustment_amount` | string | Số tiền điều chỉnh theo chính sách. |
| `reserve_id` | string | ID giao dịch reserve. |
| `reserve_amount` | string | Tiền giữ lại theo Reserve Policy. (+) released; (−) withheld. |
| `reserve_status` | string | `COLLECTED` (đã giữ một phần settlement) \| `RELEASED` (đã release & chi trả). |
| `associated_order_id` | string | Order ID gắn với giao dịch reserve. |
| `estimated_release_time` | string | Thời điểm dự kiến release reserve (Unix); rỗng nếu đã release. |
| `revenue_breakdown` | object | **Xem [Phụ lục A](#a3-revenue_breakdown)**. |
| `shipping_cost_breakdown` | object | **Xem [Phụ lục A](#a4-shipping_cost_breakdown--supplementary_component)**. |
| `fee_tax_breakdown` | object | `{ fee, tax }` — **xem [Phụ lục A](#a1-fee_tax_breakdownfee) & [A2](#a2-fee_tax_breakdowntax)**. |
| `supplementary_component` | object | Thành phần bổ sung (xem A4). |

**Ví dụ response (rút gọn):**
```json
{ "code": 0, "data": {
  "id": "7238804564097517339", "create_time": 1685548800, "status": "SETTLED",
  "currency": "GBP", "payable_amount": "150", "total_reserve_amount": "20",
  "total_settlement_amount": "130",
  "total_settlement_breakdown": { "total_revenue_amount": "100",
    "total_shipping_cost_amount": "120", "total_fee_tax_amount": "20", "total_adjustment_amount": "0" },
  "total_count": 2, "next_page_token": "6AsP...",
  "transactions": [ { "id": "1636700041413599290", "type": "ORDER", "...": "..." } ] }}
```
**Error code đặc thù:** `36009003`.

---

## 5. Get Transactions by Order (202501)

Lấy chi tiết mức **SKU** của các giao dịch thuộc 1 đơn: doanh thu, phí, hoa hồng, ship, thuế, hoàn tiền.

- **GET** `/finance/202501/orders/{order_id}/statement_transactions`
- **Áp dụng:** seller mọi khu vực, dữ liệu sau 2023‑07‑01 (US cross‑border: trước 2025‑04‑30 không có).
- **Scope:** `seller.finance.info`

**Path:** `order_id` (string, bắt buộc).
**Query:** chỉ common (`app_key`, `sign`, `timestamp`, `shop_cipher`). *(Không phân trang — trả toàn bộ SKU của đơn.)*

**Response `data`** (`GetTransactionsbyOrderResponseData`):
| Field | Type | Mô tả |
|---|---|---|
| `order_id` | string | Order ID. |
| `order_create_time` | int | Thời điểm tạo đơn (Unix). |
| `currency` | string | Mã tiền tệ ISO 4217. |
| `revenue_amount` | string | Doanh thu của đơn tại thời điểm đối soát (= net sales). |
| `fee_and_tax_amount` | string | Tổng phí + thuế (không gồm ship). |
| `shipping_cost_amount` | string | Chi phí ship tại thời điểm đối soát. |
| `settlement_amount` | string | Settlement của đơn. `= revenue − shipping_cost − fee_tax`. |
| `total_count` | int | Số bản ghi transaction. |
| `sku_transactions` | array | Danh sách giao dịch theo SKU — xem dưới. |

**`data.sku_transactions[]`** (`GetTransactionsbyOrderResponseDataSkuTransactions`):
| Field | Type | Mô tả |
|---|---|---|
| `sku_id` | string | SKU ID. |
| `sku_name` | string | Tên SKU. |
| `product_name` | string | Tên sản phẩm. |
| `quantity` | string | Số lượng SKU trong đợt đối soát. |
| `statement_id` | string | Statement ID. |
| `settlement_amount` | string | Settlement cho SKU. |
| `revenue_amount` | string | Doanh thu (= tổng `revenue_breakdown`). |
| `fee_tax_amount` | string | Tổng phí + thuế (= tổng đóng góp `fee_tax_breakdown`; không gồm ship). |
| `shipping_cost_amount` | string | Chi phí ship (= tổng đóng góp `shipping_cost_breakdown`). |
| `revenue_breakdown` | object | **Xem [Phụ lục A](#a3-revenue_breakdown)**. |
| `fee_tax_breakdown` | object | `{ fee, tax }` — **xem [A1](#a1-fee_tax_breakdownfee)/[A2](#a2-fee_tax_breakdowntax)**. |
| `shipping_cost_breakdown` | object | **Xem [A4](#a4-shipping_cost_breakdown--supplementary_component)**. |

**Ví dụ response (rút gọn):**
```json
{ "code": 0, "data": {
  "order_id": "5793990727963214852", "order_create_time": 1685548800, "currency": "GBP",
  "revenue_amount": "200", "fee_and_tax_amount": "-30", "shipping_cost_amount": "-70",
  "settlement_amount": "130",
  "sku_transactions": [ { "sku_id": "1636700041413599290", "sku_name": "Test SKU name",
    "statement_id": "7238804564097517339", "product_name": "Test Product name", "quantity": "1",
    "settlement_amount": "130", "revenue_amount": "200",
    "revenue_breakdown": { "subtotal_before_discount_amount": "210", "seller_discount_amount": "-10", "...": "..." } } ] }}
```
**Error code đặc thù:** `36009003`.

> **Lưu ý phiên bản:** repo còn có model **202309** cho by‑order/by‑statement với schema **phẳng** (rất nhiều field `*_amount` ở thẳng mức transaction, không gom breakdown) — xem `_raw_scraped/tiktok_finance_models.md` mục `Finance202309GetTransactionsby*`. Phiên bản **202501** (tài liệu này) gom các field đó vào `fee_tax_breakdown / revenue_breakdown / shipping_cost_breakdown`.

---

## 6. Get Unsettled Transactions (202507)

Lấy danh sách giao dịch **CHƯA đối soát** (gồm Orders & Adjustments) với phân rã phí chi tiết theo danh sách order_id và adjustment_id.

- **GET** `/finance/202507/orders/unsettled`
- **Áp dụng:** chỉ trả giao dịch tạo **sau 2025‑01‑01**. Khi 1 giao dịch đã settle thì **không** còn trả ở đây nữa → chuyển dùng *Get Transactions by Statement*.
- **⚠ Quan trọng:** mọi số tiền là **ước tính (estimated)**, có thể thay đổi trước khi đối soát — chỉ để tham khảo; số chốt cuối chỉ lấy từ các API statement.
- **Scope:** `seller.finance.info`

**Query (ngoài common):**
| Query | Bắt buộc | Type | Mô tả |
|---|---|---|---|
| `sort_field` | ✔ | string | Chỉ hỗ trợ `order_create_time`. |
| `sort_order` | | string | `ASC` (mặc định) \| `DESC`. |
| `search_time_ge` | | int | Lọc **từ** mốc này (Unix). Nếu điền `ge` mà thiếu `lt` → `lt` mặc định current time; nếu điền `lt` mà thiếu `ge` → `ge` mặc định 2025‑01‑01. |
| `search_time_lt` | | int | Mốc kết thúc khoảng tìm. |
| `page_size`, `page_token` | | | Phân trang. |

**Response `data`** (`GetUnsettledTransactionsResponseData`):
| Field | Type | Mô tả |
|---|---|---|
| `total_count` | int | Số bản ghi transaction. |
| `sum_est_settlement_amount` | string | Tổng settlement **ước tính**. |
| `sum_est_revenue_amount` | string | Tổng doanh thu **ước tính**. |
| `sum_est_fee_amount` | string | Tổng phí **ước tính**. |
| `sum_est_adjustment_amount` | string | Tổng điều chỉnh **ước tính**. |
| `next_page_token` | string | Cursor trang sau. |
| `transactions` | array | Danh sách transaction — xem dưới. |

**`data.transactions[]`** (`GetUnsettledTransactionsResponseDataTransactions`):
| Field | Type | Mô tả |
|---|---|---|
| `id` | string | Transaction ID. |
| `type` | string | Loại giao dịch/điều chỉnh — xem [Phụ lục B](#phụ-lục-b--adjustment-types) (tập giá trị hơi khác bản 202501; chi tiết ở `_raw_scraped`). |
| `status` | string | Chỉ hỗ trợ `UNSETTLED`. |
| `currency` | string | Mã tiền tệ ISO 4217. |
| `order_id` | string | Order ID (gắn order_id **hoặc** adjustment_id). |
| `order_create_time` | int | Thời điểm tạo đơn (Unix). |
| `order_delivery_time` | int | Thời điểm giao đơn (Unix); rỗng nếu chưa giao. |
| `adjustment_id` | string | Adjustment ID (nếu là điều chỉnh). |
| `adjustment_order_id` | string | Order ID liên quan điều chỉnh (nếu có). |
| `estimated_settlement` | string | Thời điểm đối soát ước tính: "x days after delivery" (chưa giao) **hoặc** Unix timestamp (đã giao). |
| `unsettled_reason` | string | Lý do đang chờ đối soát. |
| `est_settlement_amount` | string | Settlement ước tính. `= revenue − shipping_cost − fee_tax − adjustment`. |
| `est_revenue_amount` | string | Doanh thu ước tính (= tổng `revenue_breakdown`). |
| `est_shipping_cost_amount` | string | Ship ước tính (chưa giao thì chưa có đủ); = tổng đóng góp `shipping_cost_breakdown`. |
| `est_fee_tax_amount` | string | Phí + thuế ước tính (= tổng đóng góp `fee_tax_breakdown`). |
| `est_adjustment_amount` | string | Điều chỉnh ước tính theo chính sách. |
| `revenue_breakdown` | object | **Xem [A3](#a3-revenue_breakdown)** (cấu trúc tương đương 202501). |
| `fee_tax_breakdown` | object | `{ fee, tax }` — **xem [A1](#a1-fee_tax_breakdownfee)/[A2](#a2-fee_tax_breakdowntax)** (bản 202507 ít field hơn — chi tiết ở `_raw_scraped`). |
| `shipping_cost_breakdown` | object | **Xem [A4](#a4-shipping_cost_breakdown--supplementary_component)**. |

**Ví dụ response (rút gọn):**
```json
{ "code": 0, "data": {
  "next_page_token": "6AsP...", "total_count": 2,
  "sum_est_settlement_amount": "100", "sum_est_revenue_amount": "10",
  "sum_est_adjustment_amount": "-10", "sum_est_fee_amount": "-10",
  "transactions": [ { "type": "ORDER", "id": "1636700041413599290", "status": "UNSETTLED",
    "currency": "USD", "estimated_settlement": "1685548800", "unsettled_reason": "waiting for delivery",
    "order_create_time": 1685548800, "order_delivery_time": 1685548800,
    "order_id": "576463220456522968", "adjustment_id": "7238804564097517332",
    "adjustment_order_id": "576463220456522968" } ] }}
```
**Error code đặc thù:** `36009003`.

---

# Phụ lục A — Breakdown dùng chung (202501/202507)

Các object con dưới đây xuất hiện trong: `Get Transactions by Statement` (`transactions[]`), `Get Transactions by Order` (`sku_transactions[]`), `Get Unsettled Transactions` (`transactions[]`). **Tên field & ý nghĩa lấy từ SDK 202501** (bản 202507 có tập field ít hơn, một vài tên hơi khác — đối chiếu `_raw_scraped/tiktok_finance_models.md`). Mọi field là **string** (số tiền theo `currency`).

## A1. `fee_tax_breakdown.fee`
> `fee_tax_amount = Σ(fee) + Σ(tax)`. Tất cả là phí TikTok thu (không gồm ship).

| Field | Mô tả |
|---|---|
| `platform_commission_amount` | Hoa hồng nền tảng. |
| `referral_fee_amount` | Referral fee xử lý đơn thành công (chỉ US). |
| `transaction_fee_amount` | Phí giao dịch xử lý đơn thành công. |
| `affiliate_commission_amount` | Hoa hồng trả cho creator (= giá KH trả × % hoa hồng). |
| `affiliate_commission_before_pit` | Hoa hồng affiliate trước khi khấu trừ thuế TNCN. |
| `affiliate_commission_amount_before_pit` | Hoa hồng affiliate ads trước khấu trừ PIT (chỉ SEA). |
| `affiliate_commission_deposit` | Khoản giữ cho hoa hồng creator sau khi đơn được thanh toán (theo order volume). |
| `affiliate_commission_release` | Hoàn deposit hoa hồng sau khi đối soát. |
| `affiliate_ads_commission_amount` | Hoa hồng cho đơn đủ điều kiện từ ads. |
| `affiliate_partner_commission_amount` | Hoa hồng mua qua link affiliate partner. |
| `tap_shop_ads_commission` | Hoa hồng quảng cáo trả cho TikTok Shop Affiliate Partner (TAP). |
| `tsp_commission_amount` | Hoa hồng do TikTok Shop Partners (TSP) thu. |
| `gmv_max_ad_fee_amount` | % trừ trên net sales/đơn cho quảng cáo GMV Max. |
| `mall_service_fee_amount` | Phí dùng TikTok Shop Mall. |
| `flash_sales_service_fee_amount` | Phí tham gia flash sales. |
| `live_specials_fee_amount` | Phí tham gia LIVE Specials Programme. |
| `voucher_xtra_service_fee_amount` | Phí chương trình Voucher Xtra. |
| `pre_order_service_fee_amount` | Phí tham gia chương trình Pre‑order. |
| `bonus_cashback_service_fee_amount` | Phí chương trình bonus cashback. |
| `seller_growth_fee_amount` | Phí mỗi đơn thành công để cấp Bonus Cashback & quyền lợi tăng trưởng. |
| `cofunded_promotion_service_fee_amount` | Phí dịch vụ co‑funded promotion. |
| `cofunded_creator_bonus_amount` | Phần creator bonus mà seller co‑fund trong campaign boost hoa hồng. |
| `smart_promotion_fee_amount` | Gồm quỹ TikTok Shop hỗ trợ promotion cho khách. |
| `campaign_period_fee_cfp_amount` / `_tax_amount` | Phí (và thuế) trong campaign Co‑funded Promotion. |
| `campaign_period_fee_sp_amount` / `_tax_amount` | Phí (và thuế) trong campaign Smart Promotion. |
| `campaign_resource_fee` | Phí campaign resource khi seller tham gia chương trình. |
| `sfp_service_fee_amount` | Phí Seller Free Shipping Programme. |
| `shipping_fee_guarantee_service_fee` | Phí cố định/đơn trong Shipping Fee Guarantee Program. |
| `external_affiliate_marketing_fee_amount` | Phí External Affiliate Marketing Solution Program. |
| `credit_card_handling_fee_amount` | Phí xử lý khi khách trả bằng thẻ tín dụng. |
| `seller_paylater_handling_fee_amount` | Phí PayLater cho seller. |
| `dt_handling_fee_amount` | Phí xử lý đơn fulfilled by Dilayani Tokopedia. |
| `dynamic_commission_amount` | Dynamic commission/đơn giao thành công (chỉ Indonesia). |
| `fee_per_item_sold_amount` | Phí/sản phẩm bán (chỉ Brazil). |
| `installation_service_fee` | Phí dùng dịch vụ lắp đặt của nền tảng. |
| `platform_special_service_fee_amount` | Phần Distance Shipping Fee khách trả vượt mức phí của NCC logistics, TikTok giữ. |
| `platform_semi_managed_commission_fee` / `_tax` | Hoa hồng semi‑managed (% của giá gốc − seller discount + ship khách trả) và thuế kèm. |
| `epr_pob_service_fee_amount` | Đóng góp môi trường (EPR) TikTok trả hộ cho PRO. |
| `vn_fix_infrastructure_fee` | Phí hạ tầng cố định (đơn đã giao, mức main order). |
| `refund_administration_fee_amount` | Phí quản trị hoàn 20% trừ trên tổng referral fee được hoàn. |

> **Bản 202507 (`fee`)** rút gọn còn các field tiêu biểu: `platform_commission_amount`, `referral_fee_amount`, `transaction_fee_amount`, `affiliate_*`, `pit_withheld_from_ads_commission_amount`, `retail_delivery_fee_amount`/`_payment_amount`/`_refund_amount`, `seller_growth_fee_amount`, `sfp_service_fee_amount`, `smart_promotion_fee_amount`, `mall_service_fee_amount`, `live_specials_fee_amount`, `dynamic_commission_amount`, `credit_card_handling_fee_amount`, `campaign_period_fee_*`, `platform_special_service_fee_amount`, `vn_fix_infrastructure_fee`, `refund_administration_fee_amount`.

## A2. `fee_tax_breakdown.tax`
| Field | Mô tả |
|---|---|
| `vat_amount` | VAT TikTok trả hộ seller (đơn cross‑border). |
| `local_vat_amount` | VAT TikTok trả hộ (đơn local shop). |
| `import_vat_amount` | Import VAT (đơn cross‑border; tại Nhật là JCT). |
| `gst_amount` | GST hàng giá trị thấp nhập Singapore (từ 2023‑01‑01). |
| `sst_amount` | SST hàng giá trị thấp nhập Malaysia (từ 2024‑01‑01). |
| `customs_duty_amount` | Thuế hải quan hàng cross‑border. |
| `customs_clearance_amount` | Phí thông quan của NCC logistics (cross‑border). |
| `anti_dumping_duty_amount` | Thuế chống bán phá giá hàng nhập. |
| `pit_amount` | Thuế TNCN nền tảng trả hộ seller. |
| `isr_amount` | Thuế TNCN liên bang Mexico TikTok phải khấu trừ. |
| `iva_amount` | VAT Mexico TikTok khấu trừ trên sản phẩm chịu thuế. |
| `cedular_tax` | Thuế cedular Guanajuato (Mexico). |
| `sales_tax_referral_fee_amount` | Thuế bán hàng trên referral fee (một số bang US). |
| `smart_promotion_fee_tax_amount` | Thuế cho Smart Promotion fee. |
| *(202507 thêm)* `sales_tax_amount` / `sales_tax_payment_amount` / `sales_tax_refund_amount` | Thuế bán hàng cuối / dự kiến / hoàn (US). |

## A3. `revenue_breakdown`
| Field | Mô tả |
|---|---|
| `subtotal_before_discount_amount` | Tổng giá mọi item **trước** chiết khấu seller & platform (= gross sales của shop). |
| `seller_discount_amount` | Tổng chiết khấu seller tài trợ (Product Discount, Flash Deal, BMSM, Voucher, Bundle, phần seller trong co‑funded voucher, chiết khấu campaign). |
| `seller_discount_refund_amount` | Chiết khấu hoàn lại cho seller do trả/hoàn. |
| `refund_subtotal_before_discount_amount` | Tổng giá item bị hoàn trước chiết khấu (= gross sales refund). |
| `cod_service_fee_amount` | Phí dịch vụ COD thu khách (chỉ Saudi Arabia). |
| `refund_cod_service_fee_amount` | Hoàn phí dịch vụ COD (chỉ Saudi Arabia). |
| `distant_item_fee_amount` | Phí khoảng cách cho item khách trả (Horizon+ Program). |

## A4. `shipping_cost_breakdown` (+ `supplementary_component`)
> `shipping_cost_amount = Σ(các đóng góp)`. Chi tiết một số mục nằm trong `supplementary_component`.

**`shipping_cost_breakdown`:**
| Field | Mô tả |
|---|---|
| `actual_shipping_fee_amount` | Phí ship thực theo cân nặng/kích thước carrier đo (chi tiết ở `supplementary_component`). |
| `customer_paid_shipping_fee_amount` | Phí ship khách chịu (theo cân nặng seller khai); số âm = phần hoàn lại. |
| `distant_shipping_fee_amount` | Phí khoảng cách ship khách trả (Horizon+). |
| `return_shipping_fee_amount` | Phí ship seller trả khi giao hàng trả. |
| `return_shipping_fee_paid_buyer_amount` | Hoàn phí ship khách đã trả để trả hàng. |
| `return_shipping_label_fee_amount` | Phí nhãn trả hàng khách chịu (thu hộ seller). |
| `exchange_shipping_fee_amount` | Phí ship seller trả khi đổi hàng (chỉ Indonesia). |
| `replacement_shipping_fee_amount` | Phí ship seller trả khi thay hàng (chỉ Indonesia). |
| `shipping_fee_discount_amount` | Tổng trợ giá/ưu đãi ship của nền tảng (chi tiết ở `supplementary_component`). |
| `shipping_insurance_fee_amount` | Phí bảo hiểm ship seller mua thêm. |
| `signature_confirmation_fee_amount` | Phí gói cần ký xác nhận. |
| `shipping_app_service_fee_amount` | Phí Shipping App Service tạo nhãn từ phí ship. |
| `seller_self_shipping_service_fee_amount` | Phí dịch vụ với đơn seller tự ship (gói không đạt tiêu chí miễn). |
| `logistics_service_fee` | Phí logistics theo vòng đời đơn (theo tier cân nặng & tuyến). |
| `fbt_free_shipping_fee_amount` | Phí seller chịu khi cho khách free ship qua FBT. |
| `fbt_fulfillment_fee_reimbursement_amount` | Hoàn từ TikTok cho đơn FBT không đủ điều kiện free ship (chỉ US). |
| `fbt_key_merchant_subsidy` / `fbt_overall_merchant_subsidy` | Trợ giá theo chương trình key merchant / toàn bộ merchant FBT. |
| `failed_delivery_subsidy_amount` | TikTok bù phần phí ship giao thất bại vượt mức chuẩn. |
| `free_return_subsidy_amount` | Hoàn phần ship trả hàng do nền tảng tài trợ (free returns). |

**`shipping_cost_breakdown.supplementary_component`** (mức chi tiết của 2 field tổng ở trên):
| Field | Mô tả |
|---|---|
| `fbm_shipping_cost_amount` | Phí ship khi dùng TikTok Shipping (thuộc `actual_shipping_fee_amount`). |
| `fbt_shipping_cost_amount` | Phí ship đơn FBT (thuộc `actual_shipping_fee_amount`; EU & UK). |
| `fbt_fulfillment_fee_amount` | Phí ship + fulfillment kho đơn FBT (thuộc `actual_shipping_fee_amount`; chỉ US). |
| `platform_shipping_fee_discount_amount` | Giảm phí ship theo campaign (thuộc `shipping_fee_discount_amount`). |
| `promo_shipping_incentive_amount` | Incentive ship thêm khi đăng ký Co‑Funded Free Shipping (thuộc `shipping_fee_discount_amount`); âm = thu hồi. |
| `shipping_fee_subsidy_amount` | Trợ giá ship nền tảng cho seller ship (thuộc `shipping_fee_discount_amount`). (+) nhận; (−) phải trả lại. |
| `seller_shipping_fee_discount_amount` | Giảm phí ship do seller tự cấp. |
| `customer_shipping_fee_offset_amount` | Phí offset để net charge $0 cho seller (chỉ US). |
| `refunded_customer_shipping_fee_amount` | Phí ship hoàn khách do trả/hoàn (thuộc `customer_paid_shipping_fee_amount`). |
| `return_refund_subsidy_amount` | TikTok bù phần phí ship trả/hoàn vượt mức chuẩn (thuộc `customer_paid_shipping_fee_amount`). |
| `shipping_fee_guarantee_reimbursement` | Hoàn từ Shipping Fee Guarantee Program (giao thất bại/trả hàng). |
| `fbt_fulfillment_fee_reimbursement_amount` | **Deprecated** — trả chuỗi rỗng; dùng field cùng tên ở `shipping_cost_breakdown`. |

---

# Phụ lục B — Adjustment types

Giá trị của `transactions.type` (Get Transactions by Statement 202501). `ORDER` = giao dịch đơn; `RESERVE` = giữ/release reserve; còn lại là các loại **điều chỉnh**:

**Platform‑related:** `CHARGE_BACK`, `CUSTOMER_SERVICE_COMPENSATION`, `DEDUCTIONS_INCURRED_BY_SELLER`, `GMV_PAYMENT_FOR_ADS`, `PLATFORM_COMMISSION_ADJUSTMENT`, `PLATFORM_COMMISSION_COMPENSATION`, `PLATFORM_PENALTY`, `PROMOTION_ADJUSTMENT`, `REBATE`, `PLATFORM_COMPENSATION`, `PLATFORM_REIMBURSEMENT`, `COFUNDED_CREATOR_REWARDS`, `STAMP_DUTY`.
**Logistics‑related:** `FBT_WAREHOUSE_SERVICE_FEE`, `LOGISTICS_REIMBURSEMENT`, `SHIPPING_FEE_ADJUSTMENT`, `SHIPPING_FEE_COMPENSATION`, `SHIPPING_FEE_REBATE`, `SAMPLE_SHIPPING_FEE`, `SELLER_MISSION_REWARD`.
**Số dư âm / vi phạm:** `Violation fee (settlement fee)`, `Violation fee (credit card)`, `Bill payment (negative balance)`, `Sales proceed (negative balance)`, `Bill deduction for negative balance`.
**Khác:** `OTHER_ADJUSTMENT`.

**Bảng ý nghĩa (theo overview):**
| Loại | Mô tả |
|---|---|
| Shipping fee adjustment | Điều chỉnh khi có sai lệch phí ship seller đã trả. |
| Shipping fee compensation | Bù khi phí ship thực khác phí trả trước. |
| Chargeback | Khoản hoàn về thẻ khi khách dispute thành công. |
| Customer service compensation | Bù thêm cho khách sau hạn after‑sales bởi CSKH. |
| Promotion adjustment | Điều chỉnh chênh lệch giá khuyến mãi và số tiền seller thực trả. |
| Platform compensation | Bù cho seller khi seller appeal dispute thành công. |
| Platform penalty | Phạt do vi phạm chính sách (đã trừ vào tài khoản; xem email thông báo). |
| Sample shipping fee | Phí gửi mẫu qua NCC logistics nền tảng. |
| Logistics reimbursement | Bù do mất/hỏng hàng vì lỗi logistics. |
| Platform reimbursement | Hoàn không cần trả hàng (lỗi không thuộc seller) / trợ giá do khách không hài lòng. |
| Deductions incurred by seller | Khoản seller chịu (phí ship trả hàng do lỗi sản phẩm/giao trễ, hàng giả, gói rỗng, sai mô tả, hàng lỗi, voucher đền bù…). |
| Shipping fee rebate | Hoàn phí ship cho seller khi tham gia campaign. |
| Warehouse service fee | Phí dịch vụ kho (đóng gói, dán barcode, kiểm hàng mới…). |
| Platform commission adjustment / compensation | Điều chỉnh / bù chênh lệch hoa hồng nền tảng. |
| Other adjustment | Điều chỉnh lý do khác. |

---

## Tham chiếu chéo SDK (repo)
| API (tài liệu) | SDK method | Model response |
|---|---|---|
| Get Statements 202309 | `FinanceV202309Api.StatementsGet` | `Finance202309GetStatementsResponse` |
| Get Payments 202309 | `FinanceV202309Api.PaymentsGet` | `Finance202309GetPaymentsResponse` |
| Get Withdrawals 202309 | `FinanceV202309Api.WithdrawalsGet` | `Finance202309GetWithdrawalsResponse` |
| Get Transactions by Statement 202501 | `FinanceV202501Api.StatementsStatementIdStatementTransactionsGet` | `Finance202501GetTransactionsbyStatementResponse` |
| Get Transactions by Order 202501 | `FinanceV202501Api.OrdersOrderIdStatementTransactionsGet` | `Finance202501GetTransactionsbyOrderResponse` |
| Get Unsettled Transactions 202507 | `FinanceV202507Api.OrdersUnsettledGet` | `Finance202507GetUnsettledTransactionsResponse` |

> Khi TikTok cập nhật doc: chạy lại trình duyệt với extractor trong lịch sử, ghi đè `_raw_scraped/`, rồi đối chiếu với SDK (`sdk_tiktok_seller/model/finance/`). Toàn bộ field list đầy đủ (kể cả 202309 schema phẳng & các field 202507 rút gọn) nằm ở `_raw_scraped/tiktok_finance_models.md`.
