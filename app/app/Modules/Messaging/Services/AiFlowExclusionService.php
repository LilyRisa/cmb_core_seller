<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

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

    /**
     * SPEC 0035 — kích hoạt catch-all flow ⇒ tắt AI auto theo ĐÚNG các page của flow
     * (applies_all_pages ⇒ mọi FB page + cờ nhóm-tenant fallback; ngược lại ⇒ page đã gán).
     * Trả true nếu có thay đổi (để FE cảnh báo). Tránh tắt AI toàn tenant khi flow chỉ 1 page.
     */
    public function disableFacebookAiAutoForFlow(AutomationFlow $flow): bool
    {
        $tenantId = (int) $flow->tenant_id;
        $changed = false;

        if ($flow->applies_all_pages) {
            $pageIds = ChannelAccount::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->where('provider', 'facebook_page')->pluck('id');
            // Cờ nhóm-tenant (fallback cho page chưa có meta).
            $changed = $this->disableFacebookAiAuto($tenantId);
        } else {
            // Query pivot trực tiếp (tránh TenantScope của ChannelAccount khi không có tenant context).
            $pageIds = DB::table('automation_flow_page')
                ->where('automation_flow_id', $flow->id)->pluck('channel_account_id');
        }

        foreach ($pageIds as $pageId) {
            $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($pageId);
            if ($meta !== null && $meta->ai_auto_mode) {
                $meta->forceFill(['ai_auto_mode' => false])->save();
                $changed = true;
            }
        }

        return $changed;
    }

    /** Flow này có phải catch-all "Mọi tin nhắn" của Facebook không. */
    public function isFacebookCatchAll(AutomationFlow $flow): bool
    {
        return $flow->provider === 'facebook_page'
            && $flow->trigger_type === AutomationFlow::TRIGGER_INBOX_ANY;
    }
}
