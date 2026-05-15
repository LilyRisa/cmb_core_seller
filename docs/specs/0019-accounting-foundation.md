# SPEC 0019: Accounting Foundation — Double-entry GL + CoA (TT133) + Kỳ kế toán + Sổ nhật ký/Sổ cái

- **Trạng thái:** Implemented (2026-05-15 — toàn bộ Phase 7.1→7.5 — 46/46 tests xanh)
- **Phase:** 7.1 → 7.5 (Kế toán đầy đủ — cả 5 sub-phase)
- **Module backend liên quan:** Accounting (mới), Tenancy (RBAC + audit), Inventory (event GoodsReceiptConfirmed + InventoryChanged), Procurement (đọc supplier), Customers (đọc customer), Channels (audit shop dim), Billing (gating `accounting_basic`)
- **Tác giả / Ngày:** Team · 2026-05-15
- **Liên quan:** `01-architecture/modules.md` §3 (luật phụ thuộc), `01-architecture/extensibility-rules.md` §1, `02-data-model/overview.md` §1 (quy ước), `05-api/conventions.md`, `06-frontend/overview.md` §4 (icon font, không emoji; tránh `<Select>`), `08-security-and-privacy.md`, `09-process/testing-strategy.md`, SPEC-0014 (FIFO COGS — nguồn `order_costs`), SPEC-0016 (Settlement — nguồn phí thực), SPEC-0018 (Billing — gating `plan.feature:accounting_basic`). ADR đề xuất kèm spec: **ADR-0011** (double-entry), **ADR-0012** (CoA seed TT133), **ADR-0013** (event-driven posting), **ADR-0014** (period lock), **ADR-0016** (partition `journal_lines` theo `posted_at`).

## 1. Vấn đề & mục tiêu

Hết Phase 6, app đã có ba nguồn dữ liệu chuẩn:
- `inventory_movements` + `order_costs` (FIFO COGS bất biến — SPEC-0014),
- `settlements` + `settlement_lines` (phí sàn thực, bất biến — SPEC-0016),
- `purchase_orders` + `goods_receipts` (mua hàng — SPEC-0014).

Đây là **nguồn nhập** đủ để dựng sổ kế toán kép. Tuy nhiên app **chưa có**:
1. Hệ thống tài khoản (Chart of Accounts) theo VAS.
2. **Sổ cái kép** (double-entry) bất biến — mọi giao dịch quy về `journal_entries` + `journal_lines` cân Nợ/Có.
3. **Kỳ kế toán** + cơ chế đóng/khoá để chống sửa hồi tố.
4. Sổ nhật ký chung + Sổ cái + Sổ chi tiết tài khoản (tiền đề báo cáo tài chính ở 7.5).

**Mục tiêu của SPEC này (Phase 7.1):** xây nền tảng "module kế toán nội bộ" tách bạch, lắng nghe event của các module hiện có, tự ghi sổ kép — **không** module nào import internals của Accounting (luật `modules.md` §3). Phase 7.2/7.3/7.4/7.5 sẽ cắm thêm listener + báo cáo trên cùng nền tảng này.

**Quyết định nền (chốt với chủ dự án 2026-05-15, ghi trong `roadmap.md` Phase 7):**
- CoA = **TT133** (DN nhỏ & vừa).
- **Chỉ VND**, không cột FX.
- Năm tài chính = năm dương lịch.
- Hoãn HĐĐT (Phase 8+).
- Export báo cáo CSV/Excel theo schema MISA — Phase 7.5.
- Mapping CoA cho auto-post **lưu DB**, tenant chỉnh qua UI (`accounting_post_rules`).
- Cuốn chiếu 7.1 → 7.5.

## 2. Trong / ngoài phạm vi

**Trong (SPEC này — Phase 7.1):**
- Migration nền: `chart_accounts`, `fiscal_periods`, `journal_entries`, `journal_lines` (partition tháng), `account_balances`, `accounting_post_rules`.
- Seeder `ChartAccountsTT133Seeder` (idempotent — tenant clone khi onboard, mirror pattern `BillingPlanSeeder`).
- Service nền: `JournalService::post|reverse`, `PeriodService::open|close|reopen|lock`, `BalanceService::recompute`.
- Listener nền (3 cái — phục vụ chính dòng nhập kho + chuyển kho + chênh kiểm kê, vốn đã có sẵn event Phase 5–6):
  - `PostOnGoodsReceiptConfirmed` (listen `Inventory\GoodsReceiptConfirmed`) → Dr 156 / Cr 331.
  - `PostOnInventoryTransfer` (listen `Inventory\InventoryChanged` với `type=transfer_in|transfer_out`) → Dr 156-`dim_warehouse=to` / Cr 156-`dim_warehouse=from`.
  - `PostOnStocktakeAdjust` (listen `Inventory\InventoryChanged` với `type=stocktake_adjust`) → diff>0: Dr 156 / Cr 711; diff<0: Dr 811 / Cr 156.
- API CRUD CoA + kỳ + bút toán + xem `account_balances` + mapping editor.
- UI shell: `/accounting/chart-of-accounts`, `/accounting/journals`, `/accounting/periods`, `/settings/accounting/post-rules`.
- Permission strings `accounting.view|post|close_period|config|export` (gắn vào `Role` enum đã có).
- Billing gating middleware `plan.feature:accounting_basic` (mở rộng `plans.features` JSON; seeder cập nhật — Pro + Business bật, Starter+Trial tắt).
- 5 ADR kèm spec (đặt vào `docs/01-architecture/adr/0011…0016.md`).
- Test: unit (JournalService cân bằng + idempotency + period lock; PeriodService transitions; BalanceService rebuild deterministic) + feature (3 listener nền + period lock chặn post + reverse entry + tenant isolation + RBAC + plan gating) + property test (random 10k entries → invariant `Σ Dr = Σ Cr`).

**Ngoài (làm sau / SPEC sau):**
- Listener cho **OrderShipped/Cancelled/Returned** (đơn → doanh thu + GVHB) — **SPEC-0020** (Phase 7.2, AR).
- Listener cho **SettlementReconciled** (split phí sàn + payout) — **SPEC-0020** (cùng AR vì cùng TK 131).
- Listener cho **GoodsReceiptConfirmed → vendor_bill auto-create** — **SPEC-0021** (AP).
- `cash_accounts` + `bank_statements` + matching ngân hàng — **SPEC-0022** (7.4).
- VAT codes + tờ khai + báo cáo tài chính + export MISA — **SPEC-0023** (7.5).
- HĐĐT (Phase 8+, backlog).
- Phê duyệt nhiều cấp cho bút toán tay; phân bổ chi phí; payroll — Phase 7+ backlog.

## 3. Câu chuyện người dùng / luồng chính

### 3.1 Onboard (lần đầu tenant bật module Kế toán)
1. Owner/admin nâng gói lên **Pro/Business** → middleware `plan.feature:accounting_basic` mở.
2. Lần đầu vào `/accounting`, FE phát hiện `chart_accounts` của tenant rỗng ⇒ banner "Khởi tạo hệ thống tài khoản theo TT133" + nút "Khởi tạo".
3. `POST /api/v1/accounting/setup` ⇒ chạy `ChartAccountsTT133Seeder` cho tenant (idempotent — gọi lại = no-op), tạo `fiscal_periods` cho tháng hiện tại + 11 tháng kế (status=`open`), seed bảng `accounting_post_rules` mặc định.
4. Done → vào `/accounting/chart-of-accounts` thấy đầy đủ TK TT133.

### 3.2 Ghi sổ tự động khi xác nhận phiếu nhập kho
1. Nhân viên kho xác nhận `GoodsReceipt` (đã có từ SPEC-0010/0014) → `Inventory\WarehouseDocumentService::confirmGoodsReceipt` phát event `GoodsReceiptConfirmed`.
2. Listener `PostOnGoodsReceiptConfirmed` (queue `accounting`, retry 3, idempotent) build `JournalEntryDTO`:
   - `idempotency_key = "inventory.goods_receipt.{$id}.posted"`.
   - `posted_at = GR.confirmed_at`, `period` resolved theo tháng đó.
   - `source_module=inventory`, `source_type=goods_receipt`, `source_id={$gr->id}`.
   - Lines (cho mỗi dòng GR): Dr `156` × `qty × unit_cost` (dimension `dim_warehouse_id`, `dim_sku_id`), Cr `331` × cùng giá trị (dimension `party_type=supplier`, `party_id`).
   - Đọc `accounting_post_rules` để biết TK đối ứng (mặc định 156/331; tenant có thể đổi sang 1561/3311 nếu muốn).
3. `JournalService::post` kiểm: kỳ chưa locked, `Σ Dr = Σ Cr`, idempotency_key chưa dùng ⇒ insert `journal_entries` + `journal_lines` trong transaction. Phát `JournalPosted` event.
4. UI `/accounting/journals` lập tức thấy bút toán mới (badge `auto` + link "← Phiếu nhập #PNK-...").

### 3.3 Đóng kỳ
1. Cuối tháng, kế toán vào `/accounting/periods` → bấm "Đóng kỳ 2026-05".
2. `POST /accounting/periods/{code}/close` (cần `accounting.close_period`).
3. `PeriodService::close`:
   - Snapshot `account_balances` cho kỳ → `closing` cuối kỳ trở thành `opening` đầu kỳ kế tiếp.
   - Set `fiscal_periods.status='closed'`, ghi `closed_at`/`closed_by`, audit log.
4. Từ giờ mọi `JournalService::post` với `posted_at` thuộc kỳ này ⇒ **422 PERIOD_CLOSED** trừ khi entry được mark `is_adjustment=true` (chỉ owner/accountant, qua API riêng) — entry điều chỉnh vẫn lưu vào kỳ kế tiếp với `narration="Điều chỉnh kỳ 2026-05"` + `dim_period_id=kỳ-bị-điều-chỉnh`.
5. Mở lại kỳ (`reopen`) chỉ cho phép khi kỳ kế tiếp chưa close — chống cascade rewrite.

### 3.4 Bút toán tay
1. Kế toán vào `/accounting/journals` → "Tạo bút toán tay" → modal: chọn ngày, narration, danh sách dòng (Dr/Cr, TK qua TreeSelect CoA, số tiền, party tuỳ chọn).
2. FE validate client-side `Σ Dr = Σ Cr` trước khi gửi.
3. `POST /accounting/journals {posted_at, narration, lines:[{account_code, dr_amount, cr_amount, party_type, party_id, ...}]}` (cần `accounting.post`).
4. BE re-validate cân bằng + period chưa locked + tài khoản postable + idempotency_key auto-generate `"manual.{user_id}.{client_token}"` (FE truyền `Idempotency-Key` header).

### 3.5 Đảo bút toán
1. Bút toán đã post mà sai → kế toán bấm "Đảo" trên row entry.
2. `POST /accounting/journals/{id}/reverse {reason}` (cần `accounting.post`; nếu entry ở kỳ closed ⇒ entry đảo post vào kỳ mở kế tiếp).
3. `JournalService::reverse` tạo entry mới `is_reversal_of_id=$id`, swap Dr↔Cr; idempotent qua unique `(tenant_id, is_reversal_of_id)`.

## 4. Hành vi & quy tắc

### 4.1 Cân bằng & bất biến
- Mọi entry: `Σ dr_amount = Σ cr_amount` và `≥ 2 lines` — kiểm trong `JournalService::post` + DB check constraint `(SELECT SUM(dr)-SUM(cr) FROM journal_lines WHERE entry_id=NEW.id) = 0` (deferred constraint Postgres, trigger trên SQLite cho test).
- Mỗi `journal_line`: chính xác **1** trong `dr_amount`/`cr_amount` > 0 (CHECK constraint `(dr_amount>0)::int + (cr_amount>0)::int = 1`).
- `journal_entries` + `journal_lines` **bất biến** — không `updated_at`, không soft delete; sửa = đảo + post mới.
- `chart_accounts` + `accounting_post_rules` được **chỉnh sửa** (có `updated_at`), nhưng audit log mọi thay đổi.

### 4.2 Idempotency
- `journal_entries.idempotency_key` unique per tenant.
- Listener tự build key theo công thức `"{source_module}.{source_type}.{source_id}.{kind}"` ⇒ event replay từ Horizon retry ⇒ no-op trả về entry cũ.
- Bút toán tay: dùng header `Idempotency-Key` (xem `05-api/conventions.md` §6) → BE map sang `"manual.{user_id}.{key}"`.

### 4.3 Period lock
- `fiscal_periods.status ∈ {open, closed, locked}`.
  - `open`: post tự do.
  - `closed`: kế toán đã đóng tháng; post mới ⇒ `422 PERIOD_CLOSED`. Đảo entry trong kỳ closed ⇒ entry đảo nhảy sang kỳ mở kế tiếp với `narration` và link `dim_period_id`.
  - `locked`: đã nộp tờ khai / đã ký số ⇒ tuyệt đối không cho đảo nữa; sai chỉ có thể điều chỉnh ở kỳ mới như bút toán mới.
- Resolver: `PeriodService::resolveForDate($date)` trả về `fiscal_period` theo `start_date ≤ date ≤ end_date`; thiếu kỳ ⇒ auto-create tháng đó (trừ năm trước cutoff configurable).

### 4.4 Mapping rules (tenant chỉnh)
- Bảng `accounting_post_rules` lưu, mỗi rule = 1 cặp (source event, account_code mapping). Seed mặc định khi onboard:
  ```
  inventory.goods_receipt.confirmed → debit=156, credit=331
  inventory.transfer                 → debit=156(to), credit=156(from)
  inventory.stocktake_adjust.in      → debit=156, credit=711
  inventory.stocktake_adjust.out     → debit=811, credit=156
  ```
- Tenant chỉnh qua UI `/settings/accounting/post-rules`: chọn TK đối ứng từ TreeSelect CoA. Validate `is_postable=true`.
- Đổi rule **không** ảnh hưởng entry đã post (immutable); chỉ áp cho entry mới từ thời điểm sửa.

### 4.5 Phân quyền (RBAC)
| Permission | Mô tả | Role mặc định |
|---|---|---|
| `accounting.view` | Xem CoA, journals, balances, reports | owner, admin, accountant |
| `accounting.post` | Tạo bút toán tay + đảo entry | owner, admin, accountant |
| `accounting.close_period` | Đóng/mở/lock kỳ | owner, accountant |
| `accounting.config` | Sửa CoA + mapping rules | owner, admin |
| `accounting.export` | Export báo cáo (7.5 dùng) | owner, admin, accountant |

`staff_order`/`staff_warehouse`/`viewer` không có gì.

### 4.6 Tác động sang module khác
- **Không** sửa schema bảng module khác. Không cascade. Module Accounting đứng độc lập.
- `journal_lines.party_type/party_id` chỉ là soft reference (string + bigint, không FK) — tránh phụ thuộc cứng ngược (chính Accounting phụ thuộc Customers/Suppliers chứ không ngược lại). Khi `customers.deleted_at` hoặc `suppliers.deleted_at` ⇒ Accounting không bị broken; UI hiển thị "[đã xoá]".

## 5. Dữ liệu

### 5.1 Migrations (đặt trong `Accounting/Database/Migrations/2026_xx_xx_*`)

**`chart_accounts`**
```
id bigint PK
tenant_id bigint NOT NULL index
code varchar(16) NOT NULL          -- '156', '1561', '131', '511', '33311', ...
name varchar(255) NOT NULL
type varchar(16) NOT NULL          -- 'asset|liability|equity|revenue|expense|cogs|contra'
parent_id bigint NULL              -- self-ref
is_postable bool default true
normal_balance varchar(8) NOT NULL -- 'debit|credit'
vas_template varchar(8) NOT NULL   -- 'tt133' v1; 'tt200|custom' để mở rộng
is_active bool default true
sort_order int default 0
created_at, updated_at
UNIQUE (tenant_id, code)
INDEX (tenant_id, type, is_active)
```

**`fiscal_periods`**
```
id bigint PK
tenant_id bigint NOT NULL index
code varchar(16) NOT NULL          -- '2026-05', '2026-Q2', '2026'
kind varchar(8) NOT NULL           -- 'month|quarter|year'
start_date date NOT NULL
end_date date NOT NULL
status varchar(8) NOT NULL         -- 'open|closed|locked'
closed_at timestamp NULL
closed_by bigint NULL              -- user_id (soft ref)
created_at, updated_at
UNIQUE (tenant_id, code)
INDEX (tenant_id, kind, start_date)
```

**`journal_entries`** — **bất biến** (no `updated_at`)
```
id bigint PK
tenant_id bigint NOT NULL index
code varchar(24) NOT NULL          -- 'JE-YYYYMM-NNNN' per tenant
posted_at timestamp NOT NULL       -- ngày hạch toán (key partition lines)
period_id bigint NOT NULL          -- FK fiscal_periods
narration varchar(500) NULL
source_module varchar(24) NOT NULL -- 'inventory|orders|finance|procurement|billing|cash|manual'
source_type varchar(48) NOT NULL   -- 'goods_receipt|order|settlement|vendor_bill|...'
source_id bigint NULL              -- soft ref
idempotency_key varchar(191) NOT NULL
is_adjustment bool default false
is_reversal_of_id bigint NULL      -- self-ref
total_debit bigint NOT NULL        -- denormalized sum, bằng total_credit
total_credit bigint NOT NULL
currency varchar(8) NOT NULL default 'VND'
created_by bigint NULL             -- user_id (NULL = listener)
created_at timestamp NOT NULL
UNIQUE (tenant_id, idempotency_key)
UNIQUE (tenant_id, code)
UNIQUE (tenant_id, is_reversal_of_id) WHERE is_reversal_of_id IS NOT NULL  -- partial
INDEX (tenant_id, period_id, posted_at)
INDEX (tenant_id, source_module, source_type, source_id)
```

**`journal_lines`** — **bất biến**, **partition `posted_at` theo tháng** (ADR-0016)
```
id bigint                          -- not PK alone vì partition; (id, posted_at) composite
tenant_id bigint NOT NULL
entry_id bigint NOT NULL
posted_at timestamp NOT NULL       -- partition key
account_id bigint NOT NULL         -- FK chart_accounts
account_code varchar(16) NOT NULL  -- denormalized snapshot, đỡ join báo cáo
dr_amount bigint NOT NULL default 0
cr_amount bigint NOT NULL default 0
party_type varchar(16) NULL        -- 'customer|supplier|staff'
party_id bigint NULL               -- soft ref
dim_warehouse_id bigint NULL
dim_shop_id bigint NULL            -- channel_account_id
dim_sku_id bigint NULL
dim_order_id bigint NULL
dim_po_id bigint NULL
dim_tax_code varchar(16) NULL
memo varchar(500) NULL
CHECK ((dr_amount > 0)::int + (cr_amount > 0)::int = 1)
INDEX (tenant_id, account_id, posted_at)
INDEX (tenant_id, party_type, party_id, posted_at)
INDEX (entry_id)
```
> Partition theo tháng dùng cùng cơ chế `MonthlyPartition`/`PartitionRegistry` đã có (Phase 0); job `db:partitions:ensure` mở rộng cover bảng này.

**`account_balances`** — materialized aggregate (rebuild được)
```
id bigint PK
tenant_id bigint NOT NULL index
account_id bigint NOT NULL
period_id bigint NOT NULL
party_type varchar(16) NULL
party_id bigint NULL
dim_warehouse_id bigint NULL
dim_shop_id bigint NULL
opening bigint NOT NULL default 0  -- signed (theo normal_balance của account)
debit bigint NOT NULL default 0
credit bigint NOT NULL default 0
closing bigint NOT NULL default 0
recomputed_at timestamp NOT NULL
UNIQUE (tenant_id, account_id, period_id, COALESCE(party_type,''), COALESCE(party_id,0), COALESCE(dim_warehouse_id,0), COALESCE(dim_shop_id,0))
```

**`accounting_post_rules`** — tenant-editable mapping
```
id bigint PK
tenant_id bigint NOT NULL index
event_key varchar(64) NOT NULL     -- 'inventory.goods_receipt.confirmed', 'inventory.transfer', ...
debit_account_code varchar(16) NOT NULL
credit_account_code varchar(16) NOT NULL
is_enabled bool default true
notes varchar(255) NULL
updated_by bigint NULL
created_at, updated_at
UNIQUE (tenant_id, event_key)
```

### 5.2 Seed CoA TT133

Seeder `Accounting/Database/Seeders/ChartAccountsTT133Seeder.php` (idempotent per tenant). Danh sách TK gốc theo TT133 (rút gọn — bản đủ trong code seeder):

| Code | Tên | Type | Normal | Postable |
|---|---|---|---|---|
| 111 / 1111 / 1112 | Tiền mặt / VND / Ngoại tệ | asset | debit | parent no / leaf yes |
| 112 / 1121 / 1122 | Tiền gửi NH / VND / Ngoại tệ | asset | debit | parent no / leaf yes |
| 131 | Phải thu khách hàng | asset | debit | yes |
| 133 / 1331 / 1332 | Thuế GTGT được khấu trừ | asset | debit | yes |
| 138 / 1381 / 1388 | Phải thu khác | asset | debit | yes |
| 152 | Nguyên liệu, vật liệu | asset | debit | yes |
| 153 | Công cụ, dụng cụ | asset | debit | yes |
| 156 / 1561 / 1562 | Hàng hoá / Giá mua / CP mua | asset | debit | yes |
| 211 / 214 | TSCĐ / Khấu hao TSCĐ | asset / contra | debit / credit | yes |
| 331 | Phải trả NCC | liability | credit | yes |
| 333 / 3331 / 33311 / 3334 / 3335 / 3338 / 33888 | Thuế và các khoản phải nộp NN | liability | credit | yes |
| 334 / 338 | Phải trả NLĐ / Phải trả khác | liability | credit | yes |
| 411 / 421 / 4211 | Vốn đầu tư của CSH / Lợi nhuận chưa phân phối / Năm nay | equity | credit | yes |
| 511 / 5111 / 5112 | Doanh thu BH&CCDV / Bán hàng hoá / DV | revenue | credit | yes |
| 515 | Doanh thu HĐ tài chính | revenue | credit | yes |
| 521 / 5211 / 5212 / 5213 | Các khoản giảm trừ DT | contra revenue | debit | yes |
| 611 / 632 | Mua hàng / Giá vốn hàng bán | cogs | debit | yes |
| 635 | Chi phí tài chính | expense | debit | yes |
| 642 / 6421 / 6422 | CP quản lý KD | expense | debit | yes |
| 711 / 811 | Thu nhập khác / Chi phí khác | revenue / expense | credit / debit | yes |
| 821 / 911 | CP thuế TNDN / Xác định KQKD | expense / clearing | debit | yes |

> Bản đầy đủ sẽ có ~80 TK chuẩn TT133 (gồm cả TK chi tiết cấp 2/3). Tenant clone vào bảng `chart_accounts` của mình ⇒ chỉnh được name/sort_order, đổi `is_active`, thêm TK con; **không** xoá TK đang có phát sinh.

### 5.3 Events
- **Lắng nghe (subscribe):**
  - `Inventory\GoodsReceiptConfirmed` (đã có Phase 6.1).
  - `Inventory\InventoryChanged` với `type ∈ {transfer_in, transfer_out, stocktake_adjust}` (đã có Phase 2/5).
- **Phát (publish):**
  - `Accounting\JournalPosted(entry)` — module khác lắng nghe để recompute cache nếu cần (báo cáo 7.5).
  - `Accounting\PeriodClosed(period)` / `PeriodReopened(period)` / `PeriodLocked(period)`.

## 6. API

Tiền tố `/api/v1`, middleware `auth:sanctum` + `tenant` + `plan.feature:accounting_basic`.

### 6.1 Setup
- `POST /accounting/setup` — chạy `ChartAccountsTT133Seeder` cho tenant + tạo 12 `fiscal_periods` đầu (tháng hiện tại + 11 kế); idempotent. Cần `accounting.config`. Response: `{ data: { accounts_created, periods_created } }`.

### 6.2 Chart of Accounts
- `GET /accounting/accounts?type=&q=&active=` — list (cây).
- `POST /accounting/accounts` — tạo TK con (cần `accounting.config`). Body: `{code, name, type, parent_code?, normal_balance, is_postable}`.
- `PATCH /accounting/accounts/{id}` — đổi name/sort_order/is_active (không đổi `type`/`normal_balance` nếu TK đã có phát sinh).
- `DELETE /accounting/accounts/{id}` — chặn `409` nếu có `journal_lines` ref.

### 6.3 Fiscal Periods
- `GET /accounting/periods?kind=&year=`.
- `POST /accounting/periods/{code}/close` — cần `accounting.close_period`. Yêu cầu mọi `pending` entry kỳ trước phải đã `posted`. Tự tạo `account_balances` snapshot.
- `POST /accounting/periods/{code}/reopen` — chỉ khi kỳ kế tiếp còn `open` (chưa close).
- `POST /accounting/periods/{code}/lock` — chỉ owner; lock = không bao giờ reopen được.

### 6.4 Journal Entries
- `GET /accounting/journals?period=&source_module=&account=&party=&from=&to=&q=&sort=` — phân trang (page-based 20/100).
- `GET /accounting/journals/{id}` — kèm `lines`.
- `POST /accounting/journals` — tạo bút toán tay. Header `Idempotency-Key` bắt buộc. Body:
  ```
  {
    posted_at: "2026-05-15",
    narration: "Tạm ứng văn phòng phẩm",
    lines: [
      {account_code:"642", dr_amount:500000, party_type:null, party_id:null, memo:"VPP"},
      {account_code:"1111", cr_amount:500000, memo:"Quỹ tiền mặt"}
    ]
  }
  ```
  Lỗi 422: `VALIDATION_FAILED` (cân bằng, lines<2, account không postable, period closed).
- `POST /accounting/journals/{id}/reverse {reason}` — cần `accounting.post`. Trả entry mới `is_reversal_of_id`.

### 6.5 Balances
- `GET /accounting/balances?period=&account=&party_type=&party_id=&dim_warehouse_id=` — đọc materialized `account_balances`; nếu chưa recompute cho kỳ ⇒ trigger sync recompute (≤1s cho 1 kỳ) hoặc 202 + job nếu lớn.
- `POST /accounting/balances/recompute {period}` — cần `accounting.config`. Enqueue `RecomputeBalancesJob` (queue `accounting`, supervisor mới).

### 6.6 Post Rules
- `GET /accounting/post-rules` — list mapping.
- `PATCH /accounting/post-rules/{event_key}` — body `{debit_account_code, credit_account_code, is_enabled, notes}`. Cần `accounting.config`. Audit log diff.

### 6.7 Errors (codes chuẩn — bổ sung)
- `ACCOUNTING_PERIOD_CLOSED` (422)
- `ACCOUNTING_PERIOD_LOCKED` (422)
- `ACCOUNTING_UNBALANCED` (422)
- `ACCOUNTING_ACCOUNT_NOT_POSTABLE` (422)
- `ACCOUNTING_IDEMPOTENCY_REPLAY` (200 — trả entry cũ)
- `ACCOUNTING_ACCOUNT_IN_USE` (409 — delete bị chặn)
- `PLAN_FEATURE_LOCKED` (402 — từ Billing middleware, không phải Accounting riêng)

> Cập nhật `docs/05-api/endpoints.md` khi merge.

## 7. UI

Nhất quán `06-frontend/overview.md`: AntD 5, icon `@ant-design/icons` (KHÔNG emoji), tránh `<Select>` cho lựa chọn ngắn (dùng `Radio.Group`/`Segmented`); CoA picker dùng `TreeSelect`.

Cấu trúc menu sidebar (mục mới "Kế toán" — chỉ hiện khi plan có `accounting_basic`):
```
Kế toán
├── Sổ nhật ký                  → /accounting/journals
├── Hệ thống tài khoản          → /accounting/chart-of-accounts
├── Kỳ kế toán                  → /accounting/periods
└── (Phase 7.5) Báo cáo TC      → /accounting/reports
```

### 7.1 `/accounting/journals`
- Header: PageHeader + nút "Tạo bút toán tay" (cần `accounting.post`).
- Filter bar: RangePicker `posted_at`, `Segmented` source_module (Tất cả / Tự động / Thủ công), `TreeSelect` account, search narration.
- Table cột: Mã JE · Ngày · Tổng Nợ · Tổng Có · Nguồn (badge `auto`/`manual` + link source) · Người tạo · Tác vụ (Xem / Đảo).
- Drawer chi tiết: header + bảng `journal_lines` (account code/name, Dr, Cr, party, dim_*, memo), footer Total. Nút "Đảo" có popconfirm.
- Modal "Tạo bút toán tay": `DatePicker`, `Input` narration, `Form.List` lines (TreeSelect CoA + InputNumber dr/cr + chọn party + memo); footer hiển thị live `Σ Dr` vs `Σ Cr` (đỏ khi chưa cân).

### 7.2 `/accounting/chart-of-accounts`
- Tree view (AntD `Tree` từ `chart_accounts.parent_id`).
- Mỗi node: code · name · badge type · số phát sinh kỳ hiện tại (load lazily từ `account_balances`).
- Nút "Thêm TK con" trên row (cần `accounting.config`).
- Banner đầu khi rỗng: "Khởi tạo TT133" → call `POST /accounting/setup`.

### 7.3 `/accounting/periods`
- Bảng 12 kỳ tháng năm hiện tại + tab "Quý" + "Năm".
- Mỗi row: code · khoảng ngày · status badge (open/closed/locked) · closed_at · closed_by · nút Đóng/Mở/Khoá theo permission.
- Đóng kỳ trigger modal xác nhận: liệt kê số entry trong kỳ, tổng phát sinh.

### 7.4 `/settings/accounting/post-rules`
- Bảng `event_key` × Dr account × Cr account × Bật/Tắt.
- Mỗi row sửa inline qua `TreeSelect` (validate `is_postable=true`).
- Cảnh báo: "Đổi mapping không ảnh hưởng entry đã post; chỉ áp cho entry mới."

### 7.5 Frontend libs/hooks
- `resources/js/lib/accounting/` mới: `useAccounts`, `usePeriods`, `useJournals`, `usePostRules`, `usePostJournal`, `useReverseJournal`, `useSetupAccounting`, `useBalances`.
- Component dùng chung: `<AccountTreeSelect>`, `<PartyPicker>` (customer/supplier qua tab), `<MoneyDrCrInput>` (InputNumber cặp; chỉ 1 ô >0).

### 7.6 Job & Queue (cập nhật `07-infra/queues-and-scheduler.md`)
- Queue mới `accounting` — supervisor riêng (low-priority, 2 workers). Tries 3, backoff 30s.
- Jobs: `RecomputeBalancesJob` (tenant_id + period_id; ShouldBeUnique). Scheduler:
  - `accounting:recompute-balances --due` — chạy hằng đêm cho kỳ hiện tại + kỳ kế tiếp (idempotent rebuild).

## 8. Edge case & lỗi

- **Event đến trước khi tenant onboard Accounting** (vd shop chưa nâng gói nhưng GoodsReceipt vẫn confirmed) ⇒ listener tự skip (check `chart_accounts` rỗng ⇒ return) + log info. Khi onboard sau, chạy `POST /accounting/setup` không tự "backfill" entry quá khứ — chủ động cảnh báo: "Phát sinh trước ngày X sẽ không có trong sổ; vào /accounting/balances/recompute → opening manual".
- **Idempotency replay** — Horizon retry sau khi DB transaction đã commit nhưng response chưa gửi ⇒ listener gọi lại post cùng `idempotency_key` ⇒ trả entry cũ, không tạo trùng.
- **Cân không chính xác (làm tròn)** — không có (tất cả tiền là `bigint` VND đồng, không float). Tuy nhiên Σ rule lines có thể tạo lệch 1 đồng do mapping % → JournalService check chính xác bằng nhau ⇒ listener phải dồn 1 đồng dư vào line cuối (logic gọn).
- **Account đã bị đổi `is_postable=false` mà có rule trỏ tới** — UI sửa rule lập tức cảnh báo; listener gặp account không postable ⇒ raise `ACCOUNTING_ACCOUNT_NOT_POSTABLE`, retry queue rồi sau N lần ⇒ fail-task vào dead-letter; UI `/horizon` thấy + có command `accounting:rebuild-from-events --since=` để chạy lại sau khi sửa rule.
- **Kỳ chưa tạo** — `PeriodService::resolveForDate` auto-create cho tháng đó (nếu năm hợp lệ, không quá xa quá khứ — cấu hình `accounting.auto_create_periods_back_months=12`).
- **Đảo bút toán xuyên kỳ** — entry gốc kỳ closed, entry đảo phải đặt ở kỳ mở kế tiếp; ghi `narration="Đảo JE-XXX (kỳ Y) - lý do"` + `dim_period_id=y`.
- **Race condition đóng kỳ** — `PeriodService::close` lấy `lockForUpdate` trên row period; nếu trong lúc đó có job post ⇒ post fail-retry sau (kỳ kế tiếp lúc đó đã có period mở).
- **Tenant rollback gói (Pro → Starter)** — middleware `plan.feature` chặn API mới; data cũ giữ nguyên (không xoá `journal_entries`); banner trên `/accounting/*` "Module bị khoá, dữ liệu kế toán an toàn — nâng gói để tiếp tục."

## 9. Bảo mật & dữ liệu

- Nhất quán `08-security-and-privacy.md` §2: `tenant_id` mọi bảng + global scope; policy kiểm `journal_entries.tenant_id === current_tenant`.
- `journal_lines.party_id` soft reference — không leak cross-tenant; UI render qua `Customers`/`Procurement` service tự scope.
- Audit log mọi: post entry manual, reverse, close/reopen/lock kỳ, edit `post_rules`, edit/disable CoA.
- Bút toán có dim sensitive (vd `dim_order_id` hoặc memo chứa PII) — không log Sentry full body; log chỉ `entry_id`/`code`.
- `accounting_post_rules` chỉ owner/admin sửa (`accounting.config`).
- `journal_entries`/`journal_lines` **không** soft-delete + **không** API delete: cố tình theo Luật Kế toán (số liệu KT phải lưu tối thiểu 10 năm).
- File export báo cáo (Phase 7.5) lưu MinIO theo prefix `tenants/{id}/accounting/exports/`, signed URL ngắn.

## 10. Kiểm thử (nhất quán `09-process/testing-strategy.md`)

**Unit:**
- `JournalServiceTest`: cân bằng, ≥2 lines, idempotency replay (return entry cũ), period closed reject, account not postable reject, reverse swap Dr↔Cr.
- `PeriodServiceTest`: open → close → reopen rule (chặn nếu kỳ kế tiếp đã close); lock không reopen được; resolveForDate auto-create.
- `BalanceServiceTest`: rebuild deterministic — chạy 2 lần cùng dataset = same result.

**Feature (Pest):**
- `AccountingSetupTest`: `POST /accounting/setup` seed CoA TT133 (~80 TK), tạo 12 periods, idempotent (gọi lại không nhân đôi).
- `JournalApiTest`: tạo manual, list/filter, reverse, validate cân bằng + period locked + RBAC (viewer 403, staff_order 403, accountant 200, owner 200) + plan gating (Starter 402).
- `GoodsReceiptPostingTest`: confirm GR → listener post Dr 156/Cr 331 đúng số tiền, đúng dimension (`dim_warehouse_id`, `dim_sku_id`, `party_type=supplier`); replay event không tạo trùng.
- `InventoryTransferPostingTest`: chuyển kho A→B → 2 lines Dr 156 (`dim_warehouse=B`), Cr 156 (`dim_warehouse=A`).
- `StocktakeAdjustPostingTest`: dư → Dr 156/Cr 711; thiếu → Dr 811/Cr 156.
- `PeriodCloseTest`: đóng kỳ → `account_balances` snapshot đúng (opening kỳ sau = closing kỳ trước); post vào kỳ closed → 422 `PERIOD_CLOSED`; reverse entry kỳ closed → entry đảo nhảy kỳ kế tiếp với `dim_period_id`.
- `TenantIsolationTest`: tenant A không thấy CoA/journals của tenant B; `setup` tenant A không tạo entry cho tenant B.

**Property test:**
- `JournalInvariantTest`: random 10.000 entries với random lines (đảm bảo balanced) → `Σ Dr = Σ Cr` toàn bảng.

**Coverage gate:** giữ ≥60% như CI hiện tại; module Accounting đặt mục tiêu nội bộ ≥75% (logic kế toán cao tier).

## 11. Tiêu chí hoàn thành

- [ ] 6 migration: `chart_accounts`, `fiscal_periods`, `journal_entries`, `journal_lines` (partition), `account_balances`, `accounting_post_rules`. Reversible.
- [ ] 4 service: `JournalService`, `PeriodService`, `BalanceService`, `AccountingSetupService`.
- [ ] 3 listener nền (GoodsReceipt, Transfer, Stocktake) + đăng ký trong `AccountingServiceProvider`.
- [ ] Seeder `ChartAccountsTT133Seeder` (~80 TK).
- [ ] Permission strings + cấp vào `Role` enum + middleware `plan.feature:accounting_basic` áp tất cả route `/api/v1/accounting/*`.
- [ ] API CRUD (CoA / Periods / Journals / Balances / PostRules / Setup) — đầy đủ envelope `data`/`error` + error codes ở §6.7.
- [ ] FE 4 trang (`/accounting/journals`, `/accounting/chart-of-accounts`, `/accounting/periods`, `/settings/accounting/post-rules`) + sidebar group + banner trial setup.
- [ ] Job `RecomputeBalancesJob` + queue `accounting` + scheduler nightly.
- [ ] ADR-0011 … ADR-0016 viết kèm.
- [ ] Test xanh: ≥10 feature test + unit + property; full suite không tụt coverage.
- [ ] Tài liệu cập nhật: `docs/specs/README.md` (thêm row 0019), `docs/02-data-model/overview.md` §Accounting (mới), `docs/05-api/endpoints.md`, `docs/07-infra/queues-and-scheduler.md` (queue `accounting`), `docs/01-architecture/modules.md` §1 (thêm row Accounting), `docs/00-overview/glossary.md` (Bút toán/Sổ cái kép/Kỳ kế toán/CoA TT133).

## 12. Câu hỏi mở

- Có cần job **backfill** entry tự động cho dữ liệu trước ngày onboard không? Đề xuất v1: KHÔNG (đơn giản, kế toán tự nhập opening balance qua bút toán tay "Số dư đầu kỳ"). Mở nếu user thực sự yêu cầu.
- `journal_lines` partition theo `posted_at` — chính sách archive partition cũ (>2 năm) khi nào? Luật KT yêu cầu lưu 10 năm ⇒ archive sang cold storage (S3 Glacier-equivalent) thay vì drop. Phase 7.5 quyết định.
- `account_balances` có nên tách bảng riêng cho từng cấp dimension (vd `balances_by_party`, `balances_by_warehouse`) thay vì cột nullable + composite unique? Đo lại khi có data thật.
- Có cần soft-delete cho `accounting_post_rules` (audit theo thời gian rule nào áp ở thời điểm post nào)? Đề xuất v1: snapshot rule vào `journal_entries.meta` lúc post → audit đầy đủ mà không cần soft-delete.
