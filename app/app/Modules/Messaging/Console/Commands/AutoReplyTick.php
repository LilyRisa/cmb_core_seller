<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\AutoReplyEngine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Quét `away_no_response` mỗi phút (scheduler) — SPEC-0024 §6.4.
 *
 * `schedule`/`first_message`/`order_status` fire theo event (inbound / order
 * status). `away_no_response` cần sweep định kỳ vì điều kiện "NV chưa trả lời
 * sau N phút" không có event kích.
 *
 * Engine tự kiểm threshold per-rule + idempotency (window_key theo last_inbound_at)
 * ⇒ command chỉ feed conversation ứng viên (open, có inbound chưa được đáp).
 */
class AutoReplyTick extends Command
{
    protected $signature = 'messaging:auto-reply-tick {--limit=500}';

    protected $description = 'Quét away_no_response auto-reply (chạy mỗi phút qua scheduler).';

    public function handle(AutoReplyEngine $engine): int
    {
        $tenantIds = AutoReplyRule::withoutGlobalScope(TenantScope::class)
            ->where('trigger', AutoReplyRule::TRIGGER_AWAY_NO_RESPONSE)
            ->where('enabled', true)
            ->distinct()
            ->pluck('tenant_id');

        $fired = 0;
        foreach ($tenantIds as $tenantId) {
            $candidates = Conversation::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('status', Conversation::STATUS_OPEN)
                ->whereNotNull('last_inbound_at')
                ->where(function ($q) {
                    $q->whereNull('last_outbound_at')
                        ->orWhereColumn('last_outbound_at', '<', 'last_inbound_at');
                })
                ->where('last_inbound_at', '>=', now()->subDay()) // chỉ xét gần đây, tránh quét cũ
                ->limit((int) $this->option('limit'))
                ->get();

            foreach ($candidates as $conv) {
                if ($engine->fire($conv, AutoReplyRule::TRIGGER_AWAY_NO_RESPONSE) !== null) {
                    $fired++;
                }
            }
        }

        $this->info("auto-reply-tick: fired {$fired} away replies.");

        return self::SUCCESS;
    }
}
