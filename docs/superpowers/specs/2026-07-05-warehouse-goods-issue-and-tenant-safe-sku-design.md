# Phiếu xuất kho (goods-issue) + cách ly SKU theo tenant — Design

Date: 2026-07-05
Status: Approved (pending implementation plan)

Add a standalone **goods-issue** (phiếu xuất kho, PXK) warehouse document — the missing stock-decrease counterpart to the existing goods-receipt — usable by both the web UI and third-party API keys, and harden SKU tenant-isolation so an API key can never operate on another tenant's SKU.

Golden rules honored: inventory master SKU = single source of truth (ADR-0008); `InventoryLedgerService` is the only writer of stock; every business table carries `tenant_id` + `BelongsToTenant`; core never learns a marketplace name. User-facing strings Vietnamese; code/identifiers English. All PHP/Node run from `app/`.

## Background (current state)

The WMS "phiếu" feature (SPEC 0010) is **three document types dispatched through one polymorphic controller/service** keyed by a `{type}` route segment:

| type | code | stock effect | permission | confirm service |
|------|------|--------------|------------|-----------------|
| `goods-receipts` (nhập) | PNK | `on_hand += qty` (`goods_receipt`) | `inventory.adjust` | `confirmGoodsReceipt` |
| `stock-transfers` (chuyển) | PCK | A `−qty` → B `+qty` (`transfer_out`/`transfer_in`) | `inventory.transfer` | `confirmTransfer` |
| `stocktakes` (kiểm kê) | PKK | `on_hand += diff` (`stocktake_adjust`) | `inventory.stocktake` | `confirmStocktake` |

Lifecycle: `draft → confirmed` (confirm applies stock) or `draft → cancelled`; confirmed docs are immutable. Endpoints `GET/POST /api/v1/warehouse-docs/{type}`, `POST .../{id}/confirm`, `POST .../{id}/cancel` — all inside the `auth:sanctum → verified → tenant → plan.over_quota_lock` group, which an owner's Sanctum PAT (API key) authenticates against. `EnsureTenant` forces `tenant_id` from the token (ignores `X-Tenant-Id` for bearer tokens). SKU/warehouse ids are validated by a tenant-scoped `whereIn(id)->count()` equality check (relies on `TenantScope`).

**Gaps this spec closes:**
1. No standalone phiếu xuất kho — the only stock-decrease WMS doc is stock-transfer's OUT leg (requires a destination warehouse).
2. The cross-tenant SKU-rejection behavior is implicit (a generic "SKU không hợp lệ" message) and has no dedicated test proving an API key cannot touch another tenant's SKU.

## Design

### 1. New document type `goods-issues` (PXK)

Mirror `goods-receipts`, but decreasing stock. Register it as a fourth `{type}` so both web and API get it for free.

**Tables** (new migration `app/app/Modules/Inventory/Database/Migrations/2026_07_05_100003_create_goods_issues_tables.php`):
- `goods_issues`: `id, tenant_id (index), code (PXK-…, unique per tenant), warehouse_id, reason (nullable string — lý do xuất: hủy/hỏng/biếu tặng/…), note (nullable), status (default 'draft'), confirmed_at, confirmed_by, created_by, timestamps`. Unique `(tenant_id, code)`.
- `goods_issue_items`: `id, tenant_id, goods_issue_id (FK cascade), sku_id (FK), qty (int), timestamps`.

**Models** `app/app/Modules/Inventory/Models/GoodsIssue.php` + `GoodsIssueItem.php` — `BelongsToTenant`, same `STATUS_DRAFT/CONFIRMED/CANCELLED` constants as `GoodsReceipt`, `items()` hasMany.

**Ledger** (`InventoryLedgerService` + `InventoryMovement`):
- New movement constant `GOODS_ISSUE = 'goods_issue'` on `InventoryMovement`.
- New method `InventoryLedgerService::issue(int $tenantId, int $skuId, ?int $warehouseId, int $qty, ?string $note = null, ?int $userId = null, ?string $refType = null, ?int $refId = null): InventoryMovement` — decreases on_hand (`apply(...)` with `qty_change = -$qty`, type `goods_issue`), same lock/refresh/append/`InventoryChanged` path as `receipt`. `$qty` is a positive magnitude; the method negates it.

**Confirm service** `WarehouseDocumentService::confirmGoodsIssue(GoodsIssue $doc): void` — `assertDraft`; `DB::transaction`; per item → **negative-stock guard** then `ledger->issue(tenantId, sku_id, warehouse_id, qty, 'Xuất kho '.code, refType='goods_issue', refId=doc id, userId)`; set `status=confirmed`, `confirmed_at/by`; dispatch new event `GoodsIssueConfirmed`.

**Controller registration** (`WarehouseDocumentController`):
- Add `'goods-issues'` to the `{type}` whitelist (route `whereIn`) and to `PERM` → `inventory.adjust`.
- `store`: validation branch for goods-issues — `warehouse_id` required (tenant-scoped exists), `reason` nullable string ≤255, `items` required 1..500, `items.*.sku_id` required int, `items.*.qty` required int min 1.
- `confirm`: dispatch `GoodsIssue → confirmGoodsIssue` (match by model class, alongside the existing three).
- `present`: include `reason` for goods-issues (mirror how `supplier` is shown for goods-receipts).
- `cancel` and `index`/`show` work unchanged via the polymorphic path.

### 2. Negative-stock policy on issue (DECISION: block)

Unlike `adjust`/`transfer` (which allow negatives via the `is_negative` flag), a goods-issue that would drive **`on_hand` below 0** is rejected. In `confirmGoodsIssue`, before applying each line, aggregate required qty per `(sku_id, warehouse_id)` and check `ledger->onHand(tenantId, skuId, warehouseId) >= requiredQty`. If any line fails, throw a validation error (surfaced as **422**) whose message names the offending `sku_id` and the shortfall, and **no** stock is mutated (the whole confirm is in one transaction, so an early throw rolls back). Guard on `on_hand` (physical), not `available` — reserved stock is a separate concern.

### 3. Permission

Goods-issues reuse **`inventory.adjust`** (both nhập and xuất are "điều chỉnh tồn"; no new permission to seed). Owner role already grants it. (A future `inventory.issue` split — to grant issue-only or receipt-only keys — is explicitly out of scope for this spec.)

### 4. SKU tenant-isolation hardening (the security requirement)

Goal: an API key bound to tenant A can **never** create or confirm a warehouse document that touches tenant B's SKU (IDOR/attack prevention).

- **Already enforced, inherited by goods-issues:** `EnsureTenant` binds the token's tenant; `store` resolves `sku_id`/`warehouse_id` via tenant-scoped queries, so a foreign id is not found.
- **Hardening (applies to ALL warehouse-doc types, not just the new one):** replace the generic "SKU không hợp lệ." with an explicit check that computes the set of submitted `sku_id`s not owned by the current tenant and returns **422** with a message naming those ids (e.g. `"SKU không thuộc gian hàng: 123, 456"`), field-keyed under `items`. Same treatment for `warehouse_id`. This makes the rejection explicit and auditable rather than a silent count mismatch.
- **Proof (tests):** a dedicated feature test using a Sanctum PAT (API key) for tenant A that submits tenant B's `sku_id` to `POST /warehouse-docs/goods-issues` (and `/goods-receipts`) → asserts **422**, asserts the response names the offending id, and asserts **no** `inventory_movements` / stock change occurred for either tenant.

### 5. Web UI

Add a "Phiếu xuất kho" tab/section to `app/resources/js/components/WarehouseDocsTab.tsx` (hosted in `InventoryPage.tsx`), mirroring the goods-receipts form: pick warehouse, optional reason/note, add lines (SKU picker + qty), save draft, confirm, cancel. Reuse the existing warehouse-doc data hooks/types (extend the type union with `goods-issues`). Icons via `@ant-design/icons` (no emoji); reuse the existing table/action-toolbar patterns.

### 6. Docs

- `docs/05-api/endpoints.md`: add `goods-issues` to the warehouse-docs section (create/confirm/cancel), note it decreases stock and blocks negative on_hand, and note the explicit cross-tenant SKU rejection (422).
- Note in the inventory/WMS doc (or SPEC 0010 reference) that goods-issue is the phiếu-xuất counterpart of goods-receipt.

## Data flow

Create: `POST /api/v1/warehouse-docs/goods-issues` `{ warehouse_id, reason?, note?, items:[{sku_id, qty}] }` → validate (permission `inventory.adjust`; tenant-owned warehouse_id + sku_ids) → `WarehouseDocumentService` creates a draft `GoodsIssue` + items → `201 {data: present(doc)}`.
Confirm: `POST /api/v1/warehouse-docs/goods-issues/{id}/confirm` → `confirmGoodsIssue` → per-line negative-stock guard → `ledger->issue` (−qty, movement `goods_issue`) → status `confirmed` → `GoodsIssueConfirmed`.
Cancel (draft only): `POST .../{id}/cancel` → status `cancelled`.

## Error handling

- Missing permission → 403 (existing `authorizeFor`).
- Foreign/unknown `sku_id`/`warehouse_id` → 422 naming the offending ids (no mutation).
- Confirm on a non-draft doc → 422 (existing `assertDraft`).
- Issue exceeding on_hand → 422 naming the sku_id + shortfall (no mutation; transaction rollback).
- Idempotency: like the other WMS types, replay-safety comes from the one-way `draft→confirmed` guard (a second confirm sees `confirmed` and 422s); `issue()` itself is not ref-idempotent, consistent with `receipt()`.

## Testing

Feature tests (`app/tests/Feature/Inventory/`):
1. Create goods-issue draft (API) → 201, status draft, code PXK, lines persisted.
2. Confirm goods-issue → on_hand decreases by qty; one `goods_issue` movement per line with correct `balance_after`; status confirmed; `GoodsIssueConfirmed` dispatched.
3. Negative-stock guard: confirm issuing more than on_hand → 422 names sku_id; no movement; on_hand unchanged.
4. Immutability: confirm an already-confirmed issue → 422.
5. Cancel draft → status cancelled, no stock effect.
6. **Cross-tenant SKU isolation** (security): API-key PAT for tenant A submits tenant B's sku_id to goods-issues and goods-receipts → 422 naming the id; no movement/stock change for either tenant.
7. Permission: user without `inventory.adjust` → 403.

Note: no JS test runner — the web tab gates on `npm run typecheck`/`build`. Repo baseline: ~pre-existing test failures unrelated to inventory; only new/related tests must pass.

## Cross-cutting

- **Migration required** (dev auto; prod `RUN_MIGRATIONS=false` → run `php artisan migrate` manually after deploy): `goods_issues` + `goods_issue_items`.
- No data backfill.
- No new permission to seed (reuses `inventory.adjust`).
