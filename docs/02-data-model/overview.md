# Mô hình dữ liệu — Quy ước & Danh mục bảng

**Status:** Draft (sẽ chi tiết hoá theo từng phase) · **Cập nhật:** 2026-05-23

> File này giữ **quy ước chung** + **danh mục bảng theo module** ở mức "biết bảng nào ở đâu, vai trò gì". Schema cột chi tiết viết trong migration + bổ sung mô tả ở đây hoặc file con `02-data-model/<module>.md` khi cần.

## 1. Quy ước (RULES)

1. **`tenant_id`** ở mọi bảng nghiệp vụ (NOT NULL, có index; thường đứng đầu các index phức hợp).
2. **Khoá chính** `bigint identity` (hoặc UUID v7 nếu cần lộ ra ngoài) — chốt ở Phase 0, ghi ADR. ID lộ ra API có thể dùng dạng hashid/UUID, không lộ số tự tăng.
3. **`created_at`, `updated_at`** mọi bảng; **`deleted_at`** (soft delete) cho bảng người dùng có thể xoá nhầm (sản phẩm, SKU, đơn manual...); bảng log/sổ cái **không** soft delete (bất biến).
4. **Tiền tệ:** chỉ VND. Lưu số tiền dạng `bigint` đơn vị **đồng** (không dùng float). Có cột `currency` = `'VND'` để chừa đường mở rộng.
5. **Thời gian:** lưu UTC (`timestamptz`). Hiển thị theo `Asia/Ho_Chi_Minh`. Lưu kèm thời gian gốc từ sàn nếu khác.
6. **Trạng thái:** lưu **mã chuẩn** (`status`) + **chuỗi gốc** (`raw_status`). Mã chuẩn là enum trong code (string trong DB), không phải số ma thuật.
7. **Payload thô từ sàn:** cột `raw_payload jsonb` ở bảng `orders`, `webhook_events`, `settlement_lines`... để debug & tái xử lý. Index GIN nếu cần truy vấn.
8. **Idempotency:** mọi bảng nhận dữ liệu ngoài có **unique constraint** chống trùng (vd `orders(source, channel_account_id, external_order_id)`; `webhook_events(provider, external_id, event_type)`).
9. **Partition theo tháng (RANGE trên `created_at` hoặc cột thời gian nghiệp vụ):** `orders`, `order_items`, `order_status_history`, `inventory_movements`, `webhook_events`, `sync_runs`, `settlement_lines`, (sau) `messages`. Job định kỳ tạo partition tháng kế; archive/drop partition cũ theo chính sách lưu trữ.
10. **Tiền & số lượng tồn** thay đổi → luôn đi kèm 1 dòng trong sổ cái tương ứng (`inventory_movements`, `order_costs`...) — không bao giờ "ghi đè im lặng".
11. **Không cascade delete** xuyên module quan trọng — dùng soft delete + job dọn dẹp có kiểm soát.

## 2. Danh mục bảng theo module

### Tenancy
`tenants` · `users` · `tenant_user` (role, scope) · `roles` / `permissions` (nếu dùng lib team-mode) · `audit_logs` · `invitations`

### Channels
`channel_accounts` (tenant_id, provider, external_shop_id, shop_name, status, access_token🔒, refresh_token🔒, token_expires_at, refresh_expires_at, last_synced_at, meta jsonb) · `oauth_states` (state, provider, tenant_id, expires_at) · `webhook_events` (provider, event_type, external_id, signature, payload jsonb, received_at, processed_at, status, attempts, error) · `sync_runs` (channel_account_id, type, started_at, finished_at, status, stats jsonb, cursor)

### Orders
`orders` (tenant_id, source, channel_account_id?, external_order_id?, order_number, status, raw_status, payment_status, buyer_name, buyer_phone🔒, shipping_address jsonb, currency, item_total, shipping_fee, platform_discount, seller_discount, tax, grand_total, cod_amount, placed_at, paid_at, shipped_at, delivered_at, completed_at, cancelled_at, cancel_reason, note, tags jsonb, raw_payload jsonb, source_updated_at, **customer_id?** *(Phase 2 — FK `customers.id`, ON DELETE SET NULL; index `(tenant_id, customer_id, placed_at DESC)`)*) · `order_items` (order_id, channel_listing_id?, sku_id?, name, sku_code, variation, quantity, unit_price, discount, subtotal, image) · `order_status_history` (order_id, from_status, to_status, raw_status, source, changed_at, payload jsonb)

### Customers *(Phase 2 — SPEC-0002)*
`customers` (tenant_id, **phone_hash🔑uniq/tenant** `char(64)` = sha256(normalized_phone), phone🔒, name?, email🔒?, email_hash?, addresses_meta jsonb [tối đa 5 địa chỉ distinct gần nhất], lifetime_stats jsonb {orders_total, orders_completed, orders_cancelled, orders_returned, orders_delivery_failed, orders_in_progress, revenue_completed, last_order_id, computed_at}, reputation_score smallint [0..100, default 100], reputation_label varchar(16) [`ok|watch|risk|blocked`, denormalized], tags jsonb, is_blocked, blocked_at?, blocked_by_user_id?, block_reason?, manual_note?, first_seen_at, last_seen_at, merged_into_customer_id?[FK self], pii_anonymized_at?, deleted_at? [soft delete]; index `(tenant_id, last_seen_at DESC)`, `(tenant_id, reputation_label)`, `(tenant_id, is_blocked)`, GIN `(tenant_id, tags jsonb_path_ops)`) · `customer_notes` (tenant_id, customer_id, author_user_id?, kind [`manual|auto.cancel_streak|auto.return_streak|auto.delivery_failed|auto.vip|system.merge`], severity [`info|warning|danger`], note, order_id?, dedupe_key? [unique `(customer_id, dedupe_key)` cho auto-note], created_at) — **append-only, không soft delete**.

### Inventory
`skus` (tenant_id, product_id, sku_code🔑uniq/tenant, barcode, name, cost_price, **cost_method** [`average|latest|fifo`, default `average`], **last_receipt_cost** bigint?, **safety_stock** int, attributes jsonb) · `warehouses` (tenant_id, name, code, address jsonb, is_default) · `inventory_levels` (sku_id, warehouse_id, on_hand, reserved, available_cached, safety_stock) 🔑uniq(sku_id,warehouse_id) · `inventory_movements` (tenant_id, sku_id, warehouse_id, qty_change, type, ref_type, ref_id, balance_after, note, created_by, created_at) · `sku_mappings` (channel_listing_id, sku_id, quantity, type[single|bundle]) · `stock_transfers`(+`stock_transfer_items`) · `stock_takes`(+`stock_take_items`) · `goods_receipts`(+`goods_receipt_items`, **po_id?**[FK Procurement], **supplier_id?**) · **`cost_layers`** *(Phase 6.1 — FIFO)* (tenant_id, sku_id, warehouse_id?, source_type [`goods_receipt|stocktake_in|opening|adjust_in`], source_id, received_at [FIFO key], unit_cost bigint, qty_received int, qty_remaining int, exhausted_at?; index `(tenant_id, sku_id, received_at)`) · **`order_costs`** *(Phase 6.1 — bất biến, không `updated_at`)* (tenant_id, order_id, order_item_id 🔑uniq, sku_id, qty, cogs_unit_avg bigint, cogs_total bigint, cost_method [`fifo|average|latest`], layers_used jsonb [`[{layer_id, qty, unit_cost, synthetic?}]`], shipped_at, created_at)

### Products
`products` (tenant_id, name, image, brand, category, meta jsonb) · `channel_listings` (channel_account_id, external_product_id, external_sku_id, seller_sku, title, price, channel_stock, sync_status, last_pushed_at, meta jsonb) · `listing_drafts`(+ items) · `listing_publish_jobs` · `channel_categories` (provider, external_cat_id, parent_id, name, path) · `channel_attributes` (provider, external_cat_id, attribute schema jsonb)

### Fulfillment
`shipments` (tenant_id, order_id, package_no, carrier, carrier_account_id?, tracking_no, status, weight, dims jsonb, label_url, cod_amount, picked_up_at, delivered_at, raw jsonb) · `pickup_batches`(+ link shipments) · `print_jobs` (tenant_id, type[label|picking|packing|channel_invoice], scope jsonb(order/shipment ids), template_id?, file_url, file_size?, status, error, created_by, **expires_at**, **purged_at?**, meta jsonb) · `order_print_documents` (tenant_id, order_id, print_job_id, type, shipment_id?, created_at) — tra cứu nhanh phiếu in của một đơn để in lại trong 90 ngày · `print_templates` (tenant_id, type, paper_size, layout jsonb, logo_url, is_default) · `carrier_accounts` (tenant_id, carrier, credentials🔒 jsonb, default_service, meta jsonb)

### Procurement *(Phase 6.1 — SPEC-0014)*
`suppliers` (tenant_id, code🔑uniq/tenant, name, phone, email, address, tax_code, payment_terms_days int, note, is_active, created_by, deleted_at; soft delete) · `supplier_prices` (tenant_id, supplier_id, sku_id, unit_cost bigint VND, moq int, currency='VND', valid_from?, valid_to?, is_default bool, note; 🔑uniq `(supplier_id, sku_id, valid_from)`) · `purchase_orders` (tenant_id, code🔑uniq `PO-YYYYMM-NNNN`, supplier_id, warehouse_id [kho đích], status [`draft|confirmed|partially_received|received|cancelled`], expected_at?, note, total_qty, total_cost bigint, created_by, confirmed_at?, cancelled_at?) · `purchase_order_items` (tenant_id, po_id cascade, sku_id, qty_ordered, qty_received [cộng dồn khi receipt confirmed], unit_cost bigint [chốt ở `confirm`], note; 🔑uniq `(po_id, sku_id)`)

> **GoodsReceipts** ↔ **PO**: 1 PO → nhiều `GoodsReceipt` đợt nhận (cột `po_id?`/`supplier_id?` trên `goods_receipts`). Listener `LinkGoodsReceiptToPO` áp `qty_received` khi receipt confirmed → đẩy PO `partially_received` / `received`. Receipt confirmed cũng đẩy 1 layer mới vào `cost_layers` (FIFO).

### Finance *(Phase 6.2 — SPEC-0016)*
`settlements` (tenant_id, channel_account_id, **external_id**🔑uniq `(channel_account_id, external_id)`, period_start, period_end, settled_at?, **total_revenue** bigint, **total_fees** bigint, **total_payout** bigint, status [`pending|reconciled|partial|error`], currency='VND', raw_payload jsonb) · `settlement_lines` (**bất biến** — không `updated_at`) (tenant_id, settlement_id cascade, **fee_type** [`revenue|commission|payment_fee|shipping_fee|shipping_subsidy|voucher_seller|voucher_platform|adjustment|refund|other`], amount bigint [signed], currency='VND', **external_order_id**?, **external_line_id**?, **order_id**? [FK nội bộ sau reconcile], **order_item_id**?, occurred_at, raw jsonb; idempotent dedupe `(settlement_id, fee_type, external_order_id, external_line_id, amount)`) · `profit_snapshots` (tenant_id, dimension[order|sku|channel|day], key, revenue, cogs, fees, profit, period) — *follow-up*

> Lưu ý: `order_costs` (COGS FIFO) thuộc **Inventory** (Phase 6.1), không thuộc Finance — vì gắn chặt với layer tồn và bất biến từ thời điểm ship.

### Billing *(Phase 6.4 — SPEC-0018, đã implement)*
- `plans` (code🔑uniq `trial|starter|pro|business`, name, description, is_active, sort_order, price_monthly bigint VND, price_yearly bigint VND, currency='VND', trial_days, limits jsonb `{max_channel_accounts:int}` (-1 = không giới hạn), features jsonb `{procurement, fifo_cogs, profit_reports, finance_settlements, demand_planning, mass_listing, automation_rules, priority_support}`) — **KHÔNG tenant-scoped** (catalog dùng chung). Seed qua `BillingPlanSeeder` idempotent.
- `subscriptions` (tenant_id, plan_id, status [`trialing|active|past_due|cancelled|expired`] index, billing_cycle [`monthly|yearly|trial`], trial_ends_at?, current_period_start, current_period_end index, cancel_at?, cancelled_at?, ended_at?, meta jsonb) — **partial unique index** `(tenant_id) WHERE status IN ('trialing','active','past_due')` ⇒ 1 alive subscription per tenant (Postgres + SQLite hỗ trợ; MySQL fallback app-level).
- `invoices` (tenant_id, subscription_id, code🔑uniq `INV-YYYYMM-NNNN` per tenant, status [`draft|pending|paid|void|refunded`], period_start/end date, subtotal/tax/total bigint VND, currency='VND', due_at, paid_at?, voided_at?, customer_snapshot jsonb, meta jsonb)
- `invoice_lines` (invoice_id cascade — **KHÔNG có tenant_id** vì đi qua invoice; kind [`plan|addon|discount`], description, quantity, unit_price/amount bigint) — append-only theo invoice.
- `payments` (tenant_id, invoice_id, gateway [`sepay|vnpay|momo|manual`] index, external_ref, amount bigint, status [`pending|succeeded|failed|refunded`] index, raw_payload jsonb (PII-redacted), occurred_at) 🔑uniq `(gateway, external_ref)` — idempotency cho webhook retry; `raw_payload` chỉ giữ metadata không nhạy cảm (transaction_id/bank_code/amount/status/time — không PAN/CVV/full bank-account, PCI scope minimization).
- `usage_counters` (tenant_id, metric [`channel_accounts` — v1 chỉ 1 metric], period char(8) [`current` | `YYYY-MM`], value bigint, last_updated_at) 🔑uniq `(tenant_id, metric, period)` — denormalized counter, real-time count vẫn dùng query trực tiếp `channel_accounts` ở middleware (source of truth).
- `billing_profiles` (tenant_id🔑uniq, company_name?, tax_code?, billing_address?, contact_email?, contact_phone?) — 1-1 với tenant, snapshot vào `invoices.customer_snapshot` lúc tạo invoice.

> Domain events: `Tenancy::TenantCreated` ⇒ `Billing\StartTrialSubscription` (auto-start trial 14 ngày, queue `billing`). `Billing::InvoicePaid` ⇒ `Billing\ActivateSubscription` (swap subscription cũ → mới khi paid).

### Settings
`tenant_settings` (tenant_id, key, value jsonb) · `automation_rules` (tenant_id, name, enabled, trigger jsonb, conditions jsonb, actions jsonb) · `notifications` (tenant_id, user_id?, type, payload jsonb, read_at) · `notification_channels` (tenant_id, type[email|inapp|zalo|telegram], config🔒 jsonb, enabled)

> 🔒 = mã hoá ở tầng ứng dụng (Laravel `encrypted` cast). 🔑 = ràng buộc unique. `?` = nullable.

## 3. ERD (sẽ vẽ chi tiết sau)
Quan hệ cốt lõi: `tenant 1—* channel_account 1—* channel_listing *—* sku` (qua `sku_mapping`); `channel_account 1—* order 1—* order_item *—1 sku`; `order 1—* shipment`; `sku 1—* inventory_level *—1 warehouse`; `sku 1—* inventory_movement`. Vẽ bằng dbdiagram/Mermaid khi schema ổn định (Phase 1–2) và đặt file ảnh + nguồn ở `02-data-model/erd.*`.
