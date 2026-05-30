# SPEC-0027 — Facebook comment: hành động trên từng bình luận + modal nhắn riêng

Status: Draft · Owner: Minh · Liên quan: [SPEC-0024](0024-omnichannel-messaging.md)

## 1. Bối cảnh & vấn đề

Hộp thư comment Facebook (SPEC-0024) hiện gom mọi hành động kiểm duyệt ở **header**
hội thoại (ẩn/xoá comment gốc) và nhắn riêng qua **tab** trong ô soạn
(`MessageComposer` mode=comment với Segmented `Trả lời công khai / Nhắn riêng / Cả hai`).

Ba vấn đề cần xử lý:

1. **Lỗi production `(#10900) Activity already replied to`** khi nhắn riêng.
   Facebook chỉ cho **nhắn riêng 1 lần / comment** (qua bất kỳ công cụ nào). Hiện
   `privateReplyToComment` còn gửi text và ảnh thành **2 lần gọi** cùng `comment_id`
   → call thứ 2 tự dính 10900. Ngoài ra auto-reply job và nút thủ công cùng gọi một
   comment → caller thứ 2 dính 10900. Job đã `catch` (nuốt lỗi) nhưng controller thủ
   công thì ném ra user.

2. **Hiển thị media bài viết**: post card cần thể hiện rõ bài viết có **video**
   (hiện chỉ render `full_picture` — đúng là thumbnail cho cả ảnh lẫn video, nhưng
   không phân biệt được video).

3. **Thao tác per-comment**: mỗi bình luận của khách cần nút **Thích / Nhắn riêng /
   Xoá** ngay trên bình luận đó. Nút Nhắn riêng mở **modal** soạn tin đầy đủ (text +
   ảnh + video + file + mẫu tin), **không dùng tab** trong ô soạn.

## 2. Ràng buộc Facebook (đã kiểm chứng tài liệu công khai)

- **Private reply 1 lần/comment** → 10900 nếu lặp. Xử lý **idempotent**: coi 10900 là
  "đã nhắn", không ném lỗi.
- **Send API** (`me/messages`, `recipient:{comment_id}`) hỗ trợ **attachment**
  (image/video/file) và **trả về `recipient_id` = PSID**. → Lưu PSID rồi gửi tiếp các
  tin sau qua `recipient:{id:PSID}` trong cửa sổ 24h ⇒ modal nhiều đính kèm khả thi.
- 1 message = text **HOẶC** 1 attachment (không cả hai trong 1 call). Gửi nhiều phần =
  nhiều call: phần đầu qua `comment_id` (lấy PSID), các phần sau qua PSID.
- **Thích comment**: `POST /{comment-id}/likes` (bỏ thích: `DELETE`). Cần scope
  `pages_manage_engagement`. Thiếu quyền ⇒ báo lỗi rõ, gợi ý kết nối lại Page.

## 3. Thiết kế

### 3.1 Connector (`FacebookPageConnector`)

- **Interface tách riêng** `CommentEngagementConnector` (Interface Segregation, giống
  `ListsPostsConnector`) — **chỉ Facebook** implement; connector sàn TMĐT KHÔNG bị buộc
  implement và KHÔNG bị đụng tới. Core kiểm `instanceof CommentEngagementConnector`
  (tên năng lực, không phải tên sàn) trước khi gọi.
- Thêm capability `comment.like` (chỉ Facebook).
- `likeComment($auth, $commentId, bool $like): void` — POST/DELETE `/{commentId}/likes`.
- `sendCommentPrivateMessage($auth, $commentId, ?string $psid, string $message, array $attachments = []): string`
  — ghép các phần [text] + [mỗi attachment]; phần đầu gửi qua `comment_id` nếu chưa có
  `$psid` (bắt 10900 idempotent, lấy `recipient_id`), các phần sau qua PSID; trả PSID.
- `privateReplyToComment()` (vẫn trên `MessagingConnector`) giữ chữ ký `: void`,
  **delegate** sang `sendCommentPrivateMessage(...,$psid=null,...)` (sửa luôn double-call
  cho job & endpoint cũ).
- `fetchCommentThreads`: thêm field `attachments{media_type}` → suy ra `post_is_video`.

### 3.2 Backend module

- `BackfillFacebookComments`: lưu `meta.fb_post_is_video`.
- `ConversationResource`: expose `comment.post_is_video`.
- `FacebookCommentController`:
  - `like()` → `POST conversations/{id}/comment/like` body `{ comment_id, like }`.
  - `destroy()` nhận thêm `comment_id` tuỳ chọn (xoá đúng comment con; chỉ set
    `status=spam` khi xoá comment gốc).
  - `privateMessage()` → `POST conversations/{id}/comment/private-message` multipart
    `{ body?, comment_id?, files[]? }`: lưu file (mọi kind) → signed URL → connector;
    lưu `meta.fb_private_psid` + `meta.private_replied_at`; trả conversation resource.
- `routes.php`: thêm 2 route trên.

### 3.3 Frontend

- `messaging.tsx`: `ConversationComment.post_is_video`; hooks `useLikeComment`,
  `useDeleteCommentItem` (comment_id), `useSendCommentPrivateMessage`.
- `CommentPostCard`: overlay ▶ khi `post_is_video`.
- `CommentPrivateMessageModal` (mới): modal soạn tin đầy đủ (text + nhiều đính kèm
  image/video/file + chèn mẫu `/slash` + emoji + AI gợi ý), gửi tuần tự, hiển thị các
  tin đã gửi trong phiên.
- `MessagingPage`: mỗi message inbound của comment thread có hàng nút **Thích · Nhắn
  riêng · Xoá**; mở modal khi bấm Nhắn riêng. Bỏ nút Xoá ở header (giữ Ẩn).
- `MessageComposer`: bỏ Segmented đích gửi ở mode=comment (chỉ còn Trả lời công khai);
  bỏ `commentTarget` khỏi payload.

## 4. Phi mục tiêu (YAGNI)

- Không đồng bộ trạng thái "đã thích" lâu dài per-comment (chỉ optimistic trong phiên).
- Không gộp luồng DM kết quả vào thread comment (DM xuất hiện ở danh sách tin nhắn qua echo webhook).
- Không đụng code auto-reply/flow (agent khác đang làm) — chỉ sửa connector dùng chung.
- **Không** thêm like/nhắn-riêng/xoá-bình-luận vào sàn TMĐT — đây là năng lực riêng của
  Facebook (comment thread chỉ tồn tại với Facebook). UI per-comment chỉ hiện ở
  `thread_type='comment'` (Facebook).

## 6. Giới hạn đã biết (honest)

- **Ảnh/preview video chỉ đổ ở path BACKFILL** (`BackfillFacebookComments`, chạy
  `messaging:reconcile-sync` **mỗi giờ**), KHÔNG ở path webhook real-time
  (`ProcessMessagingWebhook` → `CommentConversationUpserter` chỉ lưu `fb_comment_id`/
  `fb_post_id`). ⇒ Bình luận MỚI tới chưa có ảnh/▶ cho tới lần sync kế (≤1 giờ). KHÔNG
  sửa path webhook đợt này để tránh va chạm agent đang làm comment-flow; enrich
  real-time là follow-up (thêm `fetchPostPreview` + gọi trong webhook ingest).
- **Modal nhắn riêng nhiều phần là best-effort**: Facebook chỉ chắc chắn nhận PHẦN ĐẦU
  (private reply, 1 lần/comment). Các phần sau gửi qua PSID + MESSAGE_TAG(HUMAN_AGENT) —
  có thể bị từ chối nếu khách chưa mở hội thoại / ngoài cửa sổ ⇒ BE dừng êm, báo
  `delivered/total`, FE cảnh báo "đã gửi X/Y phần…". Cần xác minh LIVE với token thật
  (Http::fake không phản ánh việc FB chấp nhận tin thứ 2).

## 5. Kiểm thử

- Unit: `sendCommentPrivateMessage` bắt 10900 idempotent; ghép parts đúng thứ tự
  (đầu `comment_id`, sau PSID); `likeComment` POST/DELETE đúng URL.
- Feature: endpoint `like` / `private-message` RBAC `messaging.reply`, validate, 422
  khi rỗng; lưu PSID vào meta.
