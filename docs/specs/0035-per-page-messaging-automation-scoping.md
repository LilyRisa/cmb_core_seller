# SPEC 0035: Per-page scoping cho tự động trả lời, kịch bản tự động & AI (Messaging)

- **Trạng thái:** Implemented (Phase 1–3 + 4a · 2026-06-08). Phase 4b (gỡ fallback cờ nhóm-tenant) HOÃN tới sau khi prod ổn.
- **Phase:** 7.x (Messaging) — mở rộng
- **Module backend liên quan:** Messaging (chính). KHÔNG đụng Integrations/Messaging (connector) — scope bằng `channel_account_id`, provider-agnostic.
- **Tác giả / Ngày:** Claude · 2026-06-08
- **Liên quan:** SPEC 0024 (Omnichannel Messaging), SPEC 0027 (FB comment + private message), SPEC 0028 (AI help/CSKH), ADR-0017 (connector registry), ADR-0021 (Reverb), ADR-0022 (AI⊕auto-reply precedence). Memory: per-page bằng `channel_account_id` (Conversation đã có sẵn).

## 1. Vấn đề & mục tiêu

Hiện **tự động trả lời** (`auto_reply_rules`), **kịch bản tự động** (`automation_flows`), và **AI** (knowledge/auto-mode/bật-tắt) đều **tenant-global**: một cấu hình áp cho **TẤT CẢ** Facebook page của tenant. Shop nhiều page (prod: ~36 page/1 tenant) không thể cấu hình khác nhau theo page → 1 kịch bản/regex/tài liệu áp nhầm sang mọi page.

Bằng chứng (điều tra 2026-06-08):
- `auto_reply_rules`: chỉ `tenant_id`; `AutoReplyEngine` chọn rule theo `tenant_id+trigger+enabled`, **bỏ qua** `channel_account_id` (4 query site).
- `automation_flows`: `tenant_id+provider` (provider = *loại* `facebook_page`, áp mọi page); `FlowMatcher::matching()` không lọc page. Riêng `comment_on_post` đã ngầm theo page qua `post_ids`.
- AI knowledge (`ai_knowledge_documents/chunks`): tenant-wide; `KnowledgeRetriever::retrieve($tenantId,...)` lấy mọi doc của tenant.
- AI auto-mode: `messaging_settings` 1 row/tenant; split theo **nhóm** (`auto_mode_facebook`/`auto_mode_marketplace`), KHÔNG theo page. `ai_enabled` + `ai_provider_code` tenant-wide.

**Mục tiêu:** cho phép cấu hình **theo từng page** cho cả 3 hệ, **không làm gián đoạn** cấu hình hiện có.

## 2. Quyết định thiết kế (đã chốt với người dùng 2026-06-08)

1. **Mô hình gán = NHIỀU page (many-to-many)** cho rule / flow / tài liệu AI: mỗi cái gán **danh sách page** áp dụng (1, vài, hoặc tất cả).
2. **Giữ tùy chọn "Áp dụng tất cả trang"** (1 cờ boolean) song song với chọn danh sách page.
3. **Dữ liệu cũ giữ chạy "tất cả trang"** tới khi người dùng sửa (cờ `applies_all_pages=true` khi migrate). Yêu cầu "không áp tất cả page nữa" áp cho **mục tạo MỚI** (mặc định = page đang xem), cũ chuyển dần.
4. **Tài liệu AI cũ** = coi như gán **mọi page** (qua `applies_all_pages=true`), không mất tác dụng.
5. **AI bật/tắt + auto-mode = theo page** (lưu ở `messaging_account_meta`). **Provider AI + quota/credit GIỮ CHUNG tenant** (credential/credit dùng chung).
6. **Ưu tiên:** cấu hình gắn **page cụ thể** thắng cấu hình **"tất cả trang"** (khi cùng tranh chấp 1 sự kiện).

## 3. Trong / ngoài phạm vi

**Trong:**
- Pivot `auto_reply_rule_page`, `automation_flow_page`, `ai_knowledge_document_page` (mỗi cái: `*_id` + `channel_account_id`, unique cặp). + cờ `applies_all_pages` trên 3 bảng gốc.
- Lọc theo page + ưu tiên page-specific ở: `AutoReplyEngine` (4 site), `FlowMatcher::matching()`, `KnowledgeRetriever::retrieve()`.
- AI per-page: thêm `ai_enabled` (đã có) + `auto_mode` per-page ở `messaging_account_meta`; `AiAutoModeOnInbound` đọc theo page (fallback nhóm-tenant trong giai đoạn chuyển tiếp).
- `AiFlowExclusionService` per-page (catch-all flow của page X chỉ tắt AI của page X).
- Controller + FE: chọn nhiều page (+ tick "tất cả trang") ở rule/flow/knowledge; toggle AI/auto-mode per-page ở trang kênh.
- Migration dữ liệu cũ: set `applies_all_pages=true` cho mọi rule/flow/doc hiện có; backfill auto-mode per-page từ cờ nhóm hiện tại.

**Ngoài:**
- KHÔNG đụng connector (Integrations/Messaging). KHÔNG đổi `AiProvider` catalog (vẫn global) hay chọn provider per-page. KHÔNG đổi quota/credit (vẫn theo tenant). KHÔNG đụng comment auto-reply demux (memory [[messaging-marketplace-webhook-demux]]).

## 4. Mô hình dữ liệu

### 4.1 Pivot + cờ (3 hệ, cùng pattern)

```
auto_reply_rules         + applies_all_pages BOOLEAN NOT NULL DEFAULT false
automation_flows         + applies_all_pages BOOLEAN NOT NULL DEFAULT false
ai_knowledge_documents   + applies_all_pages BOOLEAN NOT NULL DEFAULT false

auto_reply_rule_page(        id, tenant_id, auto_reply_rule_id, channel_account_id, timestamps,
                              UNIQUE(auto_reply_rule_id, channel_account_id) )
automation_flow_page(        id, tenant_id, automation_flow_id, channel_account_id, timestamps,
                              UNIQUE(automation_flow_id, channel_account_id) )
ai_knowledge_document_page(  id, tenant_id, ai_knowledge_document_id, channel_account_id, timestamps,
                              UNIQUE(ai_knowledge_document_id, channel_account_id) )
```
- `applies_all_pages=true` ⇒ áp mọi page (bỏ qua pivot). `false` ⇒ chỉ các page trong pivot.
- Index pivot theo `channel_account_id` để lọc nhanh khi sự kiện đến.
- `tenant_id` trên pivot để TenantScope + dọn dữ liệu; FK `channel_account_id` cascade khi xoá page.

### 4.2 AI per-page trên `messaging_account_meta` (1 row/page — đã tồn tại)
- `ai_enabled` (đã có): bật/tắt AI cho page.
- Thêm `ai_auto_mode` BOOLEAN DEFAULT false: AI tự trả lời cho page (thay cho cờ nhóm-tenant).
- Migrate: mỗi page Facebook `ai_auto_mode` = `messaging_settings.auto_mode_facebook` hiện tại; marketplace tương tự. Giữ cờ nhóm-tenant làm fallback đọc trong giai đoạn chuyển tiếp.

## 5. Logic chọn + ưu tiên

Quy tắc chung "page áp dụng": `applies_all_pages = true` **OR** tồn tại pivot `(x_id, conv.channel_account_id)`.
Ưu tiên: sắp **page-specific (`applies_all_pages=false`) TRƯỚC** "tất cả trang", rồi mới tới `priority`/`id` cũ.

### 5.1 Auto-reply — `AutoReplyEngine` (4 query site: fire, matches×2, fireKeyword)
```php
$rules = AutoReplyRule::withoutGlobalScope(TenantScope::class)
    ->where('tenant_id', $conv->tenant_id)
    ->where('trigger', $trigger)->where('enabled', true)
    ->where(fn ($q) => $q
        ->where('applies_all_pages', true)
        ->orWhereHas('pages', fn ($p) => $p->where('channel_account_id', $conv->channel_account_id)))
    ->orderByDesc(/* page-specific trước */ DB::raw('applies_all_pages = false'))
    ->orderBy('priority')->orderBy('id')
    ->get();
```
(first-wins giữ nguyên; page-specific nay đứng trước.)

### 5.2 Flows — `FlowMatcher::matching()`
- Thêm cùng điều kiện page + orderBy page-specific trước. `comment_on_post`: validate `post_ids` thuộc các page đã gán (tránh kịch bản không bao giờ chạy — xung đột #3).

### 5.3 AI knowledge — `KnowledgeRetriever::retrieve(int $tenantId, ?int $channelAccountId, ...)`
- Lọc doc: `applies_all_pages=true` OR có pivot cho `$channelAccountId`. `AiSuggestionService` truyền `$conv->channel_account_id`. `IndexKnowledgeDoc` copy quan hệ page xuống tầng retrieval (chunk lọc theo document_id đã lọc — không cần pivot ở chunk).

### 5.4 AI auto-mode — `AiAutoModeOnInbound`
- Đọc `messaging_account_meta` của `$conv->channel_account_id`: `ai_enabled` && `ai_auto_mode`. Thiếu row/giá trị ⇒ fallback cờ nhóm-tenant (`auto_mode_facebook`/`_marketplace`) trong giai đoạn chuyển tiếp.

## 6. Xung đột & cách xử lý (từ điều tra)

1. **Dữ liệu cũ không có page** → migration set `applies_all_pages=true` (no-op hành vi). ✓
2. **Ưu tiên page-specific** → orderBy `applies_all_pages=false` trước (§5). ✓
3. **`comment_on_post` ngầm theo page** → validate `post_ids` ⊂ pages đã gán; UI: chọn page trước, post picker lọc theo page. ✓
4. **AI⊕flow exclusion tenant-wide** (`AiFlowExclusionService` tắt `auto_mode_facebook` cả tenant) → chuyển per-page: catch-all flow của page X chỉ set `messaging_account_meta(page X).ai_auto_mode=false`. ✓
5. **Quota/credit theo tenant** → giữ nguyên; UI ghi rõ "giới hạn AI tính chung toàn shop". ✓
6. **TenantScope thủ công** trong engine (withoutGlobalScope) → predicate page cũng thêm thủ công + validate page thuộc tenant ở controller (chống cross-tenant). ✓
7. **pgvector tương lai** ([[fb-comment-author-identity-unavailable]] không liên quan; AiKnowledgeChunk TODO) → ghi chú thêm filter page vào kế hoạch index.

## 7. HTTP & FE

- **Rule/Flow/Knowledge controllers**: nhận `applies_all_pages: bool` + `channel_account_ids: int[]` (validate thuộc tenant + provider phù hợp); sync pivot; resource trả 2 field. `index` cho lọc theo page.
- **FE** (icon @ant-design, không emoji; page list dài → `Select` multiple hợp lý — ngoại lệ memory [[ui-avoid-select-prefer-radio]]):
  - `MessagingAutoRulesPage`, `MessagingFlowEditorPage`, `MessagingKnowledgePage`: thêm "Áp dụng cho trang" = tick "Tất cả trang" hoặc multi-select page. Mặc định cái mới = page đang xem.
  - Trang kênh/cài đặt: toggle **Bật AI** + **AI tự trả lời** per-page (ghi `messaging_account_meta`).
  - Cột hiển thị phạm vi ("Tất cả trang" / "3 trang").

## 8. Kiểm thử

- Unit/Feature: rule/flow/knowledge gán page A KHÔNG fire/không retrieve cho page B; `applies_all_pages` fire mọi page; page-specific thắng all-pages (precedence); migration cũ → all-pages giữ hành vi; AI auto-mode per-page (page A on, B off); AiFlowExclusion chỉ tắt page có catch-all; cross-tenant page bị từ chối ở controller.
- Cập nhật test global hiện có (FlowMatcherTest, AutoReply*, MessagingAutoMode*, Knowledge*) cho chiều page.

## 9. Rollout

1. Migration thêm cột/pivot + backfill (`applies_all_pages=true` cho cũ; `ai_auto_mode` từ cờ nhóm). Idempotent.
2. Backend lọc page (giữ fallback nhóm-tenant cho auto-mode tới khi FE per-page xong).
3. FE chọn page + toggle per-page.
4. Sau khi ổn: gỡ fallback cờ nhóm-tenant (cleanup, spec sau).

> Không xoá cờ `auto_mode_facebook/_marketplace` ngay (giữ làm fallback đọc) — tránh vỡ giai đoạn chuyển tiếp.

## 10. Bổ sung 2026-07-05 — Thông tin cửa hàng theo page (`business_info`)

Mở rộng mô hình "1 row/page" ở §4.2: `messaging_account_meta` (khoá theo `channel_account_id`, đã có `ai_enabled`/`ai_auto_mode`) nay có thêm cột `business_info` (JSON, nullable, **không mã hoá** — khác `settings` đã encrypted) chứa thông tin công khai của shop theo TỪNG page: `shop_name, phone, address, email, warranty_policy, working_hours, website, extra_note`.

- Đặt qua `PATCH /api/v1/messaging/channels/{id}/business-info` (1 page) hoặc `PATCH /api/v1/messaging/channels/business-info` (`{ids:int[], business_info:{...}}`, áp hàng loạt nhiều page) — cả 2 gate `messaging.ai.config`. Chi tiết request/response: `docs/05-api/endpoints.md`.
- `AiSuggestionService::withBusinessInfo()` đọc `business_info` theo đúng `conv->channel_account_id` (không fallback nhóm-tenant — mỗi page độc lập hoàn toàn, không có khái niệm "áp dụng tất cả trang" như rule/flow/knowledge ở §4.1) và ghép vào system prompt AI (auto-reply + suggest) để trả lời câu hỏi liên hệ/SĐT/địa chỉ/bảo hành. Trống ⇒ không ảnh hưởng hành vi cũ.
- Đây là dữ liệu **per-page thuần** (1-1 với `channel_account_id`), không dùng cơ chế pivot nhiều-page/`applies_all_pages` như rule/flow/knowledge — vì thông tin liên hệ của mỗi page thường khác nhau, không có nhu cầu "áp mọi page".
