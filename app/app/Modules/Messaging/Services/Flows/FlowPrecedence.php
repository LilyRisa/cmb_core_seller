<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * "Flow ưu tiên khi khớp" (quyết định SP 2026-06-30): nếu một flow CHIẾM hội thoại
 * này thì Tầng dưới (rule auto-reply phẳng + AI tự trả lời toàn cục) NHƯỜNG.
 *
 * Chiếm = đang có run active/waiting cho hội thoại HOẶC có flow inbox khớp tin này
 * (first_message / keyword / any). Catch-all `inbox_any` áp mọi trang ⇒ luôn khớp ⇒
 * AI tự tắt trên các trang đó (đúng ý 2.1). Chạy trong listener/job KHÔNG có tenant
 * context ⇒ withoutGlobalScope + FlowMatcher (đã query pivot trực tiếp).
 */
class FlowPrecedence
{
    public function __construct(private FlowMatcher $matcher) {}

    public function claims(Conversation $conv, ?string $body): bool
    {
        $activeRun = FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $conv->tenant_id)
            ->where('conversation_id', $conv->id)
            ->whereIn('status', [FlowRun::STATUS_ACTIVE, FlowRun::STATUS_WAITING])
            ->exists();
        if ($activeRun) {
            return true;
        }

        return $this->matcher->matching($conv, [
            AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            AutomationFlow::TRIGGER_INBOX_KEYWORD,
            AutomationFlow::TRIGGER_INBOX_ANY,
        ], $body)->isNotEmpty();
    }
}
