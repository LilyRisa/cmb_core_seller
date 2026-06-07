# Per-page Messaging Scoping Implementation Plan (SPEC 0035)

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development hoặc superpowers:executing-plans, task-by-task. Steps dùng checkbox.

**Goal:** Cho phép cấu hình tự động trả lời / kịch bản tự động / AI (knowledge + bật-tắt + auto-mode) **theo từng Facebook page** thay vì áp tất cả page; dữ liệu cũ giữ chạy "tất cả trang".

**Architecture:** Many-to-many `x ↔ channel_account` (pivot) + cờ `applies_all_pages` trên 3 bảng gốc; lọc page + ưu tiên page-specific ở các query chọn rule/flow/knowledge; AI bật-tắt/auto-mode chuyển sang `messaging_account_meta` (per-page). Provider + quota giữ tenant-wide. Chi tiết: `docs/specs/0035-per-page-messaging-automation-scoping.md`.

**Tech Stack:** Laravel 11 + PHPUnit, React + Vite + AntD, Postgres/SQLite. Lệnh chạy từ `app/`. Quality gate: pint --test, phpstan, php artisan test, npm run lint && typecheck && build.

**Nguyên tắc xuyên suốt:**
- `applies_all_pages=true` ⇒ áp mọi page (bỏ qua pivot). Dữ liệu cũ migrate về `true`.
- Lọc: `WHERE applies_all_pages OR EXISTS(pivot cho conv.channel_account_id)`.
- Ưu tiên: `orderByDesc(applies_all_pages = false)` (page-specific trước) rồi mới priority/id cũ.
- Controller validate `channel_account_ids` thuộc tenant (chống cross-tenant).
- Mọi query engine chạy `withoutGlobalScope(TenantScope)` + explicit tenant_id (giữ pattern hiện có).

---

## Phase 1 — Auto-reply per-page

### Task 1.1: Migration + pivot + cờ
**Files:** Create `app/app/Modules/Messaging/Database/Migrations/2026_06_08_100001_add_page_scope_to_auto_reply_rules.php`

- [ ] **Step 1:** Migration:
```php
public function up(): void
{
    Schema::table('auto_reply_rules', function (Blueprint $t) {
        $t->boolean('applies_all_pages')->default(false)->after('enabled');
    });
    Schema::create('auto_reply_rule_page', function (Blueprint $t) {
        $t->id();
        $t->foreignId('tenant_id')->index();
        $t->foreignId('auto_reply_rule_id')->constrained()->cascadeOnDelete();
        $t->foreignId('channel_account_id')->constrained()->cascadeOnDelete();
        $t->timestamps();
        $t->unique(['auto_reply_rule_id', 'channel_account_id']);
        $t->index('channel_account_id');
    });
    // Dữ liệu cũ: giữ hành vi "tất cả trang".
    DB::table('auto_reply_rules')->update(['applies_all_pages' => true]);
}
```
- [ ] **Step 2:** `php artisan migrate` (dev sqlite) chạy OK; rollback `down()` drop pivot + cột.

### Task 1.2: Model quan hệ + fillable
**Files:** `app/app/Modules/Messaging/Models/AutoReplyRule.php`
- [ ] Thêm `applies_all_pages` vào `$fillable` + cast bool; quan hệ:
```php
public function pages(): BelongsToMany
{
    return $this->belongsToMany(ChannelAccount::class, 'auto_reply_rule_page');
}
```

### Task 1.3: Engine lọc page (TDD)
**Files:** `app/app/Modules/Messaging/Services/AutoReplyEngine.php` (4 site: fire ~57, matches keyword ~120, matches generic ~135, fireKeyword ~162); Test `app/tests/Feature/Messaging/AutoReplyPageScopeTest.php`
- [ ] **Step 1:** Test fail: rule gán page A (applies_all_pages=false, pivot A) KHÔNG fire cho inbound page B; fire cho A. Rule applies_all_pages=true fire cả A,B. Rule page-specific thắng all-pages (precedence) khi cùng trigger.
- [ ] **Step 2:** Thêm helper `private function scopeToPage($query, int $channelAccountId)`:
```php
return $query
    ->where(fn ($q) => $q->where('applies_all_pages', true)
        ->orWhereHas('pages', fn ($p) => $p->where('channel_account_id', $channelAccountId)))
    ->orderByRaw('applies_all_pages asc'); // false (0) trước true (1)
```
Áp vào cả 4 query site (truyền `$conv->channel_account_id`), đặt orderBy page trước `orderBy('priority')->orderBy('id')`.
- [ ] **Step 3:** Test pass. Cập nhật test cũ (AutoReplyKeywordTest, MessagingAutoReplyTest...) — conv phải có channel_account_id (đa số đã có).

### Task 1.4: Controller + FE
**Files:** `AutoReplyRuleController.php` (validate ~73, present ~95, index ~20); `resources/js/pages/MessagingAutoRulesPage.tsx`; `resources/js/lib/messagingConfig.tsx`
- [ ] Validate `applies_all_pages: bool`, `channel_account_ids: array`, `channel_account_ids.*: int` (kiểm thuộc tenant). Sync pivot trong store/update. `present()` trả 2 field + danh sách page.
- [ ] FE: Form thêm "Áp dụng cho trang": `Switch` "Tất cả trang" + (khi tắt) `Select mode=multiple` page. Mặc định mới = page đang xem. Cột "Phạm vi". Type `AutoReplyRule` thêm `applies_all_pages` + `channel_account_ids`.
- [ ] Verify: `php artisan test --filter=AutoReply`; `npm run lint && typecheck && build`. Commit `feat(messaging): auto-reply theo từng page (SPEC 0035)`.

---

## Phase 2 — Automation flows per-page

### Task 2.1: Migration + pivot + cờ
**Files:** Create `..._100002_add_page_scope_to_automation_flows.php` — y hệt pattern 1.1 cho `automation_flows` + `automation_flow_page`; backfill `applies_all_pages=true`.

### Task 2.2: Model
**Files:** `AutomationFlow.php` — `applies_all_pages` fillable+cast; `pages()` belongsToMany `automation_flow_page`.

### Task 2.3: FlowMatcher lọc page (TDD)
**Files:** `app/app/Modules/Messaging/Services/Flows/FlowMatcher.php` (matching ~23); Test `app/tests/Feature/Messaging/Flows/FlowPageScopeTest.php`
- [ ] **Step 1:** Test fail: flow gán page A không match conv page B; all-pages match cả; page-specific thắng all-pages (->first()).
- [ ] **Step 2:** Thêm điều kiện page (như §5.1 spec) + `orderByRaw('applies_all_pages asc')` trước order hiện tại.
- [ ] **Step 3:** `comment_on_post`: validate `post_ids` thuộc page đã gán — thêm guard trong `postMatches()` hoặc controller validate. Test contradictory (post page B + gán page A) ⇒ không match.
- [ ] **Step 4:** Test pass; cập nhật FlowMatcherTest/FlowListenersTest/ChannelPostsTest.

### Task 2.4: AiFlowExclusion per-page
**Files:** `app/app/Modules/Messaging/Services/AiFlowExclusionService.php`; `AutomationFlowController.php` (isFacebookCatchAll ~88, publish ~142)
- [ ] **Step 1:** Test: catch-all flow gán page A ⇒ chỉ `messaging_account_meta(A).ai_auto_mode=false`, page B không đổi.
- [ ] **Step 2:** Đổi exclusion từ set tenant `auto_mode_facebook` → set per-page `ai_auto_mode` cho các page của flow (hoặc mọi page nếu applies_all_pages). (Phụ thuộc Task 3.3 `ai_auto_mode` column — sắp xếp Phase 3 trước hoặc thêm cột sớm.)

### Task 2.5: Controller + FE
**Files:** `AutomationFlowController.php` (validatePayload ~216, store/update/duplicate/present); `MessagingFlowEditorPage.tsx`; `lib/messagingFlows.tsx`; `features/messaging/flow/PostPicker.tsx`
- [ ] Validate + sync pivot + present 2 field. FE: page selector ở topbar editor; PostPicker dùng `channelId` đã chọn làm page (hiện đang vứt). Commit `feat(messaging): kịch bản tự động theo từng page (SPEC 0035)`.

---

## Phase 3 — AI knowledge + bật/tắt + auto-mode per-page

### Task 3.1: Migration knowledge pivot + meta auto-mode
**Files:** Create `..._100003_add_page_scope_to_ai_knowledge.php` + `..._100004_add_ai_auto_mode_to_messaging_account_meta.php`
- [ ] Knowledge: `ai_knowledge_documents.applies_all_pages` + pivot `ai_knowledge_document_page`; backfill docs cũ `applies_all_pages=true`.
- [ ] `messaging_account_meta`: thêm `ai_auto_mode` BOOLEAN DEFAULT false. Backfill mỗi page từ cờ nhóm tenant: FB page ← `messaging_settings.auto_mode_facebook`, marketplace ← `auto_mode_marketplace` (join qua channel_account.provider/group).

### Task 3.2: Knowledge retrieval per-page (TDD)
**Files:** `AiKnowledgeDocument.php` (pages() + applies_all_pages); `app/app/Modules/Messaging/Services/KnowledgeRetriever.php` (retrieve ~21); `AiSuggestionService.php` (gọi retrieve ~109,154); `KnowledgeController.php`; `IndexKnowledgeDoc.php`; Test `AiKnowledgePageScopeTest.php`
- [ ] **Step 1:** Test fail: doc gán page A không retrieve cho page B; applies_all_pages retrieve cả.
- [ ] **Step 2:** `retrieve(int $tenantId, ?int $channelAccountId, string $q, ...)`: lọc docIds theo (applies_all_pages OR pivot page). `AiSuggestionService` truyền `$conv->channel_account_id`.
- [ ] **Step 3:** Controller store nhận `applies_all_pages`+`channel_account_ids`, sync pivot. Test pass.

### Task 3.3: AI auto-mode + bật/tắt per-page (TDD)
**Files:** `MessagingAccountMeta.php` (`ai_auto_mode` fillable+cast); `app/app/Modules/Messaging/Listeners/AiAutoModeOnInbound.php` (autoModeFor ~85); Test `AiAutoModePageScopeTest.php`
- [ ] **Step 1:** Test fail: page A meta `ai_enabled=true,ai_auto_mode=true` ⇒ AI auto-reply; page B `false` ⇒ không, dù cùng tenant.
- [ ] **Step 2:** `AiAutoModeOnInbound`: đọc `MessagingAccountMeta` của `$conv->channel_account_id`; gate `ai_enabled && ai_auto_mode`; thiếu row ⇒ fallback cờ nhóm-tenant (giai đoạn chuyển tiếp). `ai_enabled` (plan-feature `messaging_ai`) vẫn gate tenant trước.
- [ ] **Step 3:** Test pass; cập nhật MessagingAutoModeTest, MessagingAiPriorityGateTest.

### Task 3.4: FE knowledge + AI toggles
**Files:** `MessagingKnowledgePage.tsx`; trang kênh/cài đặt (per-page AI) — `MessagingChannelsPage.tsx` hoặc `MessagingSettingsPage.tsx`; `lib/messagingConfig.tsx`
- [ ] Knowledge: "Áp dụng cho trang" (tick tất cả / multi-select). AI: per-page `Switch` "Bật AI" + "AI tự trả lời" (ghi messaging_account_meta qua endpoint). Commit `feat(messaging): AI knowledge + auto-mode theo từng page (SPEC 0035)`.

---

## Phase 4 — Dọn dẹp (spec sau, KHÔNG làm ngay)
- Gỡ fallback cờ nhóm-tenant (`auto_mode_facebook/_marketplace`) sau khi FE per-page ổn định + verify prod. Tách commit riêng.

---

## Self-Review
1. **Spec coverage:** auto-reply (P1), flows (P2), AI knowledge+autoMode+exclusion (P2.4/P3). Provider/quota giữ tenant (không task — đúng spec). Migration cũ→all-pages (mỗi Phase Task .1). Precedence page-specific (orderBy mỗi engine).
2. **Thứ tự phụ thuộc:** Task 2.4 (exclusion per-page) cần `ai_auto_mode` (Task 3.1) — khi thực thi, chạy migration 3.1 trước 2.4, hoặc gộp cột `ai_auto_mode` vào Phase 1 migration. Ghi rõ khi execute.
3. **Test baseline:** không có JS test runner — FE verify bằng build; BE PHPUnit. phpstan: thêm quan hệ/`belongsToMany` phải khai `@property`/return type tránh lỗi.
4. **Memory:** [[ui-avoid-select-prefer-radio]] — page list dài nên Select multiple là ngoại lệ hợp lý; icon @ant-design không emoji [[ui-use-font-icons-not-emoji]].
