<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Messaging\Support\MessagingChannelGroup;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Nguồn sự thật DUY NHẤT cho "AI tự trả lời toàn cục có bật cho hội thoại này không".
 *
 * `ai_enabled` (tenant) + công tắc theo TỪNG PAGE `messaging_account_meta.ai_auto_mode`
 * (SPEC 0035); page chưa có meta ⇒ fallback cờ nhóm-tenant (`auto_mode_facebook` /
 * `auto_mode_marketplace`). Dùng ở CẢ listener (lúc nhận tin) LẪN job debounce (lúc gửi)
 * ⇒ "tắt là tắt", đóng khe đua bật→tắt trong lúc chờ debounce. Chạy ngoài tenant context
 * ⇒ withoutGlobalScope. KHÔNG áp cho node `ai_reply` trong flow (flow là cấp cao hơn,
 * gọi AiSuggestionService trực tiếp).
 */
class AiAutoModeResolver
{
    public function enabledFor(Conversation $conv): bool
    {
        $setting = MessagingSetting::withoutGlobalScope(TenantScope::class)->find($conv->tenant_id);
        if (! $setting || ! $setting->ai_enabled) {
            return false;
        }

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($conv->channel_account_id);
        if ($meta !== null) {
            return (bool) $meta->ai_auto_mode;
        }

        return MessagingChannelGroup::isFacebook($conv->provider)
            ? (bool) $setting->auto_mode_facebook
            : (bool) $setting->auto_mode_marketplace;
    }
}
