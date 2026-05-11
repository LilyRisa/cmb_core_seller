# Mô hình dữ liệu — Quy ước & Danh mục bảng

**Status:** Draft (sẽ chi tiết hoá theo từng phase) · **Cập nhật:** 2026-05-11

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
`orders` (tenant_id, source, channel_account_id?, external_order_id?, order_number, status, raw_status, payment_status, buyer_name, buyer_phone🔒, shipping_address jsonb, currency, item_total, shipping_fee, platform_discount, seller_discount, tax, grand_total, cod_amount, placed_at, paid_at, shipped_at, delivered_at, completed_at, cancelled_at, cancel_reason, note, tags jsonb, raw_payload jsonb, source_updated_at) · `order_items` (order_id, channel_listing_id?, sku_id?, name, sku_code, variation, quantity, unit_price, discount, subtotal, image) · `order_status_history` (order_id, from_status, to_status, raw_status, source, changed_at, payload jsonb)

### Inventory
`skus` (tenant_id, product_id, sku_code🔑uniq/tenant, barcode, name, cost_price, attributes jsonb) · `warehouses` (tenant_id, name, code, address jsonb, is_default) · `inventory_levels` (sku_id, warehouse_id, on_hand, reserved, available_cached, safety_stock) 🔑uniq(sku_id,warehouse_id) · `inventory_movements` (tenant_id, sku_id, warehouse_id, qty_change, type, ref_type, ref_id, balance_after, note, created_by, created_at) · `sku_mappings` (channel_listing_id, sku_id, quantity, type[single|bundle]) · `stock_transfers`(+`stock_transfer_items`) · `stock_takes`(+`stock_take_items`) · `goods_receipts`(+`goods_receipt_items`) · `cost_layers` (sku_id, warehouse_id, qty_remaining, unit_cost, received_at) — FIFO · (tuỳ chọn) `inventory_batches`

### Products
`products` (tenant_id, name, image, brand, category, meta jsonb) · `channel_listings` (channel_account_id, external_product_id, external_sku_id, seller_sku, title, price, channel_stock, sync_status, last_pushed_at, meta jsonb) · `listing_drafts`(+ items) · `listing_publish_jobs` · `channel_categories` (provider, external_cat_id, parent_id, name, path) · `channel_attributes` (provider, external_cat_id, attribute schema jsonb)

### Fulfillment
`shipments` (tenant_id, order_id, package_no, carrier, carrier_account_id?, tracking_no, status, weight, dims jsonb, label_url, cod_amount, picked_up_at, delivered_at, raw jsonb) · `pickup_batches`(+ link shipments) · `print_jobs` (tenant_id, type[label|picking|packing|channel_invoice], scope jsonb(order/shipment ids), template_id?, file_url, file_size?, status, error, created_by, **expires_at**, **purged_at?**, meta jsonb) · `order_print_documents` (tenant_id, order_id, print_job_id, type, shipment_id?, created_at) — tra cứu nhanh phiếu in của một đơn để in lại trong 90 ngày · `print_templates` (tenant_id, type, paper_size, layout jsonb, logo_url, is_default) · `carrier_accounts` (tenant_id, carrier, credentials🔒 jsonb, default_service, meta jsonb)

### Procurement
`suppliers` · `supplier_prices` (supplier_id, sku_id, price, moq) · `purchase_orders`(+`purchase_order_items`) · `goods_receipts` (dùng chung với Inventory hoặc liên kết PO)

### Finance
`settlements` (channel_account_id, period_start, period_end, total_payout, status, raw jsonb) · `settlement_lines` (settlement_id, order_id?, fee_type, amount, raw jsonb) · `order_costs` (order_id, cost_of_goods, platform_fee, payment_fee, shipping_fee, other_fee, computed_profit, computed_at) · `profit_snapshots` (tenant_id, dimension[order|sku|channel|day], key, revenue, cogs, fees, profit, period)

### Billing
`plans` (code, name, limits jsonb, features jsonb, price) · `subscriptions` (tenant_id, plan_id, status, trial_ends_at, current_period_start/end, cancel_at) · `invoices`(+`invoice_lines`) · `payments` (gateway, ref, amount, status) · `usage_counters` (tenant_id, metric, period, value)

### Settings
`tenant_settings` (tenant_id, key, value jsonb) · `automation_rules` (tenant_id, name, enabled, trigger jsonb, conditions jsonb, actions jsonb) · `notifications` (tenant_id, user_id?, type, payload jsonb, read_at) · `notification_channels` (tenant_id, type[email|inapp|zalo|telegram], config🔒 jsonb, enabled)

> 🔒 = mã hoá ở tầng ứng dụng (Laravel `encrypted` cast). 🔑 = ràng buộc unique. `?` = nullable.

## 3. ERD (sẽ vẽ chi tiết sau)
Quan hệ cốt lõi: `tenant 1—* channel_account 1—* channel_listing *—* sku` (qua `sku_mapping`); `channel_account 1—* order 1—* order_item *—1 sku`; `order 1—* shipment`; `sku 1—* inventory_level *—1 warehouse`; `sku 1—* inventory_movement`. Vẽ bằng dbdiagram/Mermaid khi schema ổn định (Phase 1–2) và đặt file ảnh + nguồn ở `02-data-model/erd.*`.
