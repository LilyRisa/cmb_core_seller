<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Upsert a comment-thread conversation for Facebook Page.
 *
 * Extracted from BackfillFacebookComments so both the backfill job and the real-time
 * webhook processor (ProcessMessagingWebhook) share the same idempotent upsert logic.
 *
 * Contract:
 *   - Looks up `conversations` by (channel_account_id, external_conversation_id).
 *   - Creates a new row with thread_type='comment' if not found.
 *   - Always refreshes thread_type, buyer_name (if provided), and meta fields
 *     (fb_post_id, fb_comment_id — merges onto existing meta so callers can pass a
 *     subset and not clobber unrelated meta keys).
 *
 * Context array keys (all optional):
 *   - top_level_comment_id (string)  — the conversation id / top-level comment id
 *   - buyer_external_id  (string)
 *   - buyer_name         (?string)
 *   - fb_post_id         (?string)
 *   - fb_comment_id      (?string)
 *   - occurred_at        (?CarbonImmutable)
 */
class CommentConversationUpserter
{
    /**
     * @param  array<string, mixed>  $ctx
     */
    public function upsert(ChannelAccount $account, array $ctx): Conversation
    {
        $externalConvId = (string) ($ctx['top_level_comment_id'] ?? $ctx['fb_comment_id'] ?? '');
        $buyerExternalId = (string) ($ctx['buyer_external_id'] ?? '');
        $buyerName = isset($ctx['buyer_name']) && (string) $ctx['buyer_name'] !== ''
            ? (string) $ctx['buyer_name']
            : null;
        /** @var CarbonImmutable|null $occurredAt */
        $occurredAt = $ctx['occurred_at'] ?? null;

        $conv = Conversation::withoutGlobalScope(TenantScope::class)->firstOrNew([
            'channel_account_id' => (int) $account->getKey(),
            'external_conversation_id' => $externalConvId,
        ]);

        if (! $conv->exists) {
            $conv->tenant_id = (int) $account->tenant_id;
            $conv->provider = $account->messagingConnectorCode() ?? $account->provider;
            $conv->buyer_external_id = $buyerExternalId;
            $conv->status = Conversation::STATUS_OPEN;
            $conv->unread_count = 0;
            $conv->message_count = 0;
            $conv->last_message_at = $occurredAt ?? now();
        }

        $conv->thread_type = Conversation::THREAD_COMMENT;

        if ($buyerName !== null) {
            $conv->buyer_name = $buyerName;
        }

        $metaUpdates = array_filter([
            'fb_post_id' => isset($ctx['fb_post_id']) ? (string) $ctx['fb_post_id'] : null,
            'fb_comment_id' => isset($ctx['fb_comment_id']) ? (string) $ctx['fb_comment_id'] : null,
        ], fn ($v) => $v !== null);

        if ($metaUpdates !== []) {
            $conv->meta = array_merge((array) ($conv->meta ?? []), $metaUpdates);
        }

        $conv->save();

        return $conv;
    }
}
