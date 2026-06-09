<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\Contracts\CommentEngagementConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Exceptions\AttachmentInvalid;
use CMBcoreSeller\Modules\Messaging\Http\Resources\ConversationResource;
use CMBcoreSeller\Modules\Messaging\Http\Resources\MessageResource;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\CommentDmLinker;
use CMBcoreSeller\Modules\Messaging\Services\MediaRelayService;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Kiểm duyệt comment Facebook (ẩn / xoá / trả lời công khai / nhắn riêng).
 *
 * Chỉ áp dụng cho conversation có `thread_type = 'comment'` (Facebook comment thread).
 * Mọi action đều cần quyền `messaging.reply`.
 *
 * SPEC-0024 — Phase comment moderation.
 */
class FacebookCommentController extends Controller
{
    public function __construct(
        private MessagingRegistry $registry,
        private MessageIngestionService $ingestion,
        private MediaRelayService $media,
        private MediaStorage $storage,
        private CommentDmLinker $postLinker,
    ) {}

    /**
     * Ẩn hoặc hiện comment (`POST /conversations/{id}/comment/hide`).
     * Body: `{ hidden: bool }`
     */
    public function hide(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'hidden' => ['required', 'boolean'],
        ]);

        $conv = Conversation::query()->findOrFail($id);

        if ($error = $this->assertCommentThread($conv)) {
            return $error;
        }

        $account = ChannelAccount::findOrFail($conv->channel_account_id);
        $commentId = (string) ($conv->meta['fb_comment_id'] ?? '');
        $auth = $this->buildAuth($conv, $account);
        $connector = $this->registry->for($conv->provider);

        $connector->hideComment($auth, $commentId, (bool) $data['hidden']);

        $meta = (array) ($conv->meta ?? []);
        $meta['comment_hidden'] = (bool) $data['hidden'];
        $conv->meta = $meta;
        $conv->save();

        $conv->load('channelAccount');

        return response()->json(['data' => (new ConversationResource($conv))->toArray($request)]);
    }

    /**
     * Xoá vĩnh viễn comment (`DELETE /conversations/{id}/comment`).
     *
     * Body `{ comment_id? }`: bỏ trống ⇒ xoá comment GỐC (đánh dấu hội thoại spam,
     * ẩn khỏi inbox). Có `comment_id` con ⇒ chỉ xoá đúng comment đó, giữ hội thoại.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'comment_id' => ['nullable', 'string', 'max:255'],
        ]);

        $conv = Conversation::query()->findOrFail($id);

        if ($error = $this->assertCommentThread($conv)) {
            return $error;
        }

        $account = ChannelAccount::findOrFail($conv->channel_account_id);
        $rootCommentId = (string) ($conv->meta['fb_comment_id'] ?? '');
        $commentId = (string) ($data['comment_id'] ?? '') !== '' ? (string) $data['comment_id'] : $rootCommentId;
        $isRoot = $commentId === $rootCommentId;
        $auth = $this->buildAuth($conv, $account);
        $connector = $this->registry->for($conv->provider);

        $connector->deleteComment($auth, $commentId);

        // Xoá comment con → chỉ gỡ message tương ứng; xoá comment gốc → spam cả hội thoại.
        if ($isRoot) {
            $meta = (array) ($conv->meta ?? []);
            $meta['comment_deleted'] = true;
            $conv->meta = $meta;
            $conv->status = Conversation::STATUS_SPAM;
            $conv->save();
        } else {
            $conv->messages()->where('external_message_id', $commentId)->delete();
        }

        AuditLog::record('messaging.comment.deleted', null, [
            'conversation_id' => $conv->id,
            'fb_comment_id' => $commentId,
            'is_root' => $isRoot,
        ]);

        return response()->json(['data' => ['ok' => true]]);
    }

    /**
     * Thích / bỏ thích 1 comment bằng danh nghĩa Page (`POST /conversations/{id}/comment/like`).
     * Body `{ comment_id?, like: bool }` — `comment_id` bỏ trống ⇒ comment gốc.
     */
    public function like(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'comment_id' => ['nullable', 'string', 'max:255'],
            'like' => ['required', 'boolean'],
        ]);

        $conv = Conversation::query()->findOrFail($id);

        if ($error = $this->assertCommentThread($conv)) {
            return $error;
        }

        $account = ChannelAccount::findOrFail($conv->channel_account_id);
        $connector = $this->registry->for($conv->provider);

        if (! $connector instanceof CommentEngagementConnector) {
            return $this->engagementUnsupported();
        }

        $rootCommentId = (string) ($conv->meta['fb_comment_id'] ?? '');
        $commentId = (string) ($data['comment_id'] ?? '') !== '' ? (string) $data['comment_id'] : $rootCommentId;
        $auth = $this->buildAuth($conv, $account);

        try {
            $connector->likeComment($auth, $commentId, (bool) $data['like']);
        } catch (\RuntimeException $e) {
            // Thiếu quyền `pages_manage_engagement` (hoặc lỗi Graph khác) ⇒ báo rõ thay vì 500.
            $isPerm = str_contains($e->getMessage(), 'pages_manage_engagement') || str_contains($e->getMessage(), '(#200)') || str_contains($e->getMessage(), '(#10)');

            return response()->json([
                'error' => [
                    'code' => $isPerm ? 'ENGAGEMENT_PERMISSION' : 'LIKE_FAILED',
                    'message' => $isPerm
                        ? 'Trang chưa được cấp quyền thích bình luận (pages_manage_engagement). Hãy kết nối lại Page để cấp quyền.'
                        : 'Không thực hiện được thao tác thích. Vui lòng thử lại.',
                ],
            ], 422);
        }

        return response()->json(['data' => ['ok' => true, 'like' => (bool) $data['like']]]);
    }

    /**
     * Nhắn tin riêng cho người bình luận qua modal (`POST /conversations/{id}/comment/private-message`).
     *
     * Multipart `{ body?, comment_id?, files[]? }`: gửi text + nhiều đính kèm (ảnh/video/
     * file) tuần tự. Lần đầu dùng Private Reply (lấy PSID), lưu `meta.fb_private_psid` để
     * lần sau gửi thẳng. Bắt lỗi "đã nhắn riêng" idempotent ở connector.
     */
    public function privateMessage(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'comment_id' => ['nullable', 'string', 'max:255'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:51200'], // 50MB/file
        ]);

        $body = (string) ($data['body'] ?? '');
        $hasFiles = $request->hasFile('files');
        if (trim($body) === '' && ! $hasFiles) {
            return response()->json([
                'error' => ['code' => 'EMPTY_REPLY', 'message' => 'Cần nội dung hoặc tệp đính kèm để nhắn riêng.'],
            ], 422);
        }

        $conv = Conversation::query()->findOrFail($id);

        if ($error = $this->assertCommentThread($conv)) {
            return $error;
        }

        $account = ChannelAccount::findOrFail($conv->channel_account_id);
        $connector = $this->registry->for($conv->provider);

        if (! $connector instanceof CommentEngagementConnector) {
            return $this->engagementUnsupported();
        }

        $rootCommentId = (string) ($conv->meta['fb_comment_id'] ?? '');
        $commentId = (string) ($data['comment_id'] ?? '') !== '' ? (string) $data['comment_id'] : $rootCommentId;
        $auth = $this->buildAuth($conv, $account);

        try {
            $attachments = $this->buildFileAttachments($conv, $request);
        } catch (AttachmentInvalid $e) {
            return response()->json(['error' => ['code' => 'ATTACHMENT_INVALID', 'message' => $e->getMessage()]], 422);
        }

        $psid = (string) ($conv->meta['fb_private_psid'] ?? '') !== '' ? (string) $conv->meta['fb_private_psid'] : null;
        $result = $connector->sendCommentPrivateMessage($auth, $commentId, $psid, $body, $attachments);

        // Không gửi được phần nào: đã nhắn riêng trước đó / khách chưa mở hội thoại /
        // cửa sổ đóng. Báo rõ để người dùng tiếp tục ở hội thoại tin nhắn khi khách trả lời.
        if ($result['delivered'] === 0) {
            return response()->json([
                'error' => [
                    'code' => 'PRIVATE_REPLY_BLOCKED',
                    'message' => 'Facebook chưa cho gửi lúc này (đã nhắn riêng cho bình luận này, hoặc khách chưa mở hội thoại tin nhắn). Hãy nhắn tiếp khi khách trả lời trong Messenger.',
                ],
            ], 422);
        }

        $meta = (array) ($conv->meta ?? []);
        $meta['private_replied_at'] = now()->toIso8601String();
        if ($result['psid'] !== '') {
            $meta['fb_private_psid'] = $result['psid'];
        }
        $conv->meta = $meta;
        $conv->save();

        // Tạo/đính HỘP THOẠI DM (thread_type=message) keyed theo PSID buyer + ghi tin
        // outbound vừa gửi — để hộp thoại tin nhắn hiện NGAY trong inbox (trước đây chỉ
        // xuất hiện khi khách trả lời qua webhook). ensureConversation mặc định thread
        // 'message'; dùng mid Facebook làm external_message_id ⇒ echo webhook dedupe.
        $dmConv = null;
        $psidFinal = (string) $result['psid'];
        if ($psidFinal !== '') {
            $ingest = $this->ingestion->ingest($account, new MessageDTO(
                externalConversationId: $psidFinal,
                externalMessageId: ($result['message_id'] ?? null) !== null && (string) $result['message_id'] !== ''
                    ? (string) $result['message_id']
                    : 'private:'.$commentId.':'.now()->valueOf(),
                buyerExternalId: $psidFinal,
                direction: MessageDirection::Outbound,
                kind: $attachments !== [] ? MessageKind::Image : MessageKind::Text,
                body: $body !== '' ? $body : null,
                attachments: $attachments,
                sentAt: now()->toImmutable(),
                raw: ['type' => 'private_reply', 'comment_id' => $commentId],
            ));
            $dmConv = $ingest['conversation'];

            // Kế thừa tên khách từ comment thread (nếu có) khi DM chưa có tên.
            if (blank($dmConv->buyer_name) && filled($conv->buyer_name)) {
                $dmConv->forceFill(['buyer_name' => $conv->buyer_name])->save();
            }

            // Gắn bài viết nguồn cho hội thoại DM (first-touch) + map (page,psid)→post để
            // flow inbox theo bài viết / funnel comment→DM khớp đúng (SPEC 2026-06-09).
            $fbPostId = (string) (($conv->meta ?? [])['fb_post_id'] ?? '');
            if ($fbPostId !== '') {
                $this->postLinker->record((int) $conv->tenant_id, (int) $account->id, $psidFinal, $fbPostId, $commentId);
                $dmMeta = (array) ($dmConv->meta ?? []);
                if (($dmMeta['fb_post_id'] ?? '') === '') {
                    $dmMeta['fb_post_id'] = $fbPostId;
                    $dmMeta['fb_comment_id'] = $dmMeta['fb_comment_id'] ?? $commentId;
                    $dmMeta['fb_post_source'] = 'comment_dm_link';
                    $dmConv->forceFill(['meta' => $dmMeta])->save();
                }
            }

            // Fire event (ConversationCreated khi mới) cho realtime inbox.
            $this->ingestion->fireEventsForNewMessage($dmConv, $ingest['message'], $ingest['created']);
        }

        AuditLog::record('messaging.comment.private_message', null, [
            'conversation_id' => $conv->id,
            'fb_comment_id' => $commentId,
            'delivered' => $result['delivered'],
            'total' => $result['total'],
            'dm_conversation_id' => $dmConv?->id,
        ]);

        $conv->load('channelAccount');

        return response()->json([
            'data' => (new ConversationResource($conv))->toArray($request),
            'meta' => [
                'delivered' => $result['delivered'],
                'total' => $result['total'],
                'dm_conversation_id' => $dmConv?->id,
            ],
        ]);
    }

    /**
     * Trả lời công khai comment (`POST /conversations/{id}/comment/reply`).
     * Body: `{ body: string }`
     * Tạo outbound message vào conversation để hiển thị trong thread.
     */
    public function reply(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'file', 'image', 'max:25600'], // 25MB, gửi kèm ảnh
        ]);

        $body = (string) ($data['body'] ?? '');
        if (trim($body) === '' && ! $request->hasFile('image')) {
            return response()->json([
                'error' => ['code' => 'EMPTY_REPLY', 'message' => 'Cần nội dung hoặc ảnh để trả lời.'],
            ], 422);
        }

        $conv = Conversation::query()->findOrFail($id);

        if ($error = $this->assertCommentThread($conv)) {
            return $error;
        }

        $account = ChannelAccount::findOrFail($conv->channel_account_id);
        $commentId = (string) ($conv->meta['fb_comment_id'] ?? '');
        $auth = $this->buildAuth($conv, $account);
        $connector = $this->registry->for($conv->provider);

        try {
            $attachments = $this->buildImageAttachments($conv, $request);
        } catch (AttachmentInvalid $e) {
            return response()->json(['error' => ['code' => 'ATTACHMENT_INVALID', 'message' => $e->getMessage()]], 422);
        }

        $newCommentId = $connector->replyToComment($auth, $commentId, $body, $attachments);

        $result = $this->ingestion->ingest($account, new MessageDTO(
            externalConversationId: $conv->external_conversation_id,
            externalMessageId: $newCommentId,
            buyerExternalId: $conv->buyer_external_id,
            direction: MessageDirection::Outbound,
            kind: $attachments !== [] ? MessageKind::Image : MessageKind::Text,
            body: $body !== '' ? $body : null,
            attachments: $attachments,
            sentAt: now()->toImmutable(),
        ));

        return response()->json(['data' => (new MessageResource($result['message']->load('attachments')))->toArray($request)]);
    }

    /**
     * Nhắn tin riêng tư cho người bình luận (`POST /conversations/{id}/comment/private-reply`).
     * Body: `{ body: string }`
     * Ghi nhận `meta.private_replied_at` → FE hiển thị icon "đã nhắn riêng".
     */
    public function privateReply(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'file', 'image', 'max:25600'],
        ]);

        $body = (string) ($data['body'] ?? '');
        if (trim($body) === '' && ! $request->hasFile('image')) {
            return response()->json([
                'error' => ['code' => 'EMPTY_REPLY', 'message' => 'Cần nội dung hoặc ảnh để nhắn riêng.'],
            ], 422);
        }

        $conv = Conversation::query()->findOrFail($id);

        if ($error = $this->assertCommentThread($conv)) {
            return $error;
        }

        $account = ChannelAccount::findOrFail($conv->channel_account_id);
        $commentId = (string) ($conv->meta['fb_comment_id'] ?? '');
        $auth = $this->buildAuth($conv, $account);
        $connector = $this->registry->for($conv->provider);

        try {
            $attachments = $this->buildImageAttachments($conv, $request);
        } catch (AttachmentInvalid $e) {
            return response()->json(['error' => ['code' => 'ATTACHMENT_INVALID', 'message' => $e->getMessage()]], 422);
        }

        $connector->privateReplyToComment($auth, $commentId, $body, $attachments);

        $meta = (array) ($conv->meta ?? []);
        $meta['private_replied_at'] = now()->toIso8601String();
        $conv->meta = $meta;
        $conv->save();

        AuditLog::record('messaging.comment.private_reply', null, [
            'conversation_id' => $conv->id,
            'fb_comment_id' => $commentId,
        ]);

        $conv->load('channelAccount');

        return response()->json(['data' => (new ConversationResource($conv))->toArray($request)]);
    }

    // --- Helpers -----------------------------------------------------------

    private function assertCommentThread(Conversation $conv): ?JsonResponse
    {
        if ($conv->thread_type !== Conversation::THREAD_COMMENT) {
            return response()->json([
                'error' => ['code' => 'NOT_A_COMMENT', 'message' => 'Hội thoại này không phải là comment thread.'],
            ], 422);
        }

        return null;
    }

    /** Provider không hỗ trợ thích/nhắn-riêng bình luận (chỉ Facebook). */
    private function engagementUnsupported(): JsonResponse
    {
        return response()->json([
            'error' => ['code' => 'UNSUPPORTED', 'message' => 'Kênh này không hỗ trợ thao tác này trên bình luận.'],
        ], 422);
    }

    private function buildAuth(Conversation $conv, ChannelAccount $account): MessagingAuthContext
    {
        return new MessagingAuthContext(
            channelAccountId: $account->id,
            provider: $conv->provider,
            externalShopId: $account->external_shop_id,
            accessToken: (string) ($account->access_token ?? ''),
            extra: (array) ($account->meta ?? []),
        );
    }

    /**
     * Lưu ảnh upload (nếu có) → MediaRefDTO kèm signed URL để connector đính vào
     * reply công khai / nhắn riêng. Lưu cả `storagePath` để ingest hiển thị trong thread.
     *
     * @return list<MediaRefDTO>
     *
     * @throws AttachmentInvalid
     */
    private function buildImageAttachments(Conversation $conv, Request $request): array
    {
        if (! $request->hasFile('image')) {
            return [];
        }

        $stored = $this->media->storeUpload((int) $conv->tenant_id, (int) $conv->id, $request->file('image'), 'image');

        return [new MediaRefDTO(
            kind: MessageKind::Image,
            mime: $stored['mime'],
            sizeBytes: $stored['size_bytes'],
            externalUrl: $this->storage->temporaryUrlForPath($stored['storage_path']),
            storagePath: $stored['storage_path'],
            filename: $stored['filename'],
        )];
    }

    /**
     * Lưu các file upload `files[]` (ảnh/video/file) → list MediaRefDTO kèm signed URL
     * cho modal nhắn riêng. Kind suy từ MIME. Lưu cả `storagePath` để hiển thị sau.
     *
     * @return list<MediaRefDTO>
     *
     * @throws AttachmentInvalid
     */
    private function buildFileAttachments(Conversation $conv, Request $request): array
    {
        $out = [];
        foreach ((array) $request->file('files', []) as $file) {
            $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType());
            $kind = str_starts_with($mime, 'image/') ? MessageKind::Image
                : (str_starts_with($mime, 'video/') ? MessageKind::Video
                    : (str_starts_with($mime, 'audio/') ? MessageKind::Audio : MessageKind::File));
            $kindStr = match ($kind) {
                MessageKind::Image => 'image',
                MessageKind::Video => 'video',
                MessageKind::Audio => 'audio',
                default => 'file',
            };

            $stored = $this->media->storeUpload((int) $conv->tenant_id, (int) $conv->id, $file, $kindStr);

            $out[] = new MediaRefDTO(
                kind: $kind,
                mime: $stored['mime'],
                sizeBytes: $stored['size_bytes'],
                externalUrl: $this->storage->temporaryUrlForPath($stored['storage_path']),
                storagePath: $stored['storage_path'],
                filename: $stored['filename'],
            );
        }

        return $out;
    }
}
