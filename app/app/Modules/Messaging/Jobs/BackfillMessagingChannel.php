<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\DTO\ConversationDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Backfill lịch sử hội thoại/tin nhắn cho 1 channel_account hỗ trợ
 * `inbound.backfill` (vd Facebook — webhook lo realtime, job này kéo lịch sử).
 *
 * Khác `SyncConversationsForShop` (polling, fire MessageReceived): job này KHÔNG
 * fire event (tránh auto-reply tin cũ) và truyền thread id qua $query['thread_id'].
 * Idempotent: ingest dedupe theo (conversation_id, external_message_id).
 *
 * $sinceIso: nếu set, dừng phân trang khi conversation updated_time < since (đối
 * soát incremental); nếu null, dùng cutoff cố định (messaging.backfill.days, mặc định 90).
 */
class BackfillMessagingChannel implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $uniqueFor = 900;

    public function __construct(public int $channelAccountId, public ?string $sinceIso = null)
    {
        $this->onQueue('messaging-sync');
    }

    public function uniqueId(): string
    {
        return "backfill:{$this->channelAccountId}";
    }

    public function handle(MessagingRegistry $registry, MessageIngestionService $ingestion): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);

        // (5) Resolve connector via messagingConnectorCode() — aligns with ReconcileMessagingSync.
        $code = $account?->messagingConnectorCode();
        if (! $account || $code === null || ! $registry->has($code)) {
            return;
        }
        $connector = $registry->for($code);
        if (! $connector->supports('inbound.backfill')) {
            return;
        }

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['channel_account_id' => (int) $account->getKey()],
            ['tenant_id' => (int) $account->tenant_id, 'messaging_enabled' => true],
        );

        // (2) Full backfill (sinceIso === null) resets the stale cursor from any prior run.
        if ($this->sinceIso === null) {
            $meta->forceFill(['sync_cursor' => null])->save();
        }

        $auth = new MessagingAuthContext(
            channelAccountId: (int) $account->getKey(),
            provider: $account->provider,
            externalShopId: (string) $account->external_shop_id,
            accessToken: (string) $account->access_token,
        );

        $meta->forceFill([
            'sync_status' => MessagingAccountMeta::SYNC_RUNNING,
            'sync_started_at' => $meta->sync_started_at ?? now(),
            'sync_error' => null,
        ])->save();

        // Avatar page (best-effort, queued) — skip khi đã relay (tránh re-download mỗi giờ).
        if ($meta->page_avatar_synced_at === null) {
            $pageProfile = $connector->fetchPageProfile($auth);
            if (! empty($pageProfile['avatar_url'])) {
                RelayMessagingAvatar::dispatch('page', (int) $account->getKey(), (string) $pageProfile['avatar_url']);
            }
        }

        $cutoff = $this->sinceIso
            ? Carbon::parse($this->sinceIso)
            : now()->subDays((int) config('messaging.backfill.days', 90));
        $perPage = (int) config('messaging.backfill.conversations_per_page', 25);
        $msgLimit = (int) config('messaging.backfill.messages_per_conversation', 50);
        $cursor = $meta->sync_cursor;

        try {
            do {
                $prevConvCursor = $cursor;
                $page = $connector->fetchConversations($auth, ['cursor' => $cursor, 'pageSize' => $perPage]);
                $reachedCutoff = false;

                foreach ($page->items as $convDto) {
                    /** @var ConversationDTO $convDto */
                    $updatedAt = $convDto->lastMessageAt;
                    if ($updatedAt !== null && $updatedAt->lt($cutoff)) {
                        $reachedCutoff = true;
                        break;
                    }

                    $threadId = (string) ($convDto->raw['fb_thread_id'] ?? '');
                    $conversation = $this->upsertConversation($account, $convDto, $threadId);
                    if ($conversation->blocked_at !== null) {
                        $meta->forceFill(['sync_done_conversations' => $meta->sync_done_conversations + 1])->save();

                        continue;
                    }

                    // Avatar buyer — skip cả Graph call lẫn dispatch khi ảnh đã lưu.
                    if ($conversation->buyer_avatar_path === null) {
                        $profile = $connector->fetchUserProfile($auth, $convDto->externalConversationId);
                        if (! empty($profile['avatar_url'])) {
                            RelayMessagingAvatar::dispatch('conversation', (int) $conversation->id, (string) $profile['avatar_url']);
                        }
                    }

                    $msgPage = $connector->fetchMessages($auth, $convDto->externalConversationId, [
                        'thread_id' => $threadId, 'pageSize' => $msgLimit,
                    ]);
                    foreach ($msgPage->items as $msgDto) {
                        $result = $ingestion->ingest($account, $msgDto);
                        if ($result['created']) {
                            $meta->forceFill(['sync_message_count' => $meta->sync_message_count + 1])->save();
                            // Relay media inbound — KHÔNG fire MessageReceived (không auto-reply tin cũ).
                            if ($result['message']->isInbound() && $result['message']->attachments_count > 0) {
                                /** @var Collection<int, MessageAttachment> $pending */
                                $pending = $result['message']->attachments()
                                    ->withoutGlobalScope(TenantScope::class)
                                    ->where('status', MessageAttachment::STATUS_PENDING)
                                    ->get();
                                $pending->each(fn (MessageAttachment $a) => DownloadInboundMedia::dispatch($a->id));
                            }
                        }
                    }

                    $meta->forceFill(['sync_done_conversations' => $meta->sync_done_conversations + 1])->save();
                }

                // (4) Cursor-didn't-advance guard — prevents infinite page loop.
                if ($page->nextCursor !== null && $page->nextCursor === $prevConvCursor) {
                    break;
                }

                $cursor = $page->nextCursor;
                $meta->forceFill(['sync_cursor' => $cursor])->save();
            } while (! $reachedCutoff && $cursor !== null && $page->hasMore);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'FACEBOOK_RATE_LIMIT')) {
                // (1) Set status to queued before releasing so the row is never stranded at 'running'.
                $meta->forceFill(['sync_status' => MessagingAccountMeta::SYNC_QUEUED])->save();
                $this->release(120);

                return;
            }
            $meta->forceFill([
                'sync_status' => MessagingAccountMeta::SYNC_FAILED,
                'sync_error' => substr($e->getMessage(), 0, 250),
            ])->save();
            Log::warning('messaging.backfill.failed', ['account' => $account->id, 'error' => $e->getMessage()]);

            return;
        }

        $meta->forceFill([
            'sync_status' => MessagingAccountMeta::SYNC_DONE,
            'sync_finished_at' => now(),
            'last_synced_at' => now(),
            'sync_cursor' => null,
        ])->save();
    }

    /**
     * (1) Terminal-state hook — when all tries are exhausted, land the row in SYNC_FAILED
     * instead of leaving it stranded at 'running' or 'queued'.
     */
    public function failed(\Throwable $e): void
    {
        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if ($meta && in_array($meta->sync_status, [MessagingAccountMeta::SYNC_RUNNING, MessagingAccountMeta::SYNC_QUEUED], true)) {
            $meta->forceFill(['sync_status' => MessagingAccountMeta::SYNC_FAILED, 'sync_error' => substr($e->getMessage(), 0, 250)])->save();
        }
    }

    private function upsertConversation(ChannelAccount $account, ConversationDTO $dto, string $threadId): Conversation
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->firstOrNew([
            'channel_account_id' => (int) $account->getKey(),
            'external_conversation_id' => $dto->externalConversationId,
        ]);
        if (! $conv->exists) {
            $conv->tenant_id = (int) $account->tenant_id;
            $conv->provider = $account->messagingConnectorCode() ?? $account->provider;
            $conv->buyer_external_id = $dto->buyerExternalId;
            $conv->status = Conversation::STATUS_OPEN;
            $conv->unread_count = 0;
            $conv->message_count = 0;
            $conv->last_message_at = $dto->lastMessageAt ?? now();
        }
        $conv->buyer_name = $dto->buyerName ?? $conv->buyer_name;
        $meta = (array) ($conv->meta ?? []);
        $meta['fb_thread_id'] = $threadId;
        $conv->meta = $meta;
        $conv->save();

        return $conv;
    }
}
