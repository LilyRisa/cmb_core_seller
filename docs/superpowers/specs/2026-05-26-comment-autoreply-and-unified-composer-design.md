# Thiết kế: Auto-reply cho Comment + Composer gửi tin đồng bộ + Template/Ảnh mọi nơi

- **Trạng thái:** Approved (chủ dự án duyệt 2026-05-26, uỷ quyền triển khai tự chủ)
- **Ngày / Tác giả:** 2026-05-26 · Codegen
- **Module:** Messaging · Integrations/Messaging · Integrations/Ai
- **Liên quan:** SPEC-0024 (Omnichannel Messaging), extensibility-rules.md §1/§6c, 04-channels/README.md §3 (capability map)

---

## 1. Vấn đề (kết quả audit hiện trạng)

Audit mã nguồn cho thấy:

1. **AI tự động trả lời TIN NHẮN — đã chạy end-to-end** cho cả Facebook lẫn 3 sàn (DM):
   `webhook → MessageReceived → AiAutoModeOnInbound → AiSuggestionService::autoRespond() → guardrail intent → connector.generateReply() → queueText → SendMessage → connector.sendText`.
   Điều kiện: provider `ai_providers` active + `messaging_settings.ai_enabled & auto_mode` + gói Business.
2. **Comment KHÔNG có auto-reply** — bị chặn cố ý ở `ProcessMessagingWebhook` (`fireInboundEvent: ! $isComment`); comment không phát `MessageReceived` nên engine không chạy. Rule cũng không có filter `thread_type`.
3. **Mẫu trả lời nhanh** chỉ dùng được ở composer tin nhắn (slash `/`); comment & nhắn-riêng không có. `TemplateResolver` không lọc theo `thread_type`.
4. **Form gửi tin KHÔNG đồng bộ:** 3 composer code rời trong `MessagingPage.tsx` — chỉ composer tin nhắn gửi được ảnh + template + AI; composer comment và modal nhắn-riêng chỉ có textarea.

## 2. Mục tiêu

- Comment **có chế độ tự động trả lời**, cấu hình theo từng rule: đích = **trả lời công khai** / **nhắn riêng** / **cả hai**; nguồn nội dung = **mẫu / văn bản cố định / AI** (AI qua cùng guardrail intent như DM).
- **Mẫu trả lời nhanh áp dụng cho cả comment lẫn tin nhắn** (kể cả khi nhắn riêng cho người comment).
- **Một composer dùng chung** cho mọi nơi soạn tin; mọi nơi **gửi được ảnh** (theo capability của provider); có template + emoji + AI gợi ý.
- Làm theo **capability map** — core không biết tên nền tảng; sàn cắm thêm sau bằng cách khai báo capability + implement method, không sửa core.

## 3. Nguyên tắc kiến trúc (BẮT BUỘC)

`extensibility-rules.md`: **core không bao giờ `if ($provider === ...)`**. Comment là **capability của `MessagingConnector`**:

| Capability mới | Ý nghĩa | FB | TikTok | Shopee | Lazada |
|---|---|---|---|---|---|
| `comment.reply_public` | trả lời công khai comment/review | ✅ | ❌ (không có API) | ⚠️ có `reply_comment` nhưng docs thiếu endpoint → chưa bật | ✅ `/review/seller/reply/add` (text ≤500) |
| `comment.reply_private` | nhắn riêng cho người comment | ✅ Private Reply | ❌ | ❌ | ❌ |
| `comment.media` | đính ảnh khi trả lời comment | ✅ (attachment_url) | ❌ | ❌ | ❌ |
| `comment.list` | list comment/review (backfill/polling) | ✅ | ✅ reviews | ✅ `get_comment` | ✅ review list v2 |
| `comment.webhook` | nhận comment/review qua webhook | ✅ feed | ❌ | ✅ review push 19 | ❌ |

> Bằng chứng SDK/docs: TikTok `customer_service/.../messages`, `review_rating/202605/product_reviews/search`, message-templates 202412, image upload-first; Shopee `sellerchat/send_message`, webchat push 10, `reply_comment`, review push 19; Lazada `im/message/send` (template_id 1/3/6 = text/image/video), `im/session|message/list` (polling, KHÔNG webhook), `review/seller/reply/add`, `review/seller/list/v2`.

**Phạm vi triển-khai-ngay vs sẵn-sàng-mở-rộng:**
- **Triển khai ngay:** Facebook comment auto-reply đầy đủ (public + private + ảnh + AI). Engine + composer + UI provider-agnostic. Lazada `replyToComment` (review reply, text). Lazada video chat (`template_id=6`). TikTok `sendTemplate`.
- **Sẵn sàng mở rộng (khai báo capability, chưa nối ingestion):** ingest review của sàn thành comment-conversation (webhook Shopee push 19 / polling TikTok+Lazada review list) — slice riêng sau; kiến trúc đã chừa chỗ. Shopee `reply_comment` chờ endpoint path chính thức.

## 4. Thiết kế Backend

### 4.1 Capability + interface
- Thêm 5 capability trên vào docblock chuẩn của `MessagingConnector` + `capabilities()` từng connector.
- Mở rộng signature (tương thích ngược, mặc định rỗng):
  - `replyToComment(auth, commentId, message, array $attachments = []): string`
  - `privateReplyToComment(auth, commentId, message, array $attachments = []): void`
- `FacebookPageConnector`: nhận `$attachments` (MediaRef → signed URL) → `attachment_url` cho public reply; Send API attachment cho private reply.
- `LazadaChatConnector::replyToComment` implement `/review/seller/reply/add`; bật `outbound.video` + implement `template_id=6`. `TikTokChatConnector::sendTemplate` implement message-templates 202412.

### 4.2 Schema (migration thuần JSON, có `down()`)
- `auto_reply_rules`:
  - `filter.thread_types`: `array<string>` tùy chọn (`['comment']` / `['message']` / vắng = mọi loại).
  - `action.comment_target`: `{ public: bool, private: bool }` (chỉ dùng khi thread là comment).
  - Trigger mới `comment_any` (mọi comment mới). Reuse `keyword`, `first_message` cho comment.
- `message_templates.scope.thread_types`: `array<string>` tùy chọn — vắng = dùng cho **cả** message & comment.
- Không cột mới; chỉ ghi vào jsonb sẵn có (migration cập nhật dữ liệu mặc định nếu cần — phần lớn không cần).

### 4.3 Luồng auto-reply comment
- `ProcessMessagingWebhook`: comment phát **event mới `CommentReceived{conversationId, messageId}`** (vẫn KHÔNG phát `MessageReceived` để auto-mode DM không bị kéo theo).
- **Listener `RunAutoReplyOnComment`** (queue `messaging`): gọi `AutoReplyEngine->fire()` cho comment theo thứ tự `first_message` → `keyword` → `comment_any`.
- **`AutoReplyEngine` nâng cấp:**
  - `matchesFilter`: thêm kiểm `filter.thread_types` so với `conv.thread_type`.
  - `conditionMet`: thêm case `TRIGGER_COMMENT_ANY => true` (khi thread = comment).
  - `windowKey`: thêm `comment_any` → per message (`cmt:` + external_message_id) để idempotent.
  - **Tách delivery khỏi engine:** sau khi resolve body, nếu `conv.thread_type === comment` → gọi `CommentReplyService::dispatch($conv, $body, $target, $attachments)`; ngược lại giữ `outbound->queueText`.
  - **AI cho comment:** `action.kind === ai_reply` + thread comment → gọi `AiSuggestionService::draftForAuto($conv, $inboundText)` (chạy guardrail; trả `null` nếu escalate → không đăng công khai, gắn `requires_human`). Refactor `autoRespond()` (DM) dùng chung `draftForAuto()` để 1 nguồn guardrail.
- **`CommentReplyService` + job `SendCommentReply`** (queue `messaging-outbound`, idempotent):
  - Kiểm capability `comment.reply_public` / `comment.reply_private` qua registry; thiếu → ghi run `failed` + log, không spam.
  - public → `connector.replyToComment(...)`; private → `connector.privateReplyToComment(...)`. Ghi lại như outbound `Message` (kind text, meta `{auto_rule_id, comment_target}`) để hiển thị trong thread + audit.
  - Cooldown/idempotency tái dùng `auto_reply_runs (rule_id, conversation_id, window_key)`.

### 4.4 Wire
- `MessagingServiceProvider`: `Event::listen(CommentReceived::class, RunAutoReplyOnComment::class)`.
- `FacebookCommentController` (manual reply hiện có) tái dùng `CommentReplyService` để gửi ảnh + ghi message nhất quán; bổ sung nhận attachment.

## 5. Thiết kế Frontend

### 5.1 `<MessageComposer>` dùng chung (`resources/js/components/messaging/MessageComposer.tsx`)
Props: `conversation`, `capabilities` (suy từ provider), `mode` (`dm` | `comment` | `private`), handlers (`onSendText`, `onSendMedia`, `onReplyComment`, `onPrivateReply`, `onAiSuggest`), `templates`, `needsTag`.
Render thống nhất: TextArea + slash-template popover + toolbar **ảnh/video/file** (ẩn/hiện theo capability) + emoji + **AI gợi ý** + nút gửi.
- `mode='comment'`: Segmented **"Trả lời công khai | Nhắn riêng"** chọn đích gửi tay; nút ảnh hiện nếu `comment.media`.
- Template list lọc theo `provider` + `thread_type` (`scope.thread_types`).
- Capability matrix client: map provider → kind cho phép (FB DM: image/video/file; sàn: image (+video Lazada); comment public: image (FB); private: image).

### 5.2 Tích hợp `MessagingPage.tsx`
Thay 3 khối composer rời (DM 1086-1194, comment 1062-1083, modal private 691-706) bằng `<MessageComposer>` với `mode` tương ứng. Bỏ code trùng (`handleCommentReply`, slash riêng) — gom vào component.

### 5.3 `MessagingAutoRulesPage.tsx`
Thêm (dùng `Radio.Group`/`Segmented`, KHÔNG `<Select>` — chuẩn dự án):
- **Loại áp dụng**: Tin nhắn / Bình luận / Cả hai → `filter.thread_types`.
- Khi có Bình luận: **Đích** Công khai / Nhắn riêng / Cả hai → `action.comment_target`.
- **Nguồn nội dung**: Mẫu / Văn bản / AI → `action.kind`.
- Trigger thêm `comment_any` ("Mọi bình luận mới").

## 6. Kiểm thử
- Unit: `matchesFilter` thread_types; `windowKey` comment_any idempotent; `CommentReplyService` capability-gate (UnsupportedOperation → run failed, không ném).
- Feature: CommentReceived → rule comment fire → `replyToComment`/`privateReplyToComment` gọi đúng (Http::fake); guardrail intent nhạy cảm → không đăng công khai; template thread_type scope; gửi ảnh public reply + private reply.
- FE: composer render đúng nút theo capability/mode; gửi ảnh ở comment & private; chọn template mọi nơi; auto-rule lưu thread_types + comment_target + kind.
- Quality gate: `pint --test`, `phpstan analyse`, `php artisan test`, `npm run lint && typecheck && build`.

## 7. Ngoài phạm vi
- Ingest review SÀN thành comment-conversation (webhook/polling review) — slice riêng; capability đã khai báo sẵn.
- Shopee `reply_comment` (chờ endpoint path) — capability `comment.reply_public=false` tới khi có docs.
- Gửi file (không sàn nào hỗ trợ); private reply cho review (không sàn nào hỗ trợ).
- Reverb realtime (giữ nguyên cơ chế hiện có).

## 8. Tiêu chí hoàn thành
- [ ] Comment Facebook tự động trả lời theo rule (public/private/cả hai; mẫu/text/AI) — idempotent, guardrail.
- [ ] Template dùng được ở DM + comment + nhắn-riêng; lọc theo thread_type.
- [ ] 1 `<MessageComposer>` dùng ở cả 3 nơi; mọi nơi gửi được ảnh (theo capability).
- [ ] Capability map mở rộng; Lazada review reply + video chat + TikTok sendTemplate implement; core không có tên sàn.
- [ ] Quality gate xanh (trừ 7 test GHN/fulfillment fail sẵn có trên main).
