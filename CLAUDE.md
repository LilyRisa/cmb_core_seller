# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

OmniSell / CMBcoreSeller — a multi-marketplace selling SaaS for Vietnam (sync orders, inventory, shipping labels, reconciliation across TikTok Shop, Lazada; Shopee pending API). Modular-monolith **Laravel 11 + React (Vite) SPA** in one repo. User-facing strings are Vietnamese; code/DB/routes/identifiers are English.

## Read first (non-negotiable)

`docs/` is the **source of truth** — read the relevant doc before coding. Start at [`docs/README.md`](docs/README.md) (the "luật vàng" / golden rules), then [`docs/01-architecture/extensibility-rules.md`](docs/01-architecture/extensibility-rules.md) (Connector/Registry pattern) and [`docs/01-architecture/modules.md`](docs/01-architecture/modules.md) (module map + dependency rules). Big features require a spec in `docs/specs/` first; architecture decisions get an ADR in `docs/01-architecture/adr/`. **Change the docs before the code, not after.**

## Gotchas that will trip you up

- **All PHP/Node commands run from `app/`, not the repo root.** The repo root holds only `docker-compose*.yml`, `docs/`, and reference SDKs. `cd app` before `composer`, `artisan`, `npm`, `vendor/bin/*`.
- **PSR-4 namespace `CMBcoreSeller\` maps to `app/app/`** (the `laravel/laravel` name in `composer.json` is vestigial). So `CMBcoreSeller\Modules\Orders\Services\X` lives at `app/app/Modules/Orders/Services/X.php`.
- **`app/.env` is committed** (private repo) — clone and run, no `cp .env.example` or `key:generate` needed for dev. Defaults to SQLite + database queue. Prod-only secrets go in the repo-root `./.env` (gitignored); never reuse the dev `APP_KEY` in prod.
- Use `config()`, never `env()`, outside config files. Toggle marketplaces/carriers/payments via `config/integrations.php` + the matching `INTEGRATIONS_*` env CSV. Tenant/system-dynamic settings come from the DB via the `system_setting()` helper (autoloaded from `app/Modules/Settings/helpers.php`).

## Commands (run from `app/`)

```bash
# Dev — no Docker (SQLite, zero-setup): runs serve + queue + pail + vite concurrently
composer dev

# Dev — full stack (postgres, redis, horizon, minio, gotenberg) — from repo ROOT:
docker compose up -d --build           # base + override auto-merge; app on :8000, horizon /horizon

# Quality gate — mirrors CI (.github/workflows/ci.yml); all must pass to merge
vendor/bin/pint --test                 # PHP format check (vendor/bin/pint to auto-fix)
vendor/bin/phpstan analyse              # Larastan level 5 + phpstan-baseline.neon
php artisan test                        # PHPUnit (CI adds --coverage --min=60)
npm run lint && npm run typecheck && npm run build   # frontend (ESLint + tsc + vite)

# Run a single test
php artisan test --filter=OrderUpsertTest
php artisan test tests/Feature/Orders/OrderControllerTest.php

# DB
php artisan migrate --seed              # seed gives owner@demo.local / password
```

## Architecture

**Modular monolith.** Domain code lives in self-contained modules at `app/app/Modules/<Name>/` (e.g. Orders, Channels, Inventory, Fulfillment, Billing, Messaging — see [`docs/01-architecture/modules.md`](docs/01-architecture/modules.md) for the full map). Each module owns: `Contracts/`, `Database/Migrations/`, `Events/`, `Http/{Controllers,Requests,Resources,routes.php}`, `Jobs/`, `Listeners/`, `Models/`, `Policies/`, `Services/`, and a `<Name>ServiceProvider`. Providers are registered in `app/bootstrap/providers.php`; each one `loadMigrationsFrom` its own `Database/Migrations`, optionally loads `Http/routes.php`, and binds `Contracts/X → Services/Y`.

**Module dependency rules (PR-blocking):** modules communicate **only** through `Contracts/` interfaces or domain events — never `use` another module's `Services/` internals. `Tenancy` is the base everyone may depend on; `Reports` is read-only; no cyclic deps. When unsure which module a feature belongs to, see §4 of the modules doc.

**Integration layer** (`app/app/Integrations/`, namespace `CMBcoreSeller\Integrations`) is **separate from modules** and follows a Connector + Registry pattern across five axes: `Channels`, `Carriers`, `Payments`, `Messaging`, `Ai`. The supreme rule: **core never knows a marketplace/carrier name.** Adding a marketplace = one connector class implementing `ChannelConnector` + one `register('<code>', XConnector::class)` line + a `config/integrations.php` block — never editing core. Connectors map raw payloads to **standard DTOs** and throw `UnsupportedOperation` for methods a provider lacks (checked via a capability map). Never add `if ($provider === 'tiktok')` in core; the integration layer must not import `app/Modules/*` beyond standard DTOs/interfaces. Full checklists in `extensibility-rules.md`.

**Invariants:** single source of truth — inventory = master SKU; order status = the standard state machine (`app/app/Support/Enums/StandardOrderStatus.php`). Every business table carries `tenant_id`; models use the `BelongsToTenant` trait (global scope + auto-set) — no unscoped queries. Webhooks are untrusted, so there is always a polling backup, and **all sync jobs are idempotent**.

## HTTP surfaces & API conventions

- **Routes:** central `app/routes/api.php` plus per-module `app/Modules/<X>/Http/routes.php`. Webhooks at `app/routes/webhook.php` (no CSRF/auth — signature-verified). OAuth callbacks + SPA shells in `app/routes/web.php`.
- **API:** `/api/v1/...`, Sanctum **cookie** SPA auth (`GET /sanctum/csrf-cookie` first), current tenant via `X-Tenant-Id` header (`tenant` middleware). Response envelope is `{ "data": ..., "meta": ... }` / `{ "error": { "code", "message", "trace_id", "details" } }`, normalized centrally in `app/bootstrap/app.php`. Money = integer VND (no floats); timestamps ISO-8601 UTC; status fields return standard `code` + `status_label` + `raw_status`. Controllers stay thin: FormRequest → one Service → API Resource. Conventions in [`docs/05-api/conventions.md`](docs/05-api/conventions.md); new endpoints must be added to `docs/05-api/endpoints.md`.

## Frontend

React 18 + Vite + Ant Design + React Router. **Two separate bundles:** `app/resources/js/app.tsx` (user app, served by the SPA catch-all) and `app/resources/js/admin.tsx` (admin app under `/admin/*`). Server state goes through **TanStack Query**, UI state through **Zustand**; all API calls funnel through `lib/api.ts` (axios + envelope-aware error interceptor). `features/*` mirror backend modules 1-to-1; logic lives in hooks, components stay dumb.

## Infrastructure

Queues run on **Laravel Horizon** (Redis in prod; `queue:work`/`sync` for dev/tests). PDF rendering (shipping labels, print jobs) via **Gotenberg**; object storage via **MinIO/S3** (Flysystem); errors to **Sentry**. Large tables partition by month (`app/app/Support/Database/*`). Prod is the standalone `docker-compose.prod.yml` (Portainer-friendly) — see [`docs/07-infra/`](docs/07-infra/).
