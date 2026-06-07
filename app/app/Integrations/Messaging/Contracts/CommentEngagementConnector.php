<?php

namespace CMBcoreSeller\Integrations\Messaging\Contracts;

use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;

/**
 * Năng lực RIÊNG: tương tác sâu trên bình luận của trang — thích/bỏ thích 1 comment
 * và nhắn riêng nhiều phần (kèm media) cho người bình luận. Tách KHỎI
 * {@see MessagingConnector} — chỉ Facebook Page hỗ trợ; các sàn TMĐT (TikTok/Shopee/
 * Lazada) KHÔNG có khái niệm này nên KHÔNG bị buộc implement (Interface Segregation).
 *
 * GOLDEN RULE: core kiểm `instanceof CommentEngagementConnector` (tên NĂNG LỰC, không
 * phải tên sàn) trước khi gọi. Sàn nào về sau có "thích/nhắn riêng bình luận" chỉ cần
 * implement interface này — không sửa core.
 */
interface CommentEngagementConnector
{
    /**
     * Thích (`$like=true`) hoặc bỏ thích 1 comment bằng danh nghĩa Page. Idempotent:
     * thích cái đã thích / bỏ thích cái chưa thích ⇒ coi như xong (không ném).
     */
    public function likeComment(MessagingAuthContext $auth, string $commentId, bool $like): void;

    /**
     * Gửi tin nhắn riêng (nhiều phần) cho người bình luận, hỗ trợ media bất kỳ
     * (ảnh/video/file). Facebook chỉ cho nhắn riêng 1 lần / comment: phần ĐẦU gửi qua
     * `comment_id` (lấy PSID từ `recipient_id`), các phần SAU gửi qua PSID kèm
     * MESSAGE_TAG. Truyền `$psid` đã lưu (nếu có) để gửi thẳng. Bắt lỗi "đã nhắn riêng"
     * (10900) + cửa sổ đóng / bị chặn (idempotent, best-effort): dừng êm thay vì ném,
     * báo số phần đã gửi để caller phản hồi trung thực cho người dùng.
     *
     * @param  list<MediaRefDTO>  $attachments
     * @return array{psid: string, message_id: ?string, delivered: int, total: int} PSID + mid phần đầu (để ghi tin outbound vào DM) + số phần gửi được / tổng
     */
    public function sendCommentPrivateMessage(MessagingAuthContext $auth, string $commentId, ?string $psid, string $message, array $attachments = []): array;
}
