# API Reference

> Nguồn: các file route thực tế (`app/routes/*.php`, per-module `Http/routes.php`) đối chiếu `docs/05-api/*`. **Khi doc và code lệch, code thắng** (đã ghi chú). Mỗi endpoint: Method · Path · Mục đích · Quyền · Khi nào gọi.

---

## 1. Quy ước API

| Khía cạnh | Quy tắc |
|---|---|
| **Base path** | `/api/v1/...` (versioned). Resource số nhiều kebab-case; hành động phi-CRUD = sub-resource động từ (`POST /orders/{id}/ship`). |
| **Xác thực** | Sanctum **cookie SPA**. Gọi `GET /sanctum/csrf-cookie` trước, rồi gửi cookie + `X-XSRF-TOKEN`. Endpoint nghiệp vụ cần `auth:sanctum` + `verified` + `tenant`. |
| **Tenant** | Header `X-Tenant-Id: <id>`. Thiếu → `400 TENANT_REQUIRED`; không phải thành viên → `403 TENANT_FORBIDDEN`. **Admin API (`/api/v1/admin/*`) dùng guard `admin_web` riêng, KHÔNG cần `X-Tenant-Id`.** |
| **Envelope thành công** | `{ "data": {...} }` hoặc `{ "data": [...], "meta": { "pagination": {...} } }`. |
| **Envelope lỗi** | `{ "error": { "code", "message", "details", "trace_id" } }`. |
| **HTTP status** | 200/201/204 · 400 validate/tenant · 401 · 403 · 404 · 409 conflict/idempotency · 422 validate chi tiet · 429 rate limit · 5xx (có `trace_id`). |
| **Tiền** | Số nguyên VND + `"currency": "VND"`. |
| **Thời gian** | ISO-8601 UTC. |
| **Trạng thái** | `status` (mã chuẩn) + `status_label` (VN) + `raw_status` (gốc sàn). |
| **Phân trang** | Mặc định `?page=1&per_page=20` (max 100; vài list 200); list nặng dùng cursor (`next_cursor`). |
| **Lọc/sắp xếp** | Query param; CSV đa giá trị (`status=processing,ready_to_ship`); `?sort=-placed_at`. |
| **Idempotency/bulk** | Header `Idempotency-Key`; bulk trả per-item `{succeeded[], failed[]}` — 1 lỗi không hỏng cả batch. |
| **Rate limit** | API chung 120/phút/user; login/register 15/phút; admin login 10/phút/IP; `POST /orders` 30/phút; messaging send 30/phút; AI suggestion 20/phút/tenant; billing checkout 10/phút. |
| **Gating gói** | Middleware `plan.limit:<resource>`, `plan.feature:<feature>`, `plan.over_quota_lock` → `402 PLAN_LIMIT_REACHED` / `402 PLAN_FEATURE_LOCKED` / `402 PLAN_QUOTA_EXCEEDED`. |

**Mã lỗi tiêu biểu**: `VALIDATION_FAILED`, `TENANT_REQUIRED`, `TENANT_FORBIDDEN`, `TENANT_SUSPENDED`, `EMAIL_NOT_VERIFIED`, `INVALID_CREDENTIALS`, `SKU_CODE_TAKEN`, `DUPLICATE_SKU`, `PLAN_LIMIT_REACHED`, `PLAN_FEATURE_LOCKED`, `PLAN_QUOTA_EXCEEDED`, `DOWNGRADE_NOT_ALLOWED`, `ALREADY_ON_PLAN`, `OUTBOUND_WINDOW_CLOSED`, `CONVERSATION_CLOSED`, `CHANNEL_ACCOUNT_INACTIVE`, `ATTACHMENT_INVALID`, `AI_PROVIDER_NOT_AVAILABLE`, `PERIOD_CLOSED`, `ACCOUNTING_UNBALANCED`, `ACCOUNTING_ACCOUNT_IN_USE`, voucher codes (`INVALID_VOUCHER`, `VOUCHER_EXPIRED`...).

---

## 2. Hệ thống & Auth

| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/api/v1/health` | Probe DB/cache/Redis/queue | công khai |
| POST | `/api/v1/auth/register` | Tạo user + tenant mới (owner), login, gửi email xác thực | công khai |
| POST | `/api/v1/auth/login` | Đăng nhập | công khai |
| POST | `/api/v1/auth/logout` | Đăng xuất | sanctum |
| GET | `/api/v1/auth/me` | User hiện tại + tenants[] | sanctum |
| PATCH | `/api/v1/auth/profile` | Sửa tên/email/mật khẩu (đổi nhạy cảm cần `current_password`) | sanctum |
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | Xác thực email (signed URL) | link |
| POST | `/api/v1/auth/email/verify/resend` | Gửi lại email xác thực | sanctum |
| POST | `/api/v1/auth/password/forgot` | Gửi link reset | công khai |
| POST | `/api/v1/auth/password/reset` | Đặt lại mật khẩu | công khai |

### Tenant / Workspace
| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET/POST | `/api/v1/tenants` | List / tạo workspace | sanctum |
| GET/PATCH | `/api/v1/tenant` | Xem / sửa workspace (tên/slug/`platform_fee_pct`/khổ in) | `tenant.settings` |
| GET/POST | `/api/v1/tenant/members` | List / thêm thành viên | owner/admin |
| POST | `/api/v1/media/image` | Upload ảnh chung | `orders.create`/`products.manage` |

---

## 3. Channels (Gian hàng) & Nhật ký đồng bộ

| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/api/v1/channel-accounts` | List gian hàng + provider có thể kết nối | `channels.view` |
| GET | `/api/v1/channel-accounts/outbound-ip` | IP máy chủ (whitelist Lazada) | `channels.view` |
| POST | `/api/v1/channel-accounts/{provider}/connect` | Bắt đầu OAuth → `{auth_url}` (`tiktok/shopee/lazada`) | `channels.manage` + `plan.limit:channel_accounts` |
| PATCH | `/api/v1/channel-accounts/{id}` | Đổi tên hiển thị | `channels.manage` |
| PATCH | `/api/v1/channel-accounts/{id}/messaging` | Bật/tắt tin nhắn cho shop | `channels.manage` |
| PATCH | `/api/v1/channel-accounts/{id}/auto-rts` | Bật/tắt auto-RTS sau in (chỉ Lazada) | `channels.manage` |
| DELETE | `/api/v1/channel-accounts/{id}` | Xoá kết nối + toàn bộ đơn của shop (body `confirm`=tên shop) | `channels.manage` |
| POST | `/api/v1/channel-accounts/{id}/resync` | Đồng bộ lại đơn | `channels.manage` |
| POST | `/api/v1/channel-accounts/{id}/resync-unprocessed` | Kéo lại đơn chưa xử lý | `channels.manage` |
| POST | `/api/v1/channel-accounts/{id}/resync-listings` | Kéo listing + tự khớp SKU | `channels.manage` |
| POST | `/api/v1/channel-accounts/{id}/resync-chat` | Kéo lại hội thoại | `channels.manage` |
| POST | `/api/v1/channel-accounts/{id}/fetch-settlements` | Kéo đối soát | `finance.reconcile` + `plan.feature:finance_settlements` |
| GET | `/api/v1/sync-runs` | List lần đồng bộ | `channels.view` |
| POST | `/api/v1/sync-runs/{id}/redrive` | Chạy lại lần đồng bộ | `channels.manage` |
| GET | `/api/v1/webhook-events` | List webhook events | `channels.view` |
| POST | `/api/v1/webhook-events/{id}/redrive` | Xử lý lại webhook | `channels.manage` |

---

## 4. Orders & Returns

| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/api/v1/orders` | List đơn (lọc status/source/carrier/sku/stage/slip/out_of_stock…) | `orders.view` |
| GET | `/api/v1/orders/stats` | Đếm theo facet (tab/filter) | `orders.view` |
| POST | `/api/v1/orders/sync` | Đồng bộ mọi shop active | `orders.view` |
| GET | `/api/v1/orders/{id}` | Chi tiết đơn + items + lịch sử | `orders.view` |
| POST | `/api/v1/orders` | Tạo đơn thủ công (đặt giữ tồn) | `orders.create` (30/phút) |
| PATCH | `/api/v1/orders/{id}` | Sửa đơn thủ công (trước ship) | `orders.update` |
| POST | `/api/v1/orders/{id}/cancel` | Huỷ đơn thủ công (nhả tồn) | `orders.update` |
| POST | `/api/v1/orders/{id}/tags` | Thêm/bỏ tag | `orders.update` |
| PATCH | `/api/v1/orders/{id}/note` | Đặt ghi chú đơn | `orders.update` |
| GET | `/api/v1/returns/stats` | Đếm hoàn/hủy | `orders.view` |
| GET | `/api/v1/returns` | List hoàn/hủy | `orders.view` |
| GET | `/api/v1/returns/{id}` | Chi tiết hoàn/hủy | `orders.view` |
| POST | `/api/v1/returns/{id}/approve` | Duyệt hoàn | `orders.update` |
| POST | `/api/v1/returns/{id}/reject` | Từ chối hoàn | `orders.update` |

> Lưu ý: nhóm `/returns/*` đã có trong code (SPEC-0025) nhưng chưa có trong doc cũ.

---

## 5. Products / Listings / Inventory / SKU / Warehouse

| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET/POST | `/api/v1/products` | List / tạo sản phẩm | `products.view`/`products.manage` |
| GET/PATCH/DELETE | `/api/v1/products/{id}` | Chi tiết / sửa / xoá mềm | `products.view`/`products.manage` |
| GET | `/api/v1/channel-listings` | List listing sàn + trạng thái ghép | `products.view` |
| POST | `/api/v1/channel-listings/sync` | Kéo listing mọi shop | `inventory.map` |
| PATCH | `/api/v1/channel-listings/{id}` | Ghim/bỏ ghim đẩy tồn | `inventory.map` |
| GET/POST | `/api/v1/skus` | List / tạo SKU (+mapping +tồn mở) | `inventory.view`/`products.manage` |
| GET/PATCH/DELETE | `/api/v1/skus/{id}` | Chi tiết / sửa / xoá (409 nếu còn tồn) | `inventory.view`/`products.manage` |
| POST/DELETE | `/api/v1/skus/{id}/image` | Upload / xoá ảnh SKU | `products.manage` |
| GET/POST | `/api/v1/warehouses` | List / tạo kho | `inventory.view`/`inventory.adjust` |
| PATCH | `/api/v1/warehouses/{id}` | Sửa kho | `inventory.adjust` |
| GET | `/api/v1/inventory/levels` | Tồn theo SKU/kho | `inventory.view` |
| POST | `/api/v1/inventory/adjust` | Điều chỉnh tồn 1 dòng | `inventory.adjust` |
| POST | `/api/v1/inventory/bulk-adjust` | Nhập/điều chỉnh hàng loạt (≤500) | `inventory.adjust` |
| POST | `/api/v1/inventory/push-stock` | Đẩy tồn lên sàn (theo SKU ids) | `inventory.map` |
| GET | `/api/v1/inventory/movements` | Sổ cái tồn | `inventory.view` |
| GET/POST | `/api/v1/warehouse-docs/{type}` | List / tạo phiếu kho (`goods-receipts`/`stock-transfers`/`stocktakes`) | `inventory.view` / write theo type |
| GET | `/api/v1/warehouse-docs/{type}/{id}` | Chi tiết phiếu | `inventory.view` |
| POST | `/api/v1/warehouse-docs/{type}/{id}/confirm` | Ghi phiếu vào sổ | write theo type |
| POST | `/api/v1/warehouse-docs/{type}/{id}/cancel` | Huỷ phiếu nháp | write theo type |

### Ghép SKU
| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| POST | `/api/v1/sku-mappings` | Ghép listing ↔ SKU | `inventory.map` |
| POST | `/api/v1/sku-mappings/auto-match` | Tự khớp theo sku_code | `inventory.map` |
| DELETE | `/api/v1/sku-mappings/{id}` | Bỏ ghép (+ đẩy lại tồn) | `inventory.map` |
| GET | `/api/v1/orders/unmapped-skus` | SKU chưa ghép từ đơn + gợi ý | `orders.view` + `inventory.map` |
| POST | `/api/v1/orders/link-skus` | Ghép SKU từ đơn + resolve lại đơn | `inventory.map` |

---

## 6. Customers

| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/api/v1/customers` | List khách (uy tín, SĐT che) | `customers.view` |
| GET | `/api/v1/customers/lookup` | Tra nhanh theo SĐT (form tạo đơn) | `customers.view` (20/phút) |
| GET | `/api/v1/customers/{id}` | Chi tiết + ghi chú | `customers.view` |
| GET | `/api/v1/customers/{id}/orders` | Đơn của khách | `customers.view`+`orders.view` |
| POST/DELETE | `/api/v1/customers/{id}/notes[/{noteId}]` | Thêm / xoá ghi chú | `customers.note` |
| POST | `/api/v1/customers/{id}/block` · `/unblock` | Chặn / bỏ chặn | `customers.block` |
| POST | `/api/v1/customers/{id}/tags` | Gắn/bỏ tag | `customers.note` |
| POST | `/api/v1/customers/merge` | Gộp 2 hồ sơ khách | `customers.merge` |

---

## 7. Fulfillment / Shipments / Print

| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/api/v1/carriers` | ĐVVC + năng lực | `fulfillment.view` |
| GET | `/api/v1/master-data/{provinces,districts,wards}` | Địa giới VN (AddressPicker) | sanctum+tenant |
| GET/POST/PATCH/DELETE | `/api/v1/carrier-accounts[/{id}]` | Quản lý tài khoản ĐVVC (key che) | `fulfillment.view`/`fulfillment.carriers` |
| POST | `/api/v1/carrier-accounts/{id}/verify` | Xác minh credential | `fulfillment.carriers` |
| POST | `/api/v1/carrier-accounts/ghn/{master-data,shops}` | Proxy GHN địa giới / shop | `fulfillment.carriers` |
| GET | `/api/v1/fulfillment/ready` · `/processing` · `/processing/counts` | Đơn theo chặng + đếm badge | `fulfillment.view` |
| POST | `/api/v1/orders/{id}/ship` | "Chuẩn bị hàng": tạo shipment + đẩy sàn + lấy AWB/tem | `fulfillment.ship` |
| GET | `/api/v1/shipments` | List vận đơn | `fulfillment.view` |
| POST | `/api/v1/shipments/bulk-create` | Tạo vận đơn hàng loạt | `fulfillment.ship` |
| POST | `/api/v1/shipments/pack` | Đóng gói hàng loạt (created→packed, đơn→ready_to_ship) | `fulfillment.scan`/`ship` |
| POST | `/api/v1/shipments/handover` | Bàn giao hàng loạt (→picked_up, đơn→shipped, trừ tồn) | `fulfillment.ship` |
| POST | `/api/v1/shipments/bulk-refetch-slip` | "Nhận phiếu giao hàng lại" | `fulfillment.ship` |
| GET/POST | `/api/v1/shipments/{id}[/track][/cancel]` | Chi tiết / refresh tracking / huỷ | `fulfillment.view`/`ship` |
| GET | `/api/v1/shipments/{id}/label` | Redirect PDF tem (302) | `fulfillment.print` |
| POST | `/api/v1/scan-pack` · `/scan-handover` | Quét mã → gói / bàn giao | `fulfillment.scan`/`ship` |
| GET/POST | `/api/v1/print-jobs[/{id}]` | List / tạo / xem job in | `fulfillment.print` |
| POST | `/api/v1/print-jobs/{id}/mark-printed` | Đánh dấu đã in (+số bản) | `fulfillment.print` |
| GET | `/api/v1/print-jobs/{id}/download` | Redirect PDF (302) | `fulfillment.print` |
| GET/POST/PUT/DELETE | `/api/v1/shipping-label-templates[/{id}]` | Mẫu phiếu (CRUD + preview + duplicate + set-default) | fulfillment perm |
| GET | `/api/v1/dashboard/summary` | Tổng quan Dashboard | `dashboard.view` |

---

## 8. Procurement · Reports · Finance

### Procurement (gói `procurement`)
| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET/POST | `/api/v1/suppliers` | List / tạo NCC | `procurement.view`/`procurement.manage` |
| GET/PATCH/DELETE | `/api/v1/suppliers/{id}` | Chi tiết / sửa / xoá | `procurement.view`/`procurement.manage` |
| POST/DELETE | `/api/v1/suppliers/{id}/prices[/{priceId}]` | Set / xoá giá nhập SKU | `procurement.manage` |
| GET/POST | `/api/v1/purchase-orders` | List / tạo PO nháp | `procurement.view`/`procurement.manage` |
| GET/PATCH | `/api/v1/purchase-orders/{id}` | Chi tiết / sửa nháp | `procurement.view`/`procurement.manage` |
| POST | `/api/v1/purchase-orders/{id}/confirm` · `/cancel` · `/receive` | Xác nhận / huỷ / nhận hàng | `procurement.manage`/`procurement.receive` |
| GET | `/api/v1/procurement/demand-planning` | Đề xuất nhập | `procurement.view` + `plan.feature:demand_planning` |
| POST | `/api/v1/procurement/demand-planning/create-po` | Tạo PO từ đề xuất (1/NCC) | `procurement.manage` + `demand_planning` |

> Lưu ý path: NCC/PO là `/api/v1/suppliers*` & `/api/v1/purchase-orders*` (chỉ demand-planning có prefix `procurement/`).

### Reports
| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/api/v1/reports/revenue` | Doanh thu theo thời gian (mọi gói) | `reports.view` |
| GET | `/api/v1/reports/profit` | Lợi nhuận (COGS/phí) | `reports.view` + `profit_reports` |
| GET | `/api/v1/reports/top-products` | Top sản phẩm | `reports.view` + `profit_reports` |
| GET | `/api/v1/reports/export` | Export CSV (UTF-8 BOM) | `reports.export` + `profit_reports` |

### Finance (gói `finance_settlements`)
| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/api/v1/settlements[/{id}]` | List / chi tiết đối soát + lines | `finance.view` |
| POST | `/api/v1/settlements/{id}/reconcile` | Đối soát (khớp đơn) | `finance.reconcile` |
| POST | `/api/v1/channel-accounts/{id}/fetch-settlements` | Kéo đối soát cho shop | `finance.reconcile` |

> Lưu ý path: Finance là `/api/v1/settlements*` + `channel-accounts/{id}/fetch-settlements` (không phải `/finance/settlements*`).

---

## 9. Accounting (gói `accounting_basic`; nâng cao = `accounting_advanced`)

Prefix `/api/v1/accounting`. Đáng chú ý (~45 endpoint):

| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/dashboard-summary` · `/setup/status` | Tổng quan / trạng thái khởi tạo | `accounting.view` |
| POST | `/setup` | Khởi tạo (idempotent) | `accounting.config` |
| GET/POST/PATCH/DELETE | `/accounts[/{id}]` | Hệ thống TK (CoA) | view / `accounting.config` |
| GET | `/periods` · POST `/periods/ensure-year` | Kỳ kế toán | view / `accounting.config` |
| POST | `/periods/{code}/close` · `/reopen` · `/lock` | Đóng / mở / khoá kỳ | `accounting.close_period` |
| GET/POST | `/journals[/{id}]` | Bút toán (tạo tay 30/phút) | view / `accounting.post` |
| POST | `/journals/{id}/reverse` | Đảo bút toán | `accounting.post` |
| GET/POST | `/balances` · `/balances/recompute` | Số dư / tính lại | view / `accounting.config` |
| GET/PATCH | `/post-rules[/{eventKey}]` | Quy tắc tự định khoản | view / `accounting.config` |
| GET | `/ar/aging` · `/ar/customers/{id}/balance` | AR aging / số dư khách | `accounting.view` |
| GET/POST | `/customer-receipts[...]` | Phiếu thu khách (+confirm/cancel) | view / `accounting.post` |
| GET | `/ap/aging` | AP aging | `accounting.view` |
| GET/POST | `/vendor-bills[...]` · `/vendor-payments[...]` | Hoá đơn NCC / phiếu chi | view / `accounting.post` |
| GET/POST | `/cash-accounts` | Quỹ/ngân hàng | view / `accounting.config` |
| GET/POST | `/bank-statements[...]` · `/bank-statement-lines/{id}/{match,ignore}` | Sao kê + matching | `accounting.view`/`post` + `accounting_advanced` |
| GET | `/reports/{trial-balance,profit-loss,balance-sheet,ledger}` | BCTC | `accounting.view` |
| GET | `/reports/vat` · POST `/tax-filings` | VAT / tờ khai | + `accounting_advanced` |
| GET | `/reports/export-misa` | Export MISA | `accounting.export` + `accounting_advanced` |

---

## 10. Billing

Prefix `/api/v1/billing`.

| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/plans` | Danh mục gói | `billing.view` |
| GET | `/subscription` · `/usage` | Gói hiện tại + hạn mức | `billing.view` |
| POST | `/checkout` | Bắt đầu thanh toán (sepay/vnpay; `voucher_code?`) | `billing.manage` (owner, 10/phút) |
| POST | `/vouchers/validate` | Xem trước giảm giá voucher | `billing.manage` (30/phút) |
| POST | `/subscription/cancel` | Huỷ cuối kỳ | `billing.manage` (owner) |
| GET | `/invoices[/{id}]` | List / chi tiết hoá đơn | `billing.view` |
| GET | `/invoices/{id}/payment-status` | Poll trạng thái thanh toán (SePay) | `billing.view` |
| GET/PATCH | `/billing-profile` | Hồ sơ xuất hoá đơn | view / `billing.manage` |

---

## 11. Messaging (prefix `/api/v1/messaging`, gói `messaging_inbox`)

**Hội thoại**
| Method | Path | Mục đích | Quyền |
|---|---|---|---|
| GET | `/conversations` | List (lọc provider/status/unread/assigned/customer/q) | `messaging.view` |
| GET | `/conversations/{id}` | Chi tiết + 50 tin gần (cursor); Post Card cho comment | `messaging.view` |
| POST | `/conversations/{id}/read` · `/unread` | Đánh dấu đọc/chưa | `messaging.view` |
| POST/DELETE | `/conversations/{id}/block` | Chặn / bỏ chặn | `messaging.reply` |
| PATCH | `/conversations/{id}` | Cập nhật status/assignee/tags/snooze | `messaging.view`/`assign` |
| POST | `/conversations/{id}/link-order` | Gắn đơn vừa tạo vào hội thoại | `messaging.reply` |

**Gửi tin** (30/phút)
| POST | `/conversations/{id}/messages` · `/messages/template` · `/messages/media` | Gửi text / mẫu / media | `messaging.reply` |

**AI gợi ý** (+ gói `messaging_ai`, 20/phút/tenant)
| POST | `/conversations/{id}/ai-suggestion` | Sinh nháp AI | `messaging.reply` |
| POST | `/conversations/{id}/ai-suggestion/{draftId}/accept` | Gửi nháp | `messaging.reply` |
| DELETE | `/conversations/{id}/ai-suggestion/{draftId}` | Từ chối nháp | `messaging.reply` |

**Bình luận Facebook**
| POST | `/conversations/{id}/comment/hide` | Ẩn/hiện luồng | `messaging.reply` |
| DELETE | `/conversations/{id}/comment` | Xoá comment (gốc hoặc `comment_id`) | `messaging.reply` |
| POST | `/conversations/{id}/comment/reply` | Trả lời công khai | `messaging.reply` |
| POST | `/conversations/{id}/comment/private-reply` | Nhắn riêng 1-lần | `messaging.reply` |
| POST | `/conversations/{id}/comment/like` | Page thích/bỏ thích | `messaging.reply` |
| POST | `/conversations/{id}/comment/private-message` | Modal nhắn riêng đầy đủ (text+đính kèm) | `messaging.reply` |

**Mẫu tin / Kiến thức / Cài đặt / Thẻ / Kênh / Flow**
| GET/POST/PATCH/DELETE | `/templates[/{id}]` | Mẫu tin | view / `template.manage` |
| GET/POST/DELETE | `/knowledge-docs[/{id}]` · `/reindex` · `/chunks` | Tài liệu RAG | view / `ai.train` |
| GET/PATCH | `/settings` | Cấu hình AI/away hours | `messaging.ai.config` |
| GET/POST/PATCH/DELETE | `/tags[/{id}]` | Thẻ hội thoại | view / `messaging.reply` |
| GET | `/capabilities` · `/channels` · `/channels/{id}/posts` | Năng lực / kênh / bài viết | `messaging.view` |
| POST/DELETE | `/channels/{id}/sync` · `/channels/{id}` | Sync / xoá kênh | `messaging.connect` |
| POST | `/facebook/connect` | OAuth Facebook Page | `messaging.connect` |
| GET/POST/PATCH/DELETE | `/auto-reply-rules[/{id}]` | Quy tắc auto-reply | view / `rule.manage` |
| GET/POST/PATCH/DELETE | `/automation-flows[/{id}]` + `/validate` `/publish` `/pause` `/duplicate` `/media` | Kịch bản (Flow Builder) | view / `rule.manage` |
| GET/POST/DELETE | `/push/{public-key,subscribe,heartbeat}` | Web Push tin mới | `messaging.view` |

---

## 12. Admin API (`/api/v1/admin/*`, guard `admin_web`, 60/phút)

| Nhóm | Endpoint tiêu biểu |
|---|---|
| Auth | `POST /admin/auth/login` (10/phút/IP), `/logout`, `GET /me`, `POST /change-password` |
| Admin users | `GET/POST /admin/admin-users`, `{id}` GET/PATCH, `/reset-password`, `/suspend`, `/reactivate` |
| Tenant users | `GET /admin/users`, `{id}` GET/PATCH, `/reset-password`, `/suspend`, `/reactivate` |
| Tenants | `GET /admin/tenants[/{id}]`, `DELETE .../channel-accounts/{caid}`, `POST .../subscription`, `/suspend`, `/reactivate`, `/extend-trial`, `/feature-overrides`, `/invoices` |
| Invoices/Payments | `POST /admin/invoices/{id}/mark-paid`, `POST /admin/payments/{id}/refund` |
| Vouchers | `GET/POST /admin/vouchers[/{id}]`, `/grant` |
| Plans | `GET/POST /admin/plans[/{id}]` (code+currency bất biến) |
| Audit/Broadcast | `GET /admin/audit-logs`, `GET/POST /admin/broadcasts[/{id}]` (≤5000 recipient) |
| System settings | `GET /admin/system-settings?group=`, `/sync-from-env`, `/{key}/reveal`, PATCH/DELETE `/{key}` |
| AI providers | `GET/POST /admin/ai-providers[/{code}]`, `/{code}/test` |

---

## 13. Webhooks & OAuth (ngoài `/api`, verify chữ ký, không auth/CSRF)

| Method | Path | Mục đích |
|---|---|---|
| POST | `/webhook/tiktok` · `/lazada` · `/shopee` | Push đơn (Shopee gồm cả chat code 10) |
| POST | `/webhook/payments/{gateway}` | IPN thanh toán (`sepay`/`vnpay`/`momo`) |
| POST | `/webhook/carriers/{carrier}` | Push trạng thái ĐVVC (`ghn`/`ghtk`/`jt`/`viettelpost`) |
| POST | `/webhook/messaging/{provider}` | Push tin nhắn (`manual`/`facebook_page`/`tiktok_chat`/`lazada_chat`) |
| GET | `/webhook/messaging/facebook` | Challenge `hub.verify_token` của Meta |
| GET | `/oauth/{provider}/callback` | Callback OAuth sàn → tạo gian hàng → redirect SPA |
| GET | `/oauth/facebook_page/callback` | Callback OAuth Facebook Page |
| GET | `/sanctum/csrf-cookie` | Lấy CSRF cookie (gọi trước request mutate) |

> **Khác biệt doc vs code đã biết**: doc cũ thiếu nhóm Returns, toàn bộ Accounting, và đánh dấu Messaging "Draft chưa code" (thực tế đã code đầy đủ). Một số path doc ghi sai prefix (Finance/Procurement). Bảng trên theo route thực tế.
