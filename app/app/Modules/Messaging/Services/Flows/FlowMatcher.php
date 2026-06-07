<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Collection;

/**
 * Tìm các flow ACTIVE khớp 1 trigger cho 1 conversation. Chạy ngoài auth tenant ⇒
 * withoutGlobalScope + lọc tenant_id tường minh. KHÔNG hardcode tên provider —
 * provider lấy từ conversation, lọc theo flow.provider.
 */
class FlowMatcher
{
    /**
     * @param  list<string>  $triggerTypes  ưu tiên theo thứ tự truyền vào
     * @return Collection<int,AutomationFlow>
     */
    public function matching(Conversation $conv, array $triggerTypes, ?string $inboundBody = null): Collection
    {
        $pageId = (int) $conv->channel_account_id;

        $flows = AutomationFlow::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $conv->tenant_id)
            ->where('provider', $conv->provider)
            ->where('status', AutomationFlow::STATUS_ACTIVE)
            ->where('enabled', true)
            ->whereIn('trigger_type', $triggerTypes)
            // SPEC 0035 — chỉ flow áp mọi trang HOẶC gán page này (whereExists pivot, tránh
            // TenantScope của ChannelAccount khi chạy trong listener/job).
            ->where(fn ($q) => $q
                ->where('applies_all_pages', true)
                ->orWhereExists(fn ($sub) => $sub
                    ->selectRaw('1')
                    ->from('automation_flow_page')
                    ->whereColumn('automation_flow_page.automation_flow_id', 'automation_flows.id')
                    ->where('automation_flow_page.channel_account_id', $pageId)))
            // page-specific (false) trước "tất cả trang" (true) — sortBy bên dưới ổn định.
            ->orderByRaw('applies_all_pages asc')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (AutomationFlow $flow) => ($p = array_search($flow->trigger_type, $triggerTypes, true)) === false ? PHP_INT_MAX : $p)
            ->values();

        return $flows->filter(fn (AutomationFlow $flow) => $this->triggerConditionMet($flow, $conv, $inboundBody))->values();
    }

    private function triggerConditionMet(AutomationFlow $flow, Conversation $conv, ?string $inboundBody): bool
    {
        $cfg = (array) $flow->trigger_config;

        return match ($flow->trigger_type) {
            AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE => (int) $conv->message_count <= 1,
            AutomationFlow::TRIGGER_INBOX_ANY, AutomationFlow::TRIGGER_COMMENT_ANY => true,
            AutomationFlow::TRIGGER_INBOX_KEYWORD => $this->keywordHit($cfg, $inboundBody),
            AutomationFlow::TRIGGER_COMMENT_ON_POST => $this->postMatches($cfg, $conv),
            default => false,
        };
    }

    private function keywordHit(array $cfg, ?string $inboundBody): bool
    {
        $haystack = mb_strtolower((string) $inboundBody);
        if ($haystack === '') {
            return false;
        }
        foreach ((array) ($cfg['keywords'] ?? []) as $kw) {
            $kw = mb_strtolower(trim((string) $kw));
            if ($kw !== '' && str_contains($haystack, $kw)) {
                return true;
            }
        }

        return false;
    }

    private function postMatches(array $cfg, Conversation $conv): bool
    {
        $postIds = array_map('strval', (array) ($cfg['post_ids'] ?? []));
        if ($postIds === []) {
            return false;
        }
        $convPost = (string) (($conv->meta ?? [])['fb_post_id'] ?? '');

        return $convPost !== '' && in_array($convPost, $postIds, true);
    }
}
