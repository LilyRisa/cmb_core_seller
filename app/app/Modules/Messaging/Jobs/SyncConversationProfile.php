<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
            ->with('channelAccount')
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

        $account = $conv->channelAccount;
        $code = $account?->messagingConnectorCode();
        if (! $account || $code === null || ! $registry->has($code)) {
            return;
        }
        $connector = $registry->for($code);

        // Ghi mốc thử TRƯỚC khi gọi Graph ⇒ throttle vẫn áp kể cả khi job lỗi/null.
        $meta['profile_attempted_at'] = now()->toIso8601String();
        $conv->forceFill(['meta' => $meta])->save();

        $auth = new MessagingAuthContext(
            channelAccountId: (int) $account->getKey(),
            provider: (string) $account->provider,
            externalShopId: (string) $account->external_shop_id,
            accessToken: (string) $account->access_token,
        );

        $profile = $connector->fetchUserProfile($auth, (string) $conv->external_conversation_id);

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
