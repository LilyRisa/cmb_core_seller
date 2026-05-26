<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Jobs\SendCommentReply;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;

/**
 * Entry point cho gửi reply lên 1 comment thread (auto-reply). Tách khỏi engine
 * để đường gửi comment (public/private qua connector) không lẫn với đường gửi DM
 * (`OutboundMessageService::queueText`).
 *
 * Provider-agnostic — chỉ dispatch job; job kiểm capability connector.
 */
class CommentReplyService
{
    /**
     * @param  array{public?:bool, private?:bool}  $target
     * @param  array{auto_rule_id?:?int, ai_run_id?:?int}  $opts
     */
    public function dispatch(Conversation $conv, string $body, array $target, array $opts = []): void
    {
        $public = (bool) ($target['public'] ?? false);
        $private = (bool) ($target['private'] ?? false);

        // Mặc định an toàn: nếu rule không khai báo đích nào ⇒ trả lời công khai.
        if (! $public && ! $private) {
            $public = true;
        }

        SendCommentReply::dispatch(
            conversationId: (int) $conv->id,
            body: $body,
            public: $public,
            private: $private,
            autoRuleId: $opts['auto_rule_id'] ?? null,
            aiRunId: $opts['ai_run_id'] ?? null,
        );
    }
}
