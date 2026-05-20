<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;

/**
 * Check outbound window rule trước khi cho phép gửi tin.
 *
 * Facebook Page: 24h kể từ last inbound; quá window ⇒ chỉ cho phép gửi
 * với `opts.message_tag` ∈ `allowed_tags`. Vi phạm ⇒ `OutboundWindowClosed`.
 *
 * SPEC-0024 §4.2.
 */
class OutboundWindowGuard
{
    /**
     * @param  array<string, mixed>  $opts  có thể chứa `message_tag` (Facebook)
     *
     * @throws OutboundWindowClosed
     */
    public function assertCanSend(MessagingConnector $connector, Conversation $conversation, array $opts = []): void
    {
        $policy = $connector->outboundWindow();

        if ($policy->freeWindowHours === null) {
            return; // provider không có hard window
        }

        $lastInbound = $conversation->last_inbound_at;
        if (! $lastInbound) {
            // Chưa có inbound (vd conversation chỉ có outbound khởi xướng) — connector
            // cụ thể có thể yêu cầu tag từ đầu. Coi như "window closed" để an toàn.
            $hoursSince = $policy->freeWindowHours + 1;
        } else {
            $hoursSince = (int) floor(CarbonImmutable::now()->diffInMinutes($lastInbound) / 60);
        }

        if ($hoursSince < $policy->freeWindowHours) {
            return; // còn trong window
        }

        if (! $policy->requiresTag) {
            return; // provider không yêu cầu tag
        }

        $tag = is_string($opts['message_tag'] ?? null) ? (string) $opts['message_tag'] : null;

        if ($tag && in_array($tag, $policy->allowedTags, true)) {
            return; // hợp lệ với tag
        }

        throw OutboundWindowClosed::for($connector->code(), $hoursSince);
    }
}
