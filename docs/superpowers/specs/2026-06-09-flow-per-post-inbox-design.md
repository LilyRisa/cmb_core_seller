# SPEC 2026-06-09 — Flow theo bài viết cho tin nhắn (inbox) + funnel comment→DM

**Mục tiêu:** Mở rộng tính năng chọn bài viết riêng lẻ (đã có cho flow bình luận `comment_on_post`) sang **flow tin nhắn (inbox)**, hỗ trợ 2 nghiệp vụ:
1. **Lọc DM theo bài viết nguồn** — flow inbox chỉ chạy khi hội thoại DM bắt nguồn từ bình luận trên bài viết đã chọn.
2. **Funnel comment→DM nhiều bước** — khách bình luận bài X → được nhắn riêng (mở đầu) → khi trả lời trong Messenger thì chạy tiếp flow DM (nút/điều kiện/AI) gắn theo bài X.

Liên quan: SPEC 0035 (per-page scoping), ADR-0022 (AI⊕auto-reply precedence).

## Nền tảng (vì sao khả thi)

Hội thoại DM không sẵn có thông tin bài viết. Nhưng khi gửi **tin riêng cho 1 bình luận** (`FacebookPageConnector::sendCommentPrivateMessage`), Facebook trả **PSID người nhận** (`recipient_id`) — lúc đó đã biết `fb_post_id` từ hội thoại comment. Đây là mắt xích comment→DM ta **kiểm soát hoàn toàn**, không cần danh tính người bình luận (vốn bị ẩn — xem memory `fb-comment-author-identity-unavailable`).

## Thiết kế

### 1. Enabler — liên kết DM ↔ bài viết nguồn
- **Bảng `messaging_comment_dm_links`**: `(tenant_id, channel_account_id, psid, fb_post_id, fb_comment_id?, linked_at)`, unique `(channel_account_id, psid)` — mới nhất thắng.
- **`CommentDmLinker`** service:
  - `record(...)`: gọi ngay sau khi gửi tin riêng cho comment (đã biết PSID + `fb_post_id`) → upsert link. Gọi ở: `SendCommentReply` (auto-reply + node `send_comment_reply`), `FacebookCommentController` (nhắn riêng thủ công).
  - `stampInbound(account, conv, dto)`: khi có DM inbound, gắn `conversations.meta.fb_post_id` (**first-touch**, không ghi đè). Chỉ `facebook_page`, chỉ inbound, chỉ thread DM. Gọi trong `MessageIngestionService::ingest()` (đồng bộ, trước khi listener queued chạy).
- `SendCommentReply`: đổi từ `privateReplyToComment` (void) sang `sendCommentPrivateMessage` (lấy PSID) khi connector hỗ trợ `CommentEngagementConnector`; fallback bản void cho connector cũ.

### 2. FlowMatcher — lọc theo bài viết cho trigger inbox
`triggerConditionMet`: với `inbox_first_message / inbox_keyword / inbox_any`, sau khi điều kiện gốc đạt, nếu `trigger_config.post_ids` không rỗng → yêu cầu `conv.meta.fb_post_id ∈ post_ids` (`inboxPostScopeOk`). Rỗng ⇒ không giới hạn (hành vi cũ). `comment_on_post` không đổi. Lọc bài viết **AND** độc lập với phạm vi trang (SPEC 0035).

### 3. Funnel comment→DM (không sửa engine)
- Bài viết đích có flow/auto-reply comment gửi **tin riêng mở đầu** (target private) → `CommentDmLinker::record`.
- Khách trả lời trong Messenger → `MessageIngestionService` stamp `fb_post_id` cho hội thoại DM → `StartFlowOnInbound` khớp flow inbox (first_message) gán đúng bài → chạy flow DM nhiều bước.

### 4. Frontend (`MessagingFlowEditorPage`)
- Với trigger inbox: thêm checkbox **"Giới hạn theo bài viết"** → mở **PostPicker** sẵn có → lưu `trigger_config.post_ids`. Tooltip giải thích chỉ khớp DM đến từ bình luận trên bài đã chọn. Tái dùng `PostPicker` + `pickerOpen` + `postIds`.

## Backend không đổi
- `AutomationFlowController` validation đã cho phép `trigger_config.post_ids` mọi trigger ⇒ không sửa.
- Engine, listener (`StartFlowOnInbound`), page-scope (SPEC 0035) giữ nguyên.

## Test
- `FlowMatcherTest`: inbox + post_ids (khớp/khác bài/không có post; rỗng ⇒ mọi DM).
- `CommentDmLinkerTest`: record mới-nhất-thắng; stampInbound gắn post (first-touch, không ghi đè, no-op khi thiếu link).

## Ngoài phạm vi
1 flow hợp nhất comment→DM (hiện phối 2 flow); referral quảng cáo; provider ≠ Facebook.
