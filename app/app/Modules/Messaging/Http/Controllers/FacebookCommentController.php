<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
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
     * Đặt status = spam để ẩn khỏi inbox.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $conv = Conversation::query()->findOrFail($id);

        if ($error = $this->assertCommentThread($conv)) {
            return $error;
        }

        $account = ChannelAccount::findOrFail($conv->channel_account_id);
        $commentId = (string) ($conv->meta['fb_comment_id'] ?? '');
        $auth = $this->buildAuth($conv, $account);
        $connector = $this->registry->for($conv->provider);

        $connector->deleteComment($auth, $commentId);

        $meta = (array) ($conv->meta ?? []);
        $meta['comment_deleted'] = true;
        $conv->meta = $meta;
        $conv->status = Conversation::STATUS_SPAM;
        $conv->save();

        AuditLog::record('messaging.comment.deleted', null, [
            'conversation_id' => $conv->id,
            'fb_comment_id' => $commentId,
        ]);

        return response()->json(['data' => ['ok' => true]]);
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
}
