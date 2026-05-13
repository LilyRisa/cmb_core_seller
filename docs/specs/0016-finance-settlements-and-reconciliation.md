# SPEC 0016: Đối soát/Settlement của sàn — phí thực thay cho ước tính

- **Trạng thái:** Implemented (2026-05-23 — Phase 6.2 lõi; cần đối chiếu shape sandbox thật để bật flag)
- **Phase:** 6.2 (Tài chính lõi — phí thực theo từng đơn)
- **Module backend liên quan:** Finance (mới), Channels, Orders
- **Liên quan:** SPEC-0001 (TikTok), SPEC-0008 (Lazada), SPEC-0012 (lợi nhuận ước tính), SPEC-0014 (FIFO COGS), SPEC-0015 (báo cáo)

## 1. Vấn đề & mục tiêu
SPEC-0012 dùng **% phí sàn ƯỚC TÍNH** trong `tenant.settings.platform_fee_pct` ⇒ lợi nhuận không chính xác (sàn có thể có voucher, payment fee, shipping subsidy biến động theo đơn). Cần:
1. **Kéo statement thật** từ TikTok/Lazada (Shopee chưa có connector) → bảng `settlements` + `settlement_lines` (bất biến).
2. **Reconcile**: match `settlement_lines.external_order_id → orders.external_order_id` → `order_id`.
3. **`OrderProfitService`** dùng phí THỰC từ `settlement_lines` thay vì ước tính (`fee_source: settlement`).

## 2. Phạm vi
**Trong:**
- DTOs `SettlementDTO` + `SettlementLineDTO` (10 `feeType` chuẩn: `revenue, commission, payment_fee, shipping_fee, shipping_subsidy, voucher_seller, voucher_platform, adjustment, refund, other`).
- Mở rộng `ChannelConnector::fetchSettlements(auth, query): Page<SettlementDTO>` — `UnsupportedOperation` mặc định, gated bởi capability `finance.settlements`.
- TikTok: `/finance/202309/statements` + `/finance/{ver}/statements/{id}/statement_transactions` (theo SDK chính thức `financeV202309Api.ts`); env `INTEGRATIONS_TIKTOK_FINANCE`.
- Lazada: `/finance/transaction/details/get` (offset/limit) — gom toàn bộ rows trong `[from, to]` thành 1 `SettlementDTO`; env `INTEGRATIONS_LAZADA_FINANCE`.
- Manual connector: ném `UnsupportedOperation`.
- Module Finance: `settlements` + `settlement_lines` migrations; `SettlementService` (`fetchForShop`, `reconcile`, `aggregateFeesForOrders`); `FetchSettlementsForShop` job (queue).
- REST: `GET /settlements`, `GET /settlements/{id}`, `POST /settlements/{id}/reconcile`, `POST /channel-accounts/{id}/fetch-settlements`.
- `OrderProfitService.compute()` ưu tiên `settlement_lines` khi đã reconcile ⇒ `fee_source: settlement`.
- UI: `/finance/settlements` — table + Drawer chi tiết (Statistic 4 ô + bảng line chip màu) + Modal "Kéo đối soát từ sàn".
- Permission: `finance.view` (đã có; Owner/Admin/Accountant); `finance.reconcile` (đã có; Owner/Admin/Accountant).

**Ngoài (follow-up):**
- Đối soát Shopee (cần connector Shopee — Phase 4.x).
- Webhook push `settlement_available` (Lazada có push, TikTok chưa rõ) ⇒ tự fetch realtime.
- Settlement → kế toán external (e.g. KiotViet/MISA) — Phase 7.
- Đối soát ship phí thực với ĐVVC (GHN/GHTK/...) — riêng tab Phase sau.

## 3. Luồng chính
1. **Manual fetch:** UI "Kéo đối soát từ sàn" → chọn shop + RangePicker → `POST /channel-accounts/{id}/fetch-settlements {from, to, sync?}` → dispatch `FetchSettlementsForShop` job → `SettlementService::fetchForShop`:
   - Gọi `Connector::fetchSettlements(auth, {from, to})` → `Page<SettlementDTO>`.
   - Per DTO: upsert `settlements` row (unique `(channel_account_id, external_id)`); bulk insert `settlement_lines` dedupe theo `(settlement_id, fee_type, external_order_id, external_line_id, amount)` (Lazada không có id ổn định ⇒ dedupe theo nội dung).
   - Sau upsert, tự gọi `reconcile($settlement)` ngay.
2. **Reconcile:** match `settlement_lines.external_order_id` → `orders.external_order_id` cùng (tenant_id, channel_account_id) → fill `order_id`; settlement `status = reconciled` nếu mọi line đã match. Idempotent (chạy lại không tạo trùng).
3. **Profit:** `OrderProfitService.compute()` đọc `fetchActualFees(orderIds)` → khi có settlement, `platform_fee = Σ |commission + payment_fee + voucher_seller + adjustment|`, `shipping_fee = Σ |shipping_fee|`; `fee_source = 'settlement'`. Khi chưa có ⇒ giữ ước tính (`fee_source = 'estimate'`).

## 4. Hành vi & quy tắc
- **Idempotent**: fetch lại cùng `[from, to]` ⇒ upsert (không trùng).
- **Bất biến**: `settlement_lines` không có `updated_at` (kế toán immutable); chỉ thêm/xoá-rebuild qua fetch mới.
- **Sign convention**: `amount` theo góc nhìn seller — dương = thu vào (revenue, shipping_subsidy, voucher_platform), âm = chi ra (commission, payment_fee, shipping_fee mà seller trả, voucher_seller).
- **Permission**:
  - `finance.view`: Owner/Admin/Accountant — đọc settlements + xem detail.
  - `finance.reconcile`: Owner/Admin/Accountant — bấm "Đối chiếu lại" + trigger fetch.
- **Feature flag**: `INTEGRATIONS_TIKTOK_FINANCE` / `INTEGRATIONS_LAZADA_FINANCE` mặc định `false` cho đến khi đối chiếu shape với sandbox thật ⇒ shop chưa bật ⇒ `fetch-settlements` trả `422 provider unsupported`.

## 5. Dữ liệu
**Migrations** (`Finance/Database/Migrations/2026_05_23_100001_*`):
- `settlements` (unique `(tenant_id, channel_account_id, external_id)`; status `pending|reconciled|error`).
- `settlement_lines` (no updated_at; index `(tenant_id, settlement_id)`, `(tenant_id, order_id)`, `(tenant_id, external_order_id)`, `(tenant_id, fee_type)`).

**Config mới** (`config/integrations.php`):
- `tiktok.finance_enabled` + `endpoints.finance_statements` + `endpoints.finance_statement_transactions`.
- `lazada.finance_enabled` + `endpoints.transaction_details`.

## 6. API
- `GET /settlements?channel_account_id&status&from&to` → list paginated.
- `GET /settlements/{id}` → detail with `lines` (load `order:id,order_number,external_order_id`).
- `POST /settlements/{id}/reconcile` → `{matched, settlement}`.
- `POST /channel-accounts/{id}/fetch-settlements {from, to, sync?}` → `{queued: true}` (async) hoặc `{fetched, lines, queued: false}` (sync mode cho test/sandbox).

## 7. UI
`/finance/settlements`:
- Table với chip màu trạng thái (`pending` vàng, `reconciled` xanh, `error` đỏ).
- Cột Doanh thu / Phí sàn (đỏ) / Phí ship / Sàn trả seller — đầy đủ phân rã.
- Drawer chi tiết: Statistic 4 ô + bảng `SettlementLine` với chip màu 10 fee type + Link sang đơn khi `line.order_id` đã matched.
- Modal "Kéo đối soát từ sàn": Radio chip shop (TikTok/Lazada), RangePicker, Switch `sync mode` (cho test).

## 8. Kiểm thử
- `SettlementApiTest` (3 ca): index/show/reconcile match line → order; RBAC `viewer 403 / accountant view+reconcile`; `fetch-settlements` 422 khi provider chưa bật.
- `LazadaSettlementFetchTest` (2 ca): contract `Http::fake('/finance/transaction/details/get')` → upsert 1 settlement + 4 line + status `reconciled`; `OrderResource.profit.fee_source='settlement'`; idempotent fetch lần 2.

## 9. Triển khai
1. `INTEGRATIONS_TIKTOK_FINANCE=false` / `INTEGRATIONS_LAZADA_FINANCE=false` mặc định.
2. Sau khi đối chiếu sandbox: bật env, đăng ký scope `app/finance/...` ở console sàn.
3. Vào "Đối soát sàn" → kéo settlement → đơn tự match → báo cáo dùng phí thực.

## 10. Câu hỏi mở
- Có cần scheduled job nightly tự kéo settlement cho mọi shop active? — chuẩn bị sẵn (job có `tries=3, backoff=60`); chạy theo lệnh manual trước, scheduled = follow-up khi shop ổn định.
- Đối soát phí ship thực với GHN/GHTK (`carrier_fee_settlements`) — Phase sau (mở rộng cùng cấu trúc).
