# CMB CORE SELLER — Data Flow Diagram (DFD) Specification

**Document Type:** Architecture & Data Flow Specification
**Purpose:** Security Review Submission (Lazada Open Platform)
**System Name:** CMB CORE SELLER
**Version:** 1.0
**Date:** 2026-05-14

---

## System Overview

CMB CORE SELLER is a web-based multi-channel e-commerce management platform integrated with Lazada Open Platform APIs. It centralizes order management, product catalog synchronization, inventory updates, shipping/tracking, and reporting for merchants operating on the Lazada marketplace.

**Technology Stack**
- Application Framework: Laravel (PHP)
- Containerization: Docker (multi-container deployment)
- Hosting: VPS Cloud Infrastructure
- Edge Protection: Cloudflare (WAF, DDoS mitigation, TLS termination)
- Database: PostgreSQL (centralized, encrypted at rest)
- Cache / Queue: Redis (sessions, job queue, rate limiting)
- Transport: HTTPS / TLS 1.2+

---

## 1. System Components

### 1.1 External Entities

| ID  | Entity                       | Role                                                                 |
|-----|------------------------------|----------------------------------------------------------------------|
| E1  | Seller / Operator (End User) | Authenticated merchant staff using the web dashboard                 |
| E2  | System Administrator         | Privileged user managing accounts, roles, and integrations           |
| E3  | Lazada Open Platform API     | External marketplace API (orders, products, logistics, finance)      |
| E4  | Lazada OAuth Authorization Server | External identity provider for Lazada seller authorization      |
| E5  | Shipping / Logistics Carrier (via Lazada) | Carrier data accessed indirectly through Lazada APIs    |
| E6  | Cloudflare Edge Network      | Reverse proxy, WAF, TLS termination, bot mitigation                  |

### 1.2 Internal Application Components

| ID   | Component                    | Responsibility                                                       |
|------|------------------------------|----------------------------------------------------------------------|
| C1   | Web UI (Laravel Blade / SPA) | Renders dashboard, forms, and reports for end users                  |
| C2   | API Gateway / Route Layer    | Laravel HTTP routing, request validation, middleware pipeline        |
| C3   | Authentication Module        | Login, session, MFA, password hashing (bcrypt/argon2)                |
| C4   | Authorization / RBAC Module  | Role and permission enforcement for routes and resources             |
| C5   | Lazada Integration Service   | OAuth handshake, token refresh, signed API calls to Lazada           |
| C6   | Order Sync Worker            | Pulls/pushes orders; reconciles statuses; persists to PostgreSQL     |
| C7   | Product & Inventory Sync Worker | Synchronizes SKUs, prices, stock levels with Lazada               |
| C8   | Shipping & Tracking Service  | Fetches AWB, tracking events, fulfillment updates                    |
| C9   | Internal Dashboard Service   | Aggregates metrics, KPIs, charts, exports                            |
| C10  | Queue Dispatcher (Laravel Horizon / Redis) | Background job orchestration                           |
| C11  | Audit & Logging Service      | Application logs, audit trail, security events                       |
| C12  | Monitoring Agent             | Health checks, metrics, alerting                                     |
| C13  | Secrets Manager              | Stores API client IDs, secrets, encryption keys (env / vault)        |

### 1.3 Data Stores

| ID   | Store                | Content                                                              |
|------|----------------------|----------------------------------------------------------------------|
| D1   | PostgreSQL (Primary) | Users, roles, orders, products, inventory, shipments, audit log      |
| D2   | Redis                | Sessions, queues, cache, rate-limit counters                         |
| D3   | Object / File Store  | Exported reports, invoice PDFs, product images (if applicable)       |
| D4   | Encrypted Secret Store | OAuth tokens, refresh tokens, API credentials (encrypted at rest)  |
| D5   | Log Store            | Application, access, and audit logs (rotated, retained)              |

---

## 2. Data Flow Steps

### 2.1 Authentication Flow (Seller / Operator Login)

1. **E1 → Cloudflare (E6):** User submits credentials via HTTPS to the web dashboard URL.
2. **E6 → C2:** Cloudflare terminates TLS, applies WAF rules, forwards request to the API Gateway.
3. **C2 → C3:** Authentication module validates credentials against D1 (hashed passwords).
4. **C3 → D2:** On success, a signed session token is issued and stored in Redis; CSRF token bound to the session.
5. **C3 → C4:** Role and permission claims are loaded from D1 for the user.
6. **C2 → E1:** HTTPS response with Secure, HttpOnly, SameSite session cookie.
7. **C11:** Auth event (success/failure, IP, user agent) written to audit log (D5).

### 2.2 Lazada OAuth Authorization Flow

1. **E1 → C1:** Seller clicks "Connect Lazada Store" in the dashboard.
2. **C5 → E4:** Application redirects the seller browser to Lazada OAuth authorization endpoint with `client_id`, `redirect_uri`, `state`.
3. **E1 → E4:** Seller authenticates on Lazada and grants scopes.
4. **E4 → C5:** Lazada redirects back with `authorization_code` to the registered callback URL (HTTPS).
5. **C5 → E4:** Server-to-server token exchange (HTTPS) using `client_secret` retrieved from D4.
6. **C5 → D4:** Access token, refresh token, expiry, and shop metadata stored encrypted at rest.
7. **C5 → C10:** Initial backfill jobs (orders, products) enqueued in Redis.
8. **C11:** Authorization event recorded in audit log (D5).

### 2.3 Order Synchronization Flow

1. **C10 → C6:** Scheduled / event-driven job triggers the Order Sync Worker.
2. **C6 → D4:** Worker loads the active Lazada access token (decrypted in memory only).
3. **C6 → E3:** Signed HTTPS request to Lazada `/orders/get` (and related endpoints) with HMAC signature.
4. **E3 → C6:** Response returns order list and line items over TLS.
5. **C6 → D1:** Orders, items, buyer-masked address, totals, and status are upserted into PostgreSQL.
6. **C6 → C11:** Sync result, counts, and any API errors logged.
7. **E1 → C1 → C9:** Seller views orders via the dashboard; reads come from D1, not direct from Lazada.
8. **E1 → C6 → E3 (write-back):** Seller actions (pack, cancel, ready-to-ship) are validated by C4, then forwarded to Lazada APIs by C6.

### 2.4 Product & Inventory Synchronization Flow

1. **C10 → C7:** Triggered by cron, webhook, or user action.
2. **C7 → E3:** Pulls product catalog and stock via Lazada Product / Inventory APIs (HTTPS, signed).
3. **C7 → D1:** Products, SKUs, prices, stock levels reconciled in PostgreSQL.
4. **E1 → C1 → C7:** Seller edits price/stock; change is queued (C10).
5. **C7 → E3:** Push update to Lazada `/product/price/update`, `/product/stock/update`.
6. **E3 → C7:** Acknowledgement; failure cases retried with exponential backoff.
7. **C11:** Mutation logged with before/after values for audit (D5).

### 2.5 Shipping & Tracking Flow

1. **C10 → C8:** Periodic tracking refresh job is dispatched.
2. **C8 → E3:** Calls Lazada Logistics / Shipment APIs for AWB and tracking events.
3. **E3 → C8:** Returns waybill numbers, carrier names, status timeline.
4. **C8 → D1:** Shipment records updated; tracking history appended.
5. **E1 → C1:** Seller views shipment timeline; data read from D1.
6. **C8 → C11:** Shipping events logged.

### 2.6 Internal Dashboard Operations

1. **E1 → C1:** Authenticated request to dashboard views (orders, sales, inventory, finance).
2. **C2 → C4:** RBAC check on each route and resource.
3. **C9 → D1 / D2:** Aggregated queries; hot KPIs cached in Redis.
4. **C1 → E1:** Rendered HTML / JSON over HTTPS.
5. **E1 → C9:** Export requests (CSV/PDF) handled asynchronously via C10; artifacts stored in D3 with signed, expiring URLs.

### 2.7 Logging and Monitoring Flow

1. **All components → C11:** Structured logs (JSON) emitted for requests, jobs, errors, security events.
2. **C11 → D5:** Logs persisted, rotated, and retained per policy.
3. **C12:** Scrapes health endpoints, queue depth, DB latency; emits alerts to operators.
4. **Sensitive fields (tokens, passwords, full card data) are redacted before write.**

---

## 3. Security Controls

### 3.1 Edge & Network
- Cloudflare WAF with managed rule sets, OWASP CRS, and custom rules.
- DDoS mitigation, bot scoring, geo / ASN blocking where applicable.
- TLS 1.2+ enforced; HSTS enabled; only HTTPS reachable publicly.
- VPS firewall restricts inbound traffic to Cloudflare IP ranges; admin SSH gated by allowlist + key auth.

### 3.2 Application
- CSRF tokens on all state-changing requests.
- Server-side input validation; output encoding to prevent XSS.
- Parameterized queries (Eloquent / PDO) — no string-built SQL.
- File upload validation (MIME, size, extension); files stored outside web root.
- Strict CORS policy; same-origin for the dashboard.
- Rate limiting per IP and per user (Redis token bucket).
- Security headers: `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`.

### 3.3 Authentication & Session
- Password hashing with bcrypt / argon2id.
- Optional MFA (TOTP) for privileged roles.
- Session cookies: `Secure`, `HttpOnly`, `SameSite=Lax`/`Strict`.
- Idle and absolute session timeouts; session rotation on privilege change.
- Brute-force protection and account lockout thresholds.

### 3.4 Authorization (RBAC)
- Roles: `Admin`, `Manager`, `Operator`, `Finance`, `Viewer` (least privilege).
- Permissions enforced at route, controller, and data-row level where applicable.
- All sensitive actions (token revoke, role change, mass export) require `Admin`.
- Change in roles triggers audit-log entries.

### 3.5 Secret & Key Management
- Lazada `client_id` / `client_secret` stored only in D4 (encrypted env / vault), never in source.
- Access tokens and refresh tokens encrypted at rest with AES-256.
- Encryption keys rotated on a defined schedule.
- `.env` and secret files excluded from version control.

### 3.6 Lazada API Communication
- All requests over HTTPS with required Lazada HMAC signature.
- Per-shop token isolation; tokens never logged or returned to the browser.
- Refresh-token rotation handled server-side.
- Webhook payloads (if subscribed) verified by signature and replay-window timestamp.

### 3.7 Monitoring & Auditing
- Immutable audit log for auth, RBAC, token, and data-mutation events.
- Alerts on: failed-login spikes, token-refresh failures, abnormal API error rates, queue backlog.
- Log retention defined per compliance policy.

---

## 4. Storage Architecture

| Store | Purpose                              | Encryption                          | Access                                  |
|-------|--------------------------------------|--------------------------------------|------------------------------------------|
| D1 PostgreSQL | Authoritative business data  | TLS in transit; AES-256 at rest      | Application service account only         |
| D2 Redis      | Sessions, queues, cache      | TLS in transit; instance-private     | Application service account only         |
| D3 Object Store | Reports, exports, assets   | TLS in transit; AES-256 at rest      | Signed, time-limited URLs                |
| D4 Secret Store | OAuth tokens, API secrets  | AES-256 envelope encryption          | Read by integration service only         |
| D5 Log Store    | Audit and application logs | At-rest encryption; integrity hashed | Operators with `Admin` role              |

**Backups:** Encrypted, off-host, retained per policy; restoration tested periodically.
**Network:** Database and Redis bound to private interfaces inside the Docker network; not exposed publicly.

---

## 5. External Integrations

| Integration             | Direction        | Protocol | Auth                      | Data Exchanged                                  |
|-------------------------|------------------|----------|----------------------------|--------------------------------------------------|
| Lazada Open Platform API| Outbound + Inbound (webhooks) | HTTPS    | OAuth 2.0 + HMAC signature | Orders, products, inventory, shipments, finance |
| Lazada OAuth Server     | Outbound (redirect + token exchange) | HTTPS    | `client_id` / `client_secret` | Authorization code, access/refresh tokens       |
| Cloudflare              | Inbound proxy    | HTTPS    | Origin certificate / token | All public HTTP(S) traffic                       |
| Email / Notification Provider (if used) | Outbound | HTTPS / SMTPS | API key            | Transactional notifications                      |

**No PII or token material is sent to third parties other than Lazada under the authorized scope.**

---

## 6. Trust Boundaries

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                              UNTRUSTED ZONE                                  │
│  E1 Seller Browser     E3 Lazada API     E4 Lazada OAuth     E5 Carriers     │
└────────────────────────────────┬─────────────────────────────────────────────┘
                                 │ HTTPS / TLS 1.2+
┌────────────────────────────────▼─────────────────────────────────────────────┐
│                          EDGE BOUNDARY (E6 Cloudflare)                       │
│           WAF · DDoS · TLS termination · Bot mitigation · Rate limit         │
└────────────────────────────────┬─────────────────────────────────────────────┘
                                 │ Cloudflare → Origin (TLS, IP allowlist)
┌────────────────────────────────▼─────────────────────────────────────────────┐
│                       APPLICATION BOUNDARY (VPS / Docker)                    │
│   C1 Web UI · C2 Gateway · C3 AuthN · C4 RBAC · C5 Lazada Integration        │
│   C6 Order · C7 Product · C8 Shipping · C9 Dashboard · C10 Queue             │
│   C11 Audit · C12 Monitoring                                                 │
└────────────────────────────────┬─────────────────────────────────────────────┘
                                 │ Private Docker network only
┌────────────────────────────────▼─────────────────────────────────────────────┐
│                            DATA BOUNDARY (Private)                           │
│        D1 PostgreSQL · D2 Redis · D3 Object Store · D4 Secrets · D5 Logs     │
└──────────────────────────────────────────────────────────────────────────────┘
```

- **Untrusted ↔ Edge:** All traffic must traverse Cloudflare; origin rejects non-Cloudflare sources.
- **Edge ↔ Application:** Authenticated TLS; security headers enforced.
- **Application ↔ Data:** Service-account credentials, private subnet only.
- **Application ↔ Lazada:** Outbound HTTPS with OAuth + HMAC; tokens stored only inside the Data boundary.

---

## 7. Sensitive Data Handling

| Data Class                       | Examples                                  | Handling                                                                 |
|----------------------------------|-------------------------------------------|--------------------------------------------------------------------------|
| Authentication credentials       | Passwords, MFA secrets                    | Hashed (bcrypt/argon2id); never logged; never returned to client.        |
| Lazada tokens                    | Access token, refresh token               | AES-256 at rest in D4; in memory only during use; never logged.          |
| Personally Identifiable Info (PII)| Buyer name, address, phone (from orders) | Stored in D1 with at-rest encryption; access gated by RBAC; masked in UI where appropriate. |
| Business data                    | Orders, prices, stock                     | RBAC-controlled; exports require `Manager`+ role.                        |
| Logs                             | Request, audit, error                     | Sensitive fields redacted before write; access restricted.               |
| Backups                          | DB snapshots                              | Encrypted; restricted access; off-host storage.                          |

**Data minimization:** Only fields required for marketplace operations are retrieved from Lazada and persisted.
**Retention:** Order and audit data retained per regulatory and Lazada policy requirements; PII purged or anonymized after retention window.
**Transport:** Every hop — browser→edge→app→DB→Lazada — uses TLS.

---

## 8. Diagram Node Relationships

The following edge list is suitable for direct conversion into a visual DFD (Graphviz / Draw.io / Lucidchart).

### 8.1 Nodes

```
External:    E1, E2, E3, E4, E5, E6
Components:  C1, C2, C3, C4, C5, C6, C7, C8, C9, C10, C11, C12, C13
Stores:      D1, D2, D3, D4, D5
```

### 8.2 Edges (Source → Destination : Data / Purpose)

```
E1  -> E6   : HTTPS request (credentials, dashboard actions)
E2  -> E6   : HTTPS request (admin operations)
E6  -> C2   : Filtered HTTPS traffic (WAF-passed)
C2  -> C3   : Authentication request
C3  -> D1   : Read user record (hashed password, role)
C3  -> D2   : Create / read session
C3  -> C11  : Auth event log
C2  -> C4   : Authorization check
C4  -> D1   : Read role / permission
C2  -> C1   : Render dashboard
C1  -> E1   : HTTPS response (HTML / JSON)

C1  -> C5   : "Connect Lazada" initiation
C5  -> E4   : OAuth authorization redirect
E4  -> C5   : OAuth callback with authorization_code
C5  -> E4   : Token exchange (client_secret)
C5  -> D4   : Persist access/refresh tokens (encrypted)
C5  -> C10  : Enqueue initial sync jobs
C10 -> C6   : Dispatch order-sync job
C10 -> C7   : Dispatch product/inventory job
C10 -> C8   : Dispatch shipping/tracking job

C6  -> D4   : Read decrypted Lazada token
C6  -> E3   : Signed HTTPS call (orders)
E3  -> C6   : Order payload
C6  -> D1   : Upsert orders
C6  -> C11  : Sync audit log

C7  -> E3   : Signed HTTPS call (products, stock, price)
E3  -> C7   : Catalog / stock payload
C7  -> D1   : Upsert products & inventory
C1  -> C7   : Seller price/stock edits
C7  -> E3   : Push update to Lazada
C7  -> C11  : Mutation audit log

C8  -> E3   : Signed HTTPS call (logistics)
E3  -> C8   : Shipping & tracking data (incl. data from E5 via Lazada)
C8  -> D1   : Update shipment records
C8  -> C11  : Shipping event log

C1  -> C9   : Dashboard / report request
C9  -> D1   : Aggregate queries
C9  -> D2   : Cached KPIs
C9  -> D3   : Store exported reports
C9  -> E1   : Signed URL for export download

C11 -> D5   : Write audit & application logs
C12 -> C2   : Health-check probes
C12 -> D1   : DB health metrics
C12 -> D2   : Queue depth metrics
C13 -> C5   : Provide Lazada client credentials at runtime
C13 -> D4   : Manage encryption keys

E6  -> [block] : Malicious / non-compliant traffic dropped at edge
```

### 8.3 Suggested Diagram Layers (top → bottom)

1. **External actors:** E1, E2 (left) · E3, E4, E5 (right)
2. **Edge:** E6 Cloudflare
3. **Presentation:** C1 Web UI · C2 API Gateway
4. **Security:** C3 AuthN · C4 RBAC · C13 Secrets
5. **Domain services:** C5 Lazada Integration · C6 Orders · C7 Products/Inventory · C8 Shipping · C9 Dashboard
6. **Infrastructure services:** C10 Queue · C11 Audit/Logging · C12 Monitoring
7. **Data layer:** D1 PostgreSQL · D2 Redis · D3 Object Store · D4 Secrets · D5 Logs

---

## Appendix A — Compliance Posture (Summary)

- **Encryption in transit:** TLS 1.2+ everywhere; HSTS enforced.
- **Encryption at rest:** AES-256 for database, object store, secrets, and backups.
- **Access control:** RBAC with least privilege; admin actions audited.
- **Secret hygiene:** No secrets in source control or logs; rotation policy defined.
- **Monitoring:** Centralized logging, alerting on security-relevant events.
- **Lazada compliance:** OAuth scopes minimized; tokens isolated per shop; signature verification on all API calls; no redistribution of Lazada data to unauthorized parties.

---

*End of document.*
