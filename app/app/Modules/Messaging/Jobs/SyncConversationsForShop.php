<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Pull-side chat sync for one channel account. Pages through all conversations
 * (max 50 pages) and all messages per conversation (max 20 pages), funnelling
 * each MessageDTO into MessageIngestionService (idempotent upsert).
 *
 * Only connectors that support `inbound.polling` run — Shopee/TikTok/Facebook
 * are webhook-only and return false from supports('inbound.polling'), so this
 * job is a no-op for them (they receive chat via webhook pipeline instead).
 *
 * Lazada IM has no webhook push for buyer messages, so polling is the sole
 * reception path for Lazada chat.
 *
 * SPEC-0024 Phase C1. Mirror pattern: SyncOrdersForShop (Channels).
 */
class SyncConversationsForShop implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 900;

    public function __construct(public int $channelAccountId)
    {
        $this->onQueue('messaging-sync');
    }

    public function uniqueId(): string
    {
        return "sync-chat:{$this->channelAccountId}";
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(MessagingRegistry $registry, MessageIngestionService $ingest): void
    {
        /** @var ChannelAccount|null $account */
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if (! $account || $account->status !== ChannelAccount::STATUS_ACTIVE || ! $account->messaging_enabled) {
            return;
        }

        $code = $account->messagingConnectorCode();
        if ($code === null || ! $registry->has($code)) {
            return;
        }

        $connector = $registry->for($code);
        if (! $connector->supports('inbound.polling')) {
            // Webhook-only connectors (Shopee, TikTok, Facebook) — no-op, not an error.
            return;
        }

        /** @var MessagingAccountMeta $meta */
        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)
            ->firstOrCreate(
                ['channel_account_id' => $account->id],
                ['tenant_id' => $account->tenant_id],
            );

        $meta->sync_status = MessagingAccountMeta::SYNC_RUNNING;
        $meta->sync_started_at = now();
        $meta->sync_done_conversations = 0;
        $meta->sync_message_count = 0;
        $meta->sync_total_conversations = 0;
        $meta->sync_error = null;
        $meta->save();

        $runStart = CarbonImmutable::now();

        $auth = new MessagingAuthContext(
            channelAccountId: $account->id,
            provider: $account->provider,
            externalShopId: (string) $account->external_shop_id,
            accessToken: (string) $account->access_token,
        );

        $since = $meta->last_synced_at ? CarbonImmutable::parse($meta->last_synced_at) : null;

        try {
            $convCursor = null;
            $msgCount = 0;
            $doneConvs = 0;
            $maxConvPages = 50;

            for ($convPage = 0; $convPage < $maxConvPages; $convPage++) {
                $page = $connector->fetchConversations($auth, [
                    'since' => $since,
                    'cursor' => $convCursor,
                    'pageSize' => 50,
                ]);

                foreach ($page->items as $conv) {
                    // Page messages for this conversation (max 20 pages).
                    $mCursor = null;
                    $prevMCursor = null;
                    $maxMsgPages = 20;

                    for ($mPage = 0; $mPage < $maxMsgPages; $mPage++) {
                        $mPageResult = $connector->fetchMessages($auth, $conv->externalConversationId, [
                            'since' => $since,
                            'pageSize' => 50,
                            'cursor' => $mCursor,
                        ]);

                        foreach ($mPageResult->items as $dto) {
                            $res = $ingest->ingest($account, $dto);
                            if ($res['created']) {
                                $ingest->fireEventsForNewMessage(
                                    $res['conversation'],
                                    $res['message'],
                                    $res['conversation']->wasRecentlyCreated,
                                );
                                $msgCount++;
                            }
                        }

                        if (! $mPageResult->hasMore || ! $mPageResult->nextCursor) {
                            break;
                        }
                        // Defensive: stop if cursor didn't advance (prevents infinite re-fetch of same page).
                        if ($mPageResult->nextCursor === $prevMCursor) {
                            break;
                        }
                        $prevMCursor = $mCursor;
                        $mCursor = $mPageResult->nextCursor;
                    }

                    $doneConvs++;
                    $meta->sync_done_conversations = $doneConvs;
                    $meta->sync_message_count = $msgCount;
                    $meta->save();
                }

                // Save conversation cursor after each page.
                $meta->sync_cursor = $page->nextCursor;
                $meta->save();

                if (! $page->hasMore || ! $page->nextCursor) {
                    break;
                }
                // Defensive: stop if conversation cursor didn't advance (prevents infinite re-fetch of same page).
                if ($page->nextCursor === $convCursor) {
                    break;
                }
                $convCursor = $page->nextCursor;
            }

            $overlapMin = (int) config('integrations.sync.poll_overlap_minutes', 5);
            $meta->update([
                'last_synced_at' => $runStart->subMinutes($overlapMin),
                'sync_status' => MessagingAccountMeta::SYNC_DONE,
                'sync_finished_at' => now(),
                'sync_cursor' => null,
                'sync_total_conversations' => $doneConvs,
                'sync_done_conversations' => $doneConvs,
                'sync_message_count' => $msgCount,
            ]);
        } catch (Throwable $e) {
            $meta->sync_status = MessagingAccountMeta::SYNC_FAILED;
            $meta->sync_error = substr($e->getMessage(), 0, 500);
            $meta->save();

            throw $e;
        }
    }
}
