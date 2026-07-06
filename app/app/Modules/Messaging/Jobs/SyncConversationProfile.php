<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lấy hồ sơ buyer (tên + avatar) cho 1 hội thoại DM rồi relay avatar về object
 * storage (`conversations.buyer_avatar_path`). Dùng cho path WEBHOOK realtime —
 * `MessageIngestionService::ensureConversation` tạo conversation KHÔNG có
 * name/avatar (khác `BackfillMessagingChannel` đã fetch sẵn). SPEC-0024 §6.4.
 *
 * Best-effort: connector.fetchUserProfile lỗi/thiếu quyền ⇒ trả null, không ném.
 * Throttle 24h (mốc `meta.profile_attempted_at`) tránh gọi Graph mỗi tin khi page
 * chưa được duyệt "Business Asset User Profile Access".
 *
 * Chỉ DM (`thread_type=message`); comment thread định danh bằng comment id, không
 * phải PSID nên không áp dụng fetchUserProfile.
 */
class SyncConversationProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $conversationId)
    {
        $this->onQueue('messaging-sync');
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(MessagingRegistry $registry, MessagingAvatarRelay $relay): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)
            ->find($this->conversationId);

        if (! $conv || $conv->thread_type === Conversation::THREAD_COMMENT) {
            return;
        }

        // Đã đủ tên + avatar ⇒ không cần fetch lại.
        if ($conv->buyer_avatar_path !== null && filled($conv->buyer_name)) {
            return;
        }

        // Throttle: chỉ thử mỗi 24h (tránh spam Graph khi page thiếu quyền profile).
        $meta = is_array($conv->meta) ? $conv->meta : [];
        $attemptedAt = isset($meta['profile_attempted_at'])
            ? CarbonImmutable::parse((string) $meta['profile_attempted_at'])
            : null;
        if ($attemptedAt !== null && $attemptedAt->gt(now()->subDay())) {
            return;
        }

        // Job queued chạy KHÔNG có CurrentTenant ⇒ nạp account BỎ TenantScope (như mọi job messaging khác:
        // SendMessage/SyncCommentAvatars…). Trước đây eager-load `$conv->channelAccount` dính TenantScope ⇒
        // null ⇒ job no-op ⇒ avatar realtime chưa từng chạy (bug prod 2026-07-02).
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($conv->channel_account_id);
        $code = $account?->messagingConnectorCode();
        if (! $account || $code === null || ! $registry->has($code)) {
            return;
        }
        $connector = $registry->for($code);

        $auth = new MessagingAuthContext(
            channelAccountId: (int) $account->getKey(),
            provider: (string) $account->provider,
            externalShopId: (string) $account->external_shop_id,
            accessToken: (string) $account->access_token,
        );

        // Best-effort (docblock): connector lỗi/thiếu quyền KHÔNG được ném — nếu không, chạy sync trong
        // luồng webhook (queue sync) sẽ làm webhook 500. FB connector đã tự nuốt lỗi; connector khác (Zalo…)
        // có thể ném ⇒ bọc ở đây làm lưới an toàn cho MỌI provider. Ném = transient ⇒ KHÔNG ghi mốc (thử lại).
        try {
            $profile = $connector->fetchUserProfile($auth, (string) $conv->external_conversation_id);
        } catch (Throwable $e) {
            Log::warning('messaging.sync_conversation_profile_failed', [
                'conversation' => $conv->getKey(), 'provider' => $account->provider, 'error' => $e->getMessage(),
            ]);

            return;
        }

        // Ghi mốc throttle 24h CHỈ khi provider trả lời DỨT KHOÁT (kể cả rỗng do thiếu quyền).
        // Lỗi thoáng qua (`attempted=false`: timeout/5xx/rate-limit) ⇒ KHÔNG ghi mốc ⇒ tin sau
        // (`maybeSyncBuyerProfile`) + `tries=2` còn thử lại — tránh "đầu độc 24h" khi hồ sơ vốn
        // lấy được (bug prod: hội thoại rỗng tên/avatar cả ngày dù Graph trả đủ). Connector cũ
        // không trả `attempted` ⇒ mặc định true (giữ nguyên hành vi throttle).
        if (($profile['attempted'] ?? true) !== false) {
            $meta['profile_attempted_at'] = now()->toIso8601String();
            $conv->forceFill(['meta' => $meta])->save();
        }

        $name = $profile['name'] ?? null;
        if (filled($name) && blank($conv->buyer_name)) {
            $conv->forceFill(['buyer_name' => (string) $name])->save();
        }

        $avatarUrl = $profile['avatar_url'] ?? null;
        if (filled($avatarUrl)) {
            // Lưu URL CDN Facebook làm FALLBACK hiển thị ngay (và khi relay lỗi / object
            // storage chưa cấu hình) — giống cơ chế `external_url` của media tin nhắn.
            // `ConversationResource` ưu tiên signed URL storage, fallback URL này.
            $conv->forceFill(['buyer_avatar_url' => (string) $avatarUrl])->save();

            if ($conv->buyer_avatar_path === null) {
                $path = $relay->relay((int) $conv->tenant_id, (string) $avatarUrl);
                if ($path !== null) {
                    $conv->forceFill(['buyer_avatar_path' => $path])->save();
                }
            }
        }
    }
}
