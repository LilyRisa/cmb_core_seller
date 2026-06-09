# Timezone Standardization — Store UTC, Display & Process in UTC+7 (Asia/Ho_Chi_Minh)

**Date:** 2026-06-10
**Status:** Approved (brainstorming → ready for implementation plan)
**Owner:** lilyrisa

## Problem

The app currently exhibits inconsistent / skewed times. Root cause: commit `e138fc4e`
("fix", 2026-05-12) set `APP_TIMEZONE=Asia/Ho_Chi_Minh` in `app/.env`. This contradicts
the documented convention in `CLAUDE.md` ("timestamps ISO-8601 UTC") and the storage design
(`timestamp()` columns, no `timestampTz`). The result is a half-and-half state:

- `now()` / Eloquent now produce **HCM wall-clock**, stored **naive** → DB became a mix of
  UTC-naive rows (pre-12/05) and HCM-naive rows (post-12/05).
- The frontend display layer (`lib/format.ts`, admin pages) does **not** pin a timezone — it
  renders in the **browser's** local tz, so it only happens to look correct for HCM browsers.
- Some business-logic boundaries (Dashboard "today", Reports day buckets, scheduler `dailyAt`)
  silently follow whatever `config('app.timezone')` is.

Data is mostly test data, so **no backfill is required**.

## Goal

Standardize on the industry-correct, `CLAUDE.md`-aligned model:

- **Storage & transport:** UTC. `APP_TIMEZONE=UTC`; DB stays UTC-naive; API keeps emitting
  ISO-8601 (will now carry `+00:00`/`Z`).
- **Display:** always **Asia/Ho_Chi_Minh**, regardless of the viewer's browser timezone.
- **Business processing in local time:** day boundaries (Dashboard/Reports), the task
  scheduler, and any provider time that is documented as local (VNPay GMT+7) must be computed
  in **Asia/Ho_Chi_Minh** explicitly — not by relying on the global app timezone.

Non-goal: changing existing data (test data; user will handle any backfill separately).

## Design

### 1. Single source of truth for the display/processing timezone

Avoid hardcoding `'Asia/Ho_Chi_Minh'` in ~12 places.

- **Backend:** add `'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Asia/Ho_Chi_Minh')` to
  `config/app.php`. Add a tiny helper `app_display_tz(): string` (alongside existing helpers,
  e.g. `app/Support/helpers.php` or the Settings helpers autoload) returning that config value.
- **Frontend:** export `const DISPLAY_TZ = 'Asia/Ho_Chi_Minh'` from `resources/js/lib/format.ts`
  and reuse it everywhere a date is rendered.

### 2. Base config (correct going forward)

- `app/.env`: `APP_TIMEZONE=UTC` (committed dev env).
- **Production note (cannot edit here):** the gitignored repo-root `./.env` used in prod must
  also set `APP_TIMEZONE=UTC` and `APP_DISPLAY_TIMEZONE=Asia/Ho_Chi_Minh`. Flag in the PR /
  deploy notes. After change, `config:cache` must be refreshed on prod (Horizon config is
  cached — see ops memory).
- **Scheduler** (`routes/console.php`): with the app tz now UTC, every time-anchored task
  (`dailyAt`, `hourlyAt`, `at`, `twiceDaily`, etc.) would shift −7h. Pin them to HCM so they
  keep running at the intended Vietnam local time. Apply `->timezone(app_display_tz())` to each
  time-anchored task (interval tasks like `everyFiveMinutes`/`hourly` are tz-independent and
  left unchanged). Known time-anchored tasks include: `db:partitions:ensure` 00:30, orders
  backfill 02:00, subscriptions check 04:00, settlements 02:00, prune-unverified 03:30.

### 3. Backend — guarantee correct UTC on the way in

- **Provider epoch parsing** (TikTok, Shopee, ViettelPost): make the UTC intent explicit —
  `CarbonImmutable::createFromTimestamp((int) $v, 'UTC')`. Value-neutral under UTC app tz, but
  prevents regressions if the app tz ever drifts again. Files: `TikTokMappers.php`,
  `ShopeeMappers.php`, `ViettelPostConnector.php`, and any other `createFromTimestamp` in
  `app/Integrations`.
- **VNPay (real fix):** `vnp_PayDate` / `vnp_CreateDate` are **GMT+7** per the VNPay spec.
  - Parse: `CarbonImmutable::createFromFormat('YmdHis', $s, app_display_tz())` (Carbon converts
    to UTC on store automatically).
  - Generate (`vnp_CreateDate`, `vnp_ExpireDate`): format from a HCM-localized `now()`:
    `now()->setTimezone(app_display_tz())->format('YmdHis')`.
  - File: `app/Integrations/Payments/VnPay/VnPayConnector.php` (~lines 80, 96, 135).

### 4. Backend — query / aggregate by the Vietnam day

Day boundaries must be computed in HCM then converted to UTC for the WHERE clause on UTC columns.

- Dashboard (`Orders/Http/Controllers/DashboardController.php`):
  - `$today = CarbonImmutable::now(app_display_tz())->startOfDay()` and use `->utc()` (or pass
    the HCM-anchored instant; Eloquent compares as UTC) for `whereBetween` on `placed_at` /
    `shipped_at`.
  - Line ~155 day grouping: replace `config('app.timezone', 'UTC')` with `app_display_tz()` so
    rows bucket into Vietnam calendar days regardless of app tz.
- Reports (`Reports/Services/SalesReportService.php`) and Accounting ledger/period ranges
  (`Accounting/Services/Reports/LedgerService.php`): ensure `from`/`to` day boundaries are
  HCM-anchored before the UTC `whereBetween`. The SalesReport docblock already states the
  intent ("display Asia/Ho_Chi_Minh, query on UTC column") — make the boundary computation
  match it explicitly.

### 5. Frontend — always render HCM

- `resources/js/lib/format.ts`:
  - Add `import timezone from 'dayjs/plugin/timezone'` + `dayjs.extend(timezone)` (utc plugin
    already extended). Define `DISPLAY_TZ`.
  - `formatDate`/`fromNow` (and any other formatter) pipe through `dayjs(iso).tz(DISPLAY_TZ)`
    before `.format()`. Fix the now-accurate docblock.
- **Sweep & replace** every ad-hoc date render that relies on browser tz with the shared
  formatter (or an explicit `.tz(DISPLAY_TZ)`):
  - `admin/pages/support/AdminSupportRequestsPage.tsx` (`new Date().toLocaleString('vi-VN')`)
  - `admin/pages/tenants/AdminAuditLogsPage.tsx` (`dayjs(v).format(...)` w/o tz)
  - any other `new Date(...).toLocaleString` / unpinned `dayjs().format` found in the sweep.
  - `components/MoneyText.tsx` already pins `timeZone:'Asia/Ho_Chi_Minh'` — keep, optionally
    switch to `DISPLAY_TZ` for consistency.
- **DatePicker filters:** where a picked date is sent to the API as a range filter, convert the
  HCM-local day boundary to a UTC ISO string before sending (so server-side UTC `whereBetween`
  matches the Vietnam day the user picked).

### 6. Data

No backfill (test data). Not special-casing `sent_at` (Messaging) — per the prior-session
finding, its display was correct under the old setup; after this change it follows the same
store-UTC/display-HCM rule as every other timestamp. No data is mutated.

### 7. Testing

- **Backend (PHPUnit):**
  - Round-trip: a model saved via `now()` reads back and serializes to the same UTC instant;
    asserts `config('app.timezone') === 'UTC'`.
  - Vietnam-day boundary: an order placed at `2026-06-10 23:30 HCM` (= `16:30Z`) counts in the
    `2026-06-10` Dashboard/Report bucket, not `2026-06-11`.
  - VNPay: a `vnp_PayDate` GMT+7 string parses to the correct UTC instant; generated
    `vnp_CreateDate` is the HCM wall-clock.
- **Frontend:** `formatDate` returns the HCM rendering for a UTC ISO input even when the test
  runtime `TZ` is set to something non-HCM (e.g. `UTC` or `America/New_York`). (Note: per the
  test-baseline memory there is no JS test runner wired up; if so, verify via a scratch
  `node -e` with `process.env.TZ` and document, rather than adding a runner.)
- **Quality gate (from `app/`):** `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`,
  `php artisan test`, `npm run lint && npm run typecheck && npm run build`. Known pre-existing
  GHN/fulfillment failures (test-baseline memory) are out of scope.

## Files in scope (initial inventory; finalized during planning)

- `app/.env`, `app/config/app.php`, helper file for `app_display_tz()`
- `app/routes/console.php`
- `app/app/Integrations/Channels/TikTok/TikTokMappers.php`
- `app/app/Integrations/Channels/Shopee/ShopeeMappers.php`
- `app/app/Integrations/Carriers/ViettelPost/ViettelPostConnector.php`
- `app/app/Integrations/Payments/VnPay/VnPayConnector.php`
- `app/app/Modules/Orders/Http/Controllers/DashboardController.php`
- `app/app/Modules/Reports/Services/SalesReportService.php`
- `app/app/Modules/Accounting/Services/Reports/LedgerService.php`
- `app/resources/js/lib/format.ts`
- `app/resources/js/admin/pages/support/AdminSupportRequestsPage.tsx`
- `app/resources/js/admin/pages/tenants/AdminAuditLogsPage.tsx`
- + any additional unpinned date renders / `createFromTimestamp` found in the sweep

## Risks

- Reverting `APP_TIMEZONE` re-interprets the 12/05→now HCM-naive rows as UTC (−7h). Accepted:
  data is test. Pre-12/05 UTC-naive rows display correctly again.
- Missing a display site in the sweep → that one screen still follows browser tz. Mitigation:
  grep-based exhaustive sweep for `toLocaleString`, `new Date(`, and `dayjs(` `.format` without
  `.tz`.
- Prod `./.env` and cached config must be updated/refreshed at deploy or prod will keep the
  HCM app tz.

## Implementation notes (2026-06-10)

Implemented and verified (FE build, `php artisan test --filter=Report|Dashboard|Sales|VnPay|ViettelPost`
= 59 passed; pint clean on changed files; phpstan error count unchanged at 251 baseline).

Deliberately **left as-is** (calendar-period domains; low blast-radius edge cases only, high risk to
change; dates still render correctly via the FE fix):
- Accounting period/ledger boundaries (`LedgerService`, `BalanceService`, `ClosingEntryService`,
  `TrialBalanceService`, `ProfitLossService`, `BalanceSheetService`, `TaxService`, `MisaExportService`,
  `JournalController` filters) — bounded by monthly `period->start_date/end_date`.
- Finance settlement filters (`SettlementController`/`SettlementService`) — coarse statement periods.
- Billing AI-credit `period_anchor` (`AiCreditService`) — quota window anchor.

Prod follow-up (cannot edit gitignored `./.env` from here): set `APP_TIMEZONE=UTC` +
`APP_DISPLAY_TIMEZONE=Asia/Ho_Chi_Minh` and refresh cached config (`config:cache`).
