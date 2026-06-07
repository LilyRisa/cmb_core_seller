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
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
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
 *
 * GIỚI HẠN (chống job chạy mãi → timeout 600s → kênh kẹt 'running'): backfill dừng khi tới
 * mốc 90 ngày HOẶC đã đủ `max_conversations` (mặc định 500) — đủ 500 thì dừng, KHÔNG cần 90 ngày.
 * Mỗi lần chạy chỉ xử lý tối đa `max_pages_per_run` trang rồi TỰ dispatch job kế (self-chain) để
 * không vượt timeout; dùng ShouldBeUniqueUntilProcessing nên self-dispatch trong handle() chạy được
 * (lock nhả khi job bắt đầu). `$processed` mang số hội thoại đã xử lý qua các mắt xích để đếm cap 500.
 */
class BackfillMessagingChannel implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $uniqueFor = 900;

    public function __construct(
        public int $channelAccountId,
        public ?string $sinceIso = null,
        public int $processed = 0,
        public bool $isContinuation = false,
    ) {
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
        // Fresh backfill (KHÔNG phải mắt xích self-chain) ⇒ reset bộ đếm để cap 500 + hiển thị
        // phản ánh đúng lần sync này (cũng dọn số đếm bị phồng từ các lần treo trước).
        if (! $this->isContinuation) {
            $meta->forceFill(['sync_done_conversations' => 0, 'sync_message_count' => 0])->save();
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

        // Avatar page (best-effort). Lấy khi CHƯA relay (path) HOẶC thiếu URL CDN fallback —
        // page đã sync trước đây (synced_at set) vẫn cần điền page_avatar_url để hiển thị
        // khi storage URL không tới được. Chỉ DISPATCH relay khi chưa từng relay.
        // Bọc try-catch: avatar page là best-effort, KHÔNG được làm vỡ toàn bộ backfill
        // (vd lỗi lưu URL, mạng) — block này nằm NGOÀI try/catch chính bên dưới.
        if ($meta->page_avatar_synced_at === null || $meta->page_avatar_url === null) {
            try {
                $pageProfile = $connector->fetchPageProfile($auth);
                if (! empty($pageProfile['avatar_url'])) {
                    $meta->forceFill(['page_avatar_url' => (string) $pageProfile['avatar_url']])->save();
                    if ($meta->page_avatar_synced_at === null) {
                        RelayMessagingAvatar::dispatch('page', (int) $account->getKey(), (string) $pageProfile['avatar_url']);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('messaging.backfill.page_avatar_failed', ['account' => $account->id, 'error' => $e->getMessage()]);
            }
        }

        $cutoff = $this->sinceIso
            ? Carbon::parse($this->sinceIso)
            : now()->subDays((int) config('messaging.backfill.days', 90));
        $perPage = (int) config('messaging.backfill.conversations_per_page', 25);
        $msgLimit = (int) config('messaging.backfill.messages_per_conversation', 50);
        // Cap cứng: dừng khi đủ 500 hội thoại (KHÔNG cần tới mốc 90 ngày). Budget trang/run để 1 job
        // không vượt timeout 600s — còn thì self-chain ở cuối.
        $maxConversations = (int) config('messaging.backfill.max_conversations', 500);
        $maxPagesPerRun = (int) config('messaging.backfill.max_pages_per_run', 5);
        $cursor = $meta->sync_cursor;
        $processed = $this->processed;          // tổng hội thoại đã xử lý qua cả chuỗi self-chain
        $reachedCutoff = false;
        $reachedCap = false;
        $pagesThisRun = 0;

        try {
            do {
                $prevConvCursor = $cursor;
                $page = $connector->fetchConversations($auth, ['cursor' => $cursor, 'pageSize' => $perPage]);

                foreach ($page->items as $convDto) {
                    /** @var ConversationDTO $convDto */
                    // Đủ 500 hội thoại ⇒ dừng hẳn (bỏ qua điều kiện 90 ngày).
                    if ($processed >= $maxConversations) {
                        $reachedCap = true;
                        break;
                    }
                    $updatedAt = $convDto->lastMessageAt;
                    if ($updatedAt !== null && $updatedAt->lt($cutoff)) {
                        $reachedCutoff = true;
                        break;
                    }
                    $processed++;

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
                            // Fallback hiển thị ngay khi relay chưa xong / storage lỗi.
                            $conversation->forceFill(['buyer_avatar_url' => (string) $profile['avatar_url']])->save();
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

                if ($reachedCap) {
                    break;
                }

                // (4) Cursor-didn't-advance guard — prevents infinite page loop. Coi như hết trang.
                if ($page->nextCursor !== null && $page->nextCursor === $prevConvCursor) {
                    $cursor = null;
                    break;
                }

                $cursor = $page->nextCursor;
                $meta->forceFill(['sync_cursor' => $cursor])->save();
                $pagesThisRun++;
                // $reachedCap đã break ở trên ⇒ không cần lặp lại trong điều kiện.
            } while (! $reachedCutoff && $cursor !== null && $page->hasMore && $pagesThisRun < $maxPagesPerRun);
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

        // Dừng vì hết budget trang nhưng CHƯA tới cutoff/cap & còn trang ⇒ tiếp tục ở job kế (self-chain)
        // để không 1 job nào chạy quá timeout. Giữ trạng thái RUNNING + cursor đã lưu; mang theo $processed.
        if (! $reachedCutoff && ! $reachedCap && $cursor !== null && $page->hasMore) {
            static::dispatch($this->channelAccountId, $this->sinceIso, $processed, true);

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
