<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use CMBcoreSeller\Modules\Messaging\Listeners\RunAutoReplyOnComment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát khi 1 comment (bình luận) inbound MỚI được ingest (thread_type=comment).
 *
 * Tách KHỎI {@see MessageReceived} có chủ đích: comment KHÔNG kích auto-mode DM
 * (AiAutoModeOnInbound) — trả lời comment đi qua rule riêng (đích công khai/nhắn
 * riêng do rule cấu hình). Listener: {@see RunAutoReplyOnComment}.
 *
 * Provider-agnostic: bất kỳ nền tảng nào ingest comment-conversation đều phát
 * event này; engine kiểm capability connector trước khi gửi.
 */
class CommentReceived
{
    use Dispatchable;

    public function __construct(
        public int $messageId,
        public int $conversationId,
    ) {}
}
