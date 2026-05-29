# Automation Flow Builder — S3: Flows CRUD API + drag-and-drop canvas

> Implement task-by-task, commit after each (project preference). Split into **S3a (backend API)** then **S3b (frontend builder)**. Spec: `docs/superpowers/specs/2026-05-28-facebook-automation-flow-builder-design.md` §5.4, §8.

**Goal:** Give owners/staff a UI to create, edit (visually), validate, publish, pause, duplicate and delete automation flows. S1 built the engine + data model; S2 added postback/interactive. S3 adds the **HTTP CRUD + publish-validation API** and the **reactflow canvas** that emits the exact `graph` jsonb the engine already runs.

**Invariants:** RBAC **reuses** `messaging.rule.manage` (mutate) + `messaging.view` (read) — no new permission (spec §2). Tenant scoping is automatic via `BelongsToTenant` + the `tenant` middleware (controllers query `AutomationFlow::query()` directly, no `withoutGlobalScope`). Core never names a provider. User never edits raw JSON. Facebook-only v1 (provider defaults `facebook_page`).

---

## S3a — Backend: flows CRUD + validation API

### Task 1 — Soft delete migration + model
- Create `app/app/Modules/Messaging/Database/Migrations/2026_05_29_100003_add_deleted_at_to_automation_flows.php` adding `$table->softDeletes();` (reversible `dropColumn('deleted_at')`).
- `AutomationFlow` model: add `use SoftDeletes;` (matches `AutoReplyRule`/`MessageTemplate`).
- Run migrate. Commit.

### Task 2 — `FlowGraphValidator` service (+ unit tests)
Create `app/app/Modules/Messaging/Services/Flows/FlowGraphValidator.php`. Pure logic, no DB. `validate(AutomationFlow $flow): array` returns a list of errors `[{ node_id?: string, code: string, message: string }]` (empty = valid). Rules (spec §5.4), all DB-agnostic, reuse `FlowGraph`:
- `no_trigger` / `multiple_triggers`: exactly one `trigger` node.
- `unreachable`: every node reachable from the trigger via edges (BFS). Report each unreachable node id.
- `unknown_node`: every `node.type` is in the `NodeExecutorRegistry` (inject it) — unknown type ⇒ error on that node.
- `dangling_edge`: every edge `source`/`target` references an existing node.
- `wait_no_exit`: `wait_reply` nodes need ≥1 outgoing edge.
- `buttons_no_exit` / `button_edge_missing`: a `send_buttons` node needs, for each `postback` button, an outgoing edge whose `sourceHandle === button.id`; and ≥1 outgoing edge overall.
- `interactive_unsupported`: if the graph contains a `send_buttons` node, the flow's `provider` connector must implement `InteractiveMessagingConnector` + `supports('outbound.interactive')` (resolve via `MessagingRegistry`; capability-name check, not provider name).
- (Templates/thread-type checks deferred to S4 — no templates yet.)
Unit test `tests/Unit/Messaging/Flows/FlowGraphValidatorTest.php`: valid graph → []; each rule → the right error code. Commit.

### Task 3 — `AutomationFlowController` + routes (+ feature tests)
Create `app/app/Modules/Messaging/Http/Controllers/AutomationFlowController.php` mirroring `AutoReplyRuleController` (thin, inline `validate()`, private `present()`, `Gate::authorize`, `AuditLog::record`, `{data}` / `{data:[],meta}` envelope). Endpoints (add to `app/app/Modules/Messaging/Http/routes.php` in the existing tenant group):
- `GET  automation-flows` — index (paginate; `messaging.view`).
- `GET  automation-flows/{id}` — show (`messaging.view`).
- `POST automation-flows` — store; forces `status=draft`, `provider` default `facebook_page` (`messaging.rule.manage`).
- `PATCH automation-flows/{id}` — update `name|trigger_type|trigger_config|graph|enabled` (NOT status); editing a published flow keeps it active but bumps `version` (`messaging.rule.manage`).
- `DELETE automation-flows/{id}` — soft delete (`messaging.rule.manage`).
- `POST automation-flows/{id}/validate` — dry-run → `{ data: { valid: bool, errors: [...] } }` (`messaging.rule.manage`).
- `POST automation-flows/{id}/publish` — run validator; valid ⇒ `status=active`; invalid ⇒ `422 { error: { code: 'flow_invalid', details: { errors } } }` (`messaging.rule.manage`).
- `POST automation-flows/{id}/pause` — `status=paused` (`messaging.rule.manage`).
- `POST automation-flows/{id}/duplicate` — clone as a new `draft` (`name + " (bản sao)"`, version 1) (`messaging.rule.manage`).

Validation payload: `name` string≤160; `provider` sometimes string; `trigger_type` in the 5 triggers; `trigger_config` nullable array (+ `post_ids[]`, `keywords[]`, `match in:any,all`); `graph` nullable array with `graph.nodes` array + `graph.edges` array; `enabled` nullable boolean.

`present()`: id, name, provider, status, trigger_type, trigger_config, graph, version, enabled, created_at, updated_at.

Feature test `tests/Feature/Messaging/Flows/AutomationFlowApiTest.php` mirroring `MessagingTemplateTest` setup (seed plans, tenant + Owner, X-Tenant-Id header, activate Pro): owner CRUD happy paths; a non-managing role (StaffOrder) gets 403 on mutate, 200 on read; publish with an invalid graph → 422 + errors; publish with a valid graph → status active; duplicate creates a draft copy. Commit.

### Task 4 — S3a quality gate
`vendor/bin/pint`, `vendor/bin/phpstan analyse` (no new errors on S3a files), `php artisan test --filter=Messaging`. Commit.

---

## S3b — Frontend: list + reactflow builder

> No JS test runner exists. Gate = `npm run lint && npm run typecheck && npm run build`, plus manual/Playwright check of the golden path. Use `@ant-design/icons` (never emoji); prefer `Radio.Group`/`Segmented` over `<Select>`; never expose raw JSON.

### Task 5 — dependency + API hooks
- `npm i @xyflow/react` (reactflow v12) in `app/`.
- Create `app/resources/js/lib/messagingFlows.tsx`: types (`AutomationFlow`, `FlowGraph`, `FlowNode`, `FlowEdge`, node-data shapes) + hooks following `messagingConfig.tsx` (`useScopedApi`, `useCurrentTenantId`): `useFlows`, `useFlow(id)`, `useSaveFlow` (create/patch), `useDeleteFlow`, `useDuplicateFlow`, `usePublishFlow`, `usePauseFlow`, `useValidateFlow`. Query keys `['messaging','flows',tenantId]`, `['messaging','flow',id]`.

### Task 6 — list page
- `app/resources/js/pages/MessagingFlowsPage.tsx`: AntD `Table` (name, trigger label, status `Tag`, enabled `Switch`, updated_at; actions edit/duplicate/delete gated on `useCan('messaging.rule.manage')`). "Tạo kịch bản" button → creates a draft then navigates to the editor. Mirror `MessagingTemplatesPage`/`MessagingAutoRulesPage`.
- Register route `messaging/flows` in `app.tsx`; add nav entries in `MessagingNav.tsx` + `AppLayout.tsx` ("Kịch bản tự động").

### Task 7 — builder shell (canvas + palette + topbar)
- `app/resources/js/pages/MessagingFlowEditorPage.tsx` + `app/resources/js/features/messaging/flow/` components:
  - `FlowCanvas.tsx` — `<ReactFlow>` with custom node types; controlled `nodes`/`edges`; `onConnect`/`onNodesChange`/`onEdgesChange`; minimap + controls + background; node ids via `nanoid`.
  - `NodePalette.tsx` — left rail; drag node types onto the canvas (groups: Bắt đầu, Gửi tin, Hỏi/Chờ, Rẽ nhánh, Kết thúc — S5 adds AI/Action). Drop creates a node with default `data`.
  - `FlowTopbar.tsx` — flow name (editable), trigger config (`Segmented` for type; `comment_on_post` post-picker deferred to S4 → show "sắp có"), Save draft / Publish / Pause buttons; on Publish, call `usePublishFlow`, on 422 highlight the returned `errors[].node_id` nodes red.
  - Custom node components per type (`TriggerNode`, `SendMessageNode`, `SendButtonsNode`, `WaitReplyNode`, `ConditionNode`, `EndNode`) — compact cards, `@ant-design/icons`, multiple source handles where needed (condition: `match`/`no_match`; send_buttons: one handle per button + a default handle).
- Editor loads `useFlow(id)` → seeds nodes/edges from `graph`; "Lưu nháp" serializes canvas → `graph` and calls `useSaveFlow`.

### Task 8 — node config drawers (all form-based)
- `NodeConfigDrawer.tsx` — right `Drawer` keyed by selected node type; per-type form:
  - send_message: textarea.
  - send_buttons: text + a button list editor (label + type Radio postback/url + url) — generates button `id` (nanoid) used as the source handle; carousel deferred to S4.
  - condition: keywords (tags input) + match `Radio.Group` any/all.
  - wait_reply/trigger/end: minimal.
  - Drawer writes back into the node's `data`; canvas re-renders handles (send_buttons handles follow the button list).

### Task 9 — S3b quality gate + browser check
- `npm run lint && npm run typecheck && npm run build` clean.
- Start dev server, exercise: create flow → drag nodes → connect → configure via drawers → save draft → publish (and see validation errors highlight). Report explicitly if any step can't be verified.
- Commit.

---

## Out of scope (S4/S5)
- Interactive template/carousel builder + post picker + `comment_on_post` trigger + public/private comment-reply node (S4).
- AI node, intent condition, collect-info, action (tag/assign/handoff), test-run sandbox, analytics (S5).
