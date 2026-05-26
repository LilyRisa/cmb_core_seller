<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRun;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Models\MessageDraft;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ngắt kết nối 1 Facebook Page: unsubscribe webhook (best-effort) rồi xoá hẳn
 * channel_account + cascade hội thoại/tin/đính kèm của page đó (design
 * 2026-05-20 §4.1). Tenant scope tự áp qua BelongsToTenant trong route auth.
 */
class FacebookPageDisconnectService
{
    public function __construct(private MediaStorage $media) {}

    /** @return array{conversations:int} */
    public function disconnect(ChannelAccount $account): array
    {
        $this->unsubscribeWebhook($account);

        $deletedConversations = 0;
        DB::transaction(function () use ($account, &$deletedConversations) {
            $convIds = Conversation::query()
                ->where('channel_account_id', $account->getKey())
                ->pluck('id');

            if ($convIds->isNotEmpty()) {
                $messageIds = Message::query()->whereIn('conversation_id', $convIds)->pluck('id');
                if ($messageIds->isNotEmpty()) {
                    $this->deleteAttachments($messageIds);
                    Message::query()->whereIn('id', $messageIds)->delete();
                }
                MessageDraft::query()->whereIn('conversation_id', $convIds)->delete();
                AutoReplyRun::query()->whereIn('conversation_id', $convIds)->delete();
                $deletedConversations = Conversation::query()->whereIn('id', $convIds)->delete();
            }
            MessagingAccountMeta::query()->where('channel_account_id', $account->getKey())->delete();
            $account->forceDelete(); // hard delete (model dùng SoftDeletes)
        });

        return ['conversations' => (int) $deletedConversations];
    }

    /** @param  Collection<int, int>  $messageIds */
    private function deleteAttachments(Collection $messageIds): void
    {
        MessageAttachment::query()->whereIn('message_id', $messageIds)
            ->each(function (MessageAttachment $att) {
                if ($att->storage_path) {
                    try {
                        $this->media->disk()->delete($att->storage_path);
                    } catch (\Throwable $e) {
                        Log::warning('messaging.disconnect.media_delete_failed', ['path' => $att->storage_path, 'error' => $e->getMessage()]);
                    }
                }
            });
        MessageAttachment::query()->whereIn('message_id', $messageIds)->delete();
    }

    private function unsubscribeWebhook(ChannelAccount $account): void
    {
        $token = (string) $account->access_token;
        if ($token === '') {
            return;
        }
        $version = (string) config('integrations.messaging_facebook_page.graph_version', 'v19.0');
        try {
            Http::timeout(15)->delete("https://graph.facebook.com/{$version}/{$account->external_shop_id}/subscribed_apps", [
                'access_token' => $token,
            ]);
        } catch (\Throwable $e) {
            Log::warning('messaging.disconnect.unsubscribe_failed', ['page' => $account->external_shop_id, 'error' => $e->getMessage()]);
        }
    }
}
