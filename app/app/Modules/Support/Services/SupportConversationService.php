<?php

namespace CMBcoreSeller\Modules\Support\Services;

use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Support\Models\SupportMessage;
use CMBcoreSeller\Modules\Support\Models\SupportMessageAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Toàn bộ NGHIỆP VỤ hội thoại CSKH (SPEC-0028) — gom 1 chỗ để controller mỏng,
 * dễ test & mở rộng (vd thêm realtime/Reverb sau chỉ cần phát event ở đây).
 *
 * Quy tắc tenant: phía user có CurrentTenant ⇒ `tenant_id` tự set + query auto-scope.
 * Phía admin (admin_web, KHÔNG có CurrentTenant) thao tác trên `$conv` đã nạp sẵn
 * (withoutGlobalScope) và LUÔN set `tenant_id` tường minh từ `$conv->tenant_id`.
 */
class SupportConversationService
{
    public function __construct(private SupportMediaService $media) {}

    /**
     * User gửi tin. Cuộc gần nhất đang mở ⇒ nối tiếp; đã đóng / chưa có ⇒ MỞ CUỘC MỚI.
     *
     * @param  array<int,UploadedFile>  $files
     */
    public function postUserMessage(int $tenantId, ?int $userId, ?string $body, array $files): SupportConversation
    {
        // Validate đính kèm TRƯỚC (ném 422 trước khi ghi DB/disk — không tạo cuộc rác).
        $validated = $this->validateFiles($files);

        return DB::transaction(function () use ($tenantId, $userId, $body, $validated) {
            $conv = SupportConversation::query()->latest('id')->first();
            if (! $conv || $conv->isClosed()) {
                $conv = SupportConversation::query()->create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'status' => SupportConversation::STATUS_OPEN,
                ]);
            }

            $msg = SupportMessage::query()->create([
                'tenant_id' => $tenantId,
                'support_conversation_id' => $conv->getKey(),
                'sender' => SupportMessage::SENDER_USER,
                'type' => SupportMessage::TYPE_TEXT,
                'user_id' => $userId,
                'body' => $body,
            ]);
            $this->attachFiles($msg, $validated);

            $conv->forceFill([
                'last_message_at' => now(),
                'last_sender' => SupportConversation::SENDER_USER,
            ])->save();

            return $conv;
        });
    }

    /**
     * CSKH (admin) gửi tin vào 1 cuộc — nhắn nhiều lần được; tăng unread phía user.
     *
     * @param  array<int,UploadedFile>  $files
     */
    public function postCskhMessage(SupportConversation $conv, int $adminId, ?string $body, array $files): SupportMessage
    {
        $validated = $this->validateFiles($files);

        return DB::transaction(function () use ($conv, $adminId, $body, $validated) {
            $msg = SupportMessage::query()->create([
                'tenant_id' => $conv->tenant_id,
                'support_conversation_id' => $conv->getKey(),
                'sender' => SupportMessage::SENDER_CSKH,
                'type' => SupportMessage::TYPE_TEXT,
                'admin_id' => $adminId,
                'body' => $body,
            ]);
            $this->attachFiles($msg, $validated);

            $conv->forceFill([
                'last_message_at' => now(),
                'last_sender' => SupportConversation::SENDER_CSKH,
                'user_unread_count' => (int) $conv->user_unread_count + 1,
            ])->save();

            return $msg;
        });
    }

    /**
     * CSKH đóng cuộc: chèn tin hệ thống + đánh dấu closed + báo user (unread++).
     * Idempotent — đã đóng thì bỏ qua.
     */
    public function close(SupportConversation $conv, int $adminId): SupportConversation
    {
        if ($conv->isClosed()) {
            return $conv;
        }

        return DB::transaction(function () use ($conv, $adminId) {
            SupportMessage::query()->create([
                'tenant_id' => $conv->tenant_id,
                'support_conversation_id' => $conv->getKey(),
                'sender' => SupportMessage::SENDER_CSKH,
                'type' => SupportMessage::TYPE_SYSTEM,
                'admin_id' => $adminId,
                'body' => 'Hỗ trợ viên đã đóng đoạn hội thoại này. Gửi tin nhắn mới để bắt đầu cuộc trò chuyện mới.',
            ]);

            $conv->forceFill([
                'status' => SupportConversation::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => $adminId,
                'last_message_at' => now(),
                'last_sender' => SupportConversation::SENDER_CSKH,
                'user_unread_count' => (int) $conv->user_unread_count + 1,
            ])->save();

            return $conv;
        });
    }

    /** User đã xem ⇒ xoá unread. */
    public function markUserRead(SupportConversation $conv): void
    {
        if ((int) $conv->user_unread_count !== 0) {
            $conv->forceFill(['user_unread_count' => 0])->save();
        }
    }

    /**
     * Validate tất cả file (MIME→kind + size). Ném AttachmentInvalid nếu có file sai.
     *
     * @param  array<int,UploadedFile>  $files
     * @return array<int,array{file:UploadedFile, kind:string, mime:string, size_bytes:int}>
     */
    private function validateFiles(array $files): array
    {
        $out = [];
        foreach ($files as $file) {
            $meta = $this->media->validate($file);
            $out[] = ['file' => $file] + $meta;
        }

        return $out;
    }

    /**
     * Lưu + ghi rows attachment cho 1 message. Cập nhật attachments_count.
     *
     * @param  array<int,array{file:UploadedFile, kind:string, mime:string, size_bytes:int}>  $validated
     */
    private function attachFiles(SupportMessage $msg, array $validated): void
    {
        if ($validated === []) {
            return;
        }

        foreach ($validated as $v) {
            $stored = $this->media->store((int) $msg->tenant_id, (int) $msg->support_conversation_id, $v['file'], $v['mime'], $v['kind']);
            SupportMessageAttachment::query()->create([
                'tenant_id' => $msg->tenant_id,
                'support_message_id' => $msg->getKey(),
                'kind' => $v['kind'],
                'mime' => $v['mime'],
                'size_bytes' => $v['size_bytes'],
                'storage_path' => $stored['storage_path'],
                'checksum' => $stored['checksum'],
                'filename' => $stored['filename'],
                'status' => SupportMessageAttachment::STATUS_STORED,
            ]);
        }

        $msg->forceFill(['attachments_count' => count($validated)])->save();
    }
}
