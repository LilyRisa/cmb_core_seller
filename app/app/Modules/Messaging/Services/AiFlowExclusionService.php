<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Loại trừ lẫn nhau Tầng 2 (ADR-0022 §4): AI auto-reply-all (Facebook) XOR flow
 * `inbox_any` (Facebook). Bật cái này ⇒ tắt cái kia. MỘT CHIỀU: tắt về sau KHÔNG
 * tự bật lại cái kia (tránh kích hoạt bất ngờ).
 *
 * Chạy trong controller (đã có auth tenant) nhưng vẫn withoutGlobalScope + lọc
 * tenant_id tường minh cho nhất quán (controller có thể đổi luồng sau).
 */
class AiFlowExclusionService
{
    /**
     * Bật AI tự động Facebook ⇒ tạm dừng (pause) mọi flow `inbox_any` provider
     * facebook_page đang active. Trả số flow đã pause.
     */
    public function pauseFacebookCatchAllFlows(int $tenantId): int
    {
        return AutomationFlow::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('provider', 'facebook_page')
            ->where('trigger_type', AutomationFlow::TRIGGER_INBOX_ANY)
            ->where('status', AutomationFlow::STATUS_ACTIVE)
            ->update(['status' => AutomationFlow::STATUS_PAUSED]);
    }

    /**
     * Kích hoạt flow `inbox_any` Facebook ⇒ tắt AI tự động Facebook. Trả true nếu
     * trước đó đang bật (để FE cảnh báo).
     */
    public function disableFacebookAiAuto(int $tenantId): bool
    {
        $setting = MessagingSetting::withoutGlobalScope(TenantScope::class)->find($tenantId);
        if (! $setting || ! $setting->auto_mode_facebook) {
            return false;
        }

        $setting->auto_mode_facebook = false;
        $setting->save();

        return true;
    }

    /** Flow này có phải catch-all "Mọi tin nhắn" của Facebook không. */
    public function isFacebookCatchAll(AutomationFlow $flow): bool
    {
        return $flow->provider === 'facebook_page'
            && $flow->trigger_type === AutomationFlow::TRIGGER_INBOX_ANY;
    }
}
