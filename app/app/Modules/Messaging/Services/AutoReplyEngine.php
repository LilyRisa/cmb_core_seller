<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRun;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageTemplate;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;

/**
 * Engine auto-reply — fire rule khớp trigger cho 1 conversation.
 *
 * ROUTE-AROUND (SPEC-0024 §4.9 / §0): Phase 6.5 AutomationRule engine CHƯA tồn
 * tại trong repo. Thay vì chặn S5, engine này xử lý 4 trigger trực tiếp
 * (standalone). Khi rules engine generic ra đời, chuyển sang dispatch
 * `MessagingTriggerFired` → engine đó (1 đường thay thế, không phá API).
 *
 * Idempotency: UNIQUE `(rule_id, conversation_id, window_key)` ⇒ insert đụng =
 * đã fire window đó ⇒ skip. Cooldown: không fire lại trong `cooldown_seconds`
 * per (rule, conversation). Chống spam khách (§4.3).
 *
 * Chạy trong listener/job/command — KHÔNG có auth tenant ⇒ mọi query
 * `withoutGlobalScope(TenantScope)` + lọc tenant_id tường minh (tenant lấy từ
 * conversation).
 *
 * KHÔNG bao giờ fire trên message do auto-reply gửi ra: listener chỉ nghe INBOUND;
 * outbound auto-reply gắn `meta.auto_rule_id` để audit/loại trừ về sau.
 */
class AutoReplyEngine
{
    public function __construct(
        private OutboundMessageService $outbound,
        private TemplateResolver $resolver,
        private TemplateContextBuilder $contextBuilder,
        private CommentReplyService $commentReply,
        private AiSuggestionService $ai,
    ) {}

    /**
     * SPEC 0035 — lọc rule theo PAGE của conversation: chỉ rule `applies_all_pages`
     * HOẶC có gán page `$conv->channel_account_id`. Ưu tiên rule gắn page cụ thể
     * (applies_all_pages=false) đứng TRƯỚC rule "tất cả trang".
     *
     * @param  Builder<AutoReplyRule>  $query
     * @return Builder<AutoReplyRule>
     */
    private function scopeToPage(Builder $query, Conversation $conv): Builder
    {
        $pageId = (int) $conv->channel_account_id;

        // whereExists trên pivot trực tiếp (KHÔNG qua relation ChannelAccount) — tránh
        // TenantScope của ChannelAccount lọc rỗng khi chạy trong job không có tenant context.
        return $query
            ->where(fn (Builder $q) => $q
                ->where('applies_all_pages', true)
                ->orWhereExists(fn ($sub) => $sub
                    ->selectRaw('1')
                    ->from('auto_reply_rule_page')
                    ->whereColumn('auto_reply_rule_page.auto_reply_rule_id', 'auto_reply_rules.id')
                    ->where('auto_reply_rule_page.channel_account_id', $pageId)))
            ->orderByRaw('applies_all_pages asc');
    }

    /**
     * Fire rule ưu tiên cao nhất khớp `$trigger`. Trả `AutoReplyRun` đã fire, hoặc null.
     *
     * @param  array<string,mixed>  $context  trigger-specific: order_status, now, ...
     */
    public function fire(Conversation $conv, string $trigger, array $context = []): ?AutoReplyRun
    {
        $now = isset($context['now']) ? CarbonImmutable::parse($context['now']) : CarbonImmutable::now();

        // keyword trigger có logic chọn rule đặc biệt (Feature G+H) — tách riêng.
        if ($trigger === AutoReplyRule::TRIGGER_KEYWORD) {
            return $this->fireKeyword($conv, $context, $now);
        }

        $rules = $this->scopeToPage(
            AutoReplyRule::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $conv->tenant_id)
                ->where('trigger', $trigger)
                ->where('enabled', true),
            $conv,
        )
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->matchesFilter($rule, $conv, $context)) {
                continue;
            }
            if (! $this->conditionMet($rule, $conv, $context, $now)) {
                continue;
            }
            if ($this->inCooldown($rule, $conv, $now)) {
                continue;
            }

            $windowKey = $this->windowKey($rule, $conv, $context, $now);

            // Idempotency: chiếm slot trước (unique). Đụng = window đã fire ⇒ skip.
            try {
                $run = AutoReplyRun::create([
                    'tenant_id' => $conv->tenant_id,
                    'rule_id' => $rule->id,
                    'conversation_id' => $conv->id,
                    'window_key' => $windowKey,
                    'fired_at' => $now,
                    'status' => AutoReplyRun::STATUS_FIRED,
                ]);
            } catch (QueryException) {
                continue;
            }

            $resolved = $this->resolveAction($rule, $conv, $context);
            if ($resolved === null || trim($resolved['body']) === '') {
                $run->update(['status' => AutoReplyRun::STATUS_FAILED, 'error' => 'unresolved_action']);

                continue;
            }

            $this->deliver($conv, $rule, $resolved, $run);

            return $run; // 1 auto-reply / trigger event
        }

        return null;
    }

    /**
     * Có rule enabled nào KHỚP `$trigger` cho conversation/message này không —
     * KHÔNG fire, KHÔNG tính cooldown/idempotency. Dùng cho responder ưu tiên thấp
     * hơn (AI auto-mode) biết có nên nhường Tầng 1 hay không (ADR-0022 §3).
     *
     * @param  array<string,mixed>  $context  cần `inbound_body` cho keyword.
     */
    public function matches(Conversation $conv, string $trigger, array $context = []): bool
    {
        $now = isset($context['now']) ? CarbonImmutable::parse($context['now']) : CarbonImmutable::now();

        if ($trigger === AutoReplyRule::TRIGGER_KEYWORD) {
            $haystack = mb_strtolower((string) ($context['inbound_body'] ?? $conv->last_message_preview ?? ''));
            $rules = $this->scopeToPage(
                AutoReplyRule::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $conv->tenant_id)
                    ->where('trigger', AutoReplyRule::TRIGGER_KEYWORD)
                    ->where('enabled', true),
                $conv,
            )->get();

            foreach ($rules as $rule) {
                if ($this->matchesFilter($rule, $conv, $context) && $this->countMatchedKeywords($rule, $haystack) > 0) {
                    return true;
                }
            }

            return false;
        }

        $rules = $this->scopeToPage(
            AutoReplyRule::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $conv->tenant_id)
                ->where('trigger', $trigger)
                ->where('enabled', true),
            $conv,
        )->get();

        foreach ($rules as $rule) {
            if ($this->matchesFilter($rule, $conv, $context) && $this->conditionMet($rule, $conv, $context, $now)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Feature G+H — fire keyword trigger.
     *
     * Chọn rule khớp nhiều từ khoá nhất (highest matched-keyword count); tie-break
     * bằng `priority` ASC rồi `id` ASC. Chỉ fire 1 keyword-rule (most-specific wins).
     *
     * @param  array<string,mixed>  $context
     */
    private function fireKeyword(Conversation $conv, array $context, CarbonImmutable $now): ?AutoReplyRun
    {
        $haystack = mb_strtolower((string) ($context['inbound_body'] ?? $conv->last_message_preview ?? ''));

        $rules = $this->scopeToPage(
            AutoReplyRule::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $conv->tenant_id)
                ->where('trigger', AutoReplyRule::TRIGGER_KEYWORD)
                ->where('enabled', true),
            $conv,
        )
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        // Tính matched count cho mỗi rule, lọc ra rule khớp ít nhất 1 từ khoá.
        $candidates = [];
        foreach ($rules as $rule) {
            if (! $this->matchesFilter($rule, $conv, $context)) {
                continue;
            }
            $matchedCount = $this->countMatchedKeywords($rule, $haystack);
            if ($matchedCount === 0) {
                continue;
            }
            if ($this->inCooldown($rule, $conv, $now)) {
                continue;
            }
            $candidates[] = ['rule' => $rule, 'matched' => $matchedCount];
        }

        if ($candidates === []) {
            return null;
        }

        // Sắp xếp: page-specific TRƯỚC (SPEC 0035), rồi matched DESC, priority ASC, id ASC.
        usort($candidates, static function (array $a, array $b): int {
            // applies_all_pages=false (page cụ thể) ưu tiên hơn =true (tất cả trang).
            if ($a['rule']->applies_all_pages !== $b['rule']->applies_all_pages) {
                return ($a['rule']->applies_all_pages ? 1 : 0) <=> ($b['rule']->applies_all_pages ? 1 : 0);
            }
            if ($b['matched'] !== $a['matched']) {
                return $b['matched'] <=> $a['matched'];
            }
            if ($a['rule']->priority !== $b['rule']->priority) {
                return $a['rule']->priority <=> $b['rule']->priority;
            }

            return $a['rule']->id <=> $b['rule']->id;
        });

        foreach ($candidates as $candidate) {
            $rule = $candidate['rule'];
            $windowKey = $this->windowKey($rule, $conv, $context, $now);

            try {
                $run = AutoReplyRun::create([
                    'tenant_id' => $conv->tenant_id,
                    'rule_id' => $rule->id,
                    'conversation_id' => $conv->id,
                    'window_key' => $windowKey,
                    'fired_at' => $now,
                    'status' => AutoReplyRun::STATUS_FIRED,
                ]);
            } catch (QueryException) {
                continue; // window đã fire (idempotency) ⇒ thử candidate tiếp
            }

            $resolved = $this->resolveAction($rule, $conv, $context);
            if ($resolved === null || trim($resolved['body']) === '') {
                $run->update(['status' => AutoReplyRun::STATUS_FAILED, 'error' => 'unresolved_action']);

                continue;
            }

            $this->deliver($conv, $rule, $resolved, $run);

            return $run;
        }

        return null;
    }

    /**
     * Gửi auto-reply đã resolve. Comment thread → đường công khai/nhắn riêng
     * (CommentReplyService); DM → OutboundMessageService::queueText. Provider khác
     * biệt nằm trong connector (capability), engine không biết tên nền tảng.
     *
     * @param  array{body:string, ai_run_id:?int}  $resolved
     */
    private function deliver(Conversation $conv, AutoReplyRule $rule, array $resolved, AutoReplyRun $run): void
    {
        if ($conv->thread_type === Conversation::THREAD_COMMENT) {
            $target = (array) (($rule->action ?? [])['comment_target'] ?? ['public' => true, 'private' => false]);
            $this->commentReply->dispatch($conv, $resolved['body'], $target, [
                'auto_rule_id' => $rule->id,
                'ai_run_id' => $resolved['ai_run_id'],
            ]);

            // message_id link async (job ingest reply công khai) — run đã ghi `fired`.
            return;
        }

        $message = $this->outbound->queueText($conv, [
            'body' => $resolved['body'],
            'sent_by_user_id' => null,
            'sent_by_ai' => true,
            'auto_rule_id' => $rule->id,
            'ai_run_id' => $resolved['ai_run_id'],
        ]);

        $run->update(['message_id' => $message->id]);
    }

    /**
     * Đếm số từ khoá trong `trigger_config.keywords` xuất hiện trong `$haystack`
     * (đã lowercase). Trim + bỏ qua keyword rỗng.
     */
    private function countMatchedKeywords(AutoReplyRule $rule, string $haystack): int
    {
        $keywords = (array) (($rule->trigger_config ?? [])['keywords'] ?? []);
        $count = 0;
        foreach ($keywords as $kw) {
            $kw = trim((string) $kw);
            if ($kw !== '' && str_contains($haystack, mb_strtolower($kw))) {
                $count++;
            }
        }

        return $count;
    }

    /** @param array<string,mixed> $context */
    private function matchesFilter(AutoReplyRule $rule, Conversation $conv, array $context): bool
    {
        $filter = $rule->filter ?? [];

        $providers = $filter['providers'] ?? [];
        if ($providers !== [] && ! in_array($conv->provider, $providers, true)) {
            return false;
        }

        // thread_types: comment YÊU CẦU khai báo tường minh (chống rule DM cũ vô tình
        // đăng công khai); DM giữ tương thích ngược (vắng = áp dụng).
        $threadTypes = (array) ($filter['thread_types'] ?? []);
        $convThread = $conv->thread_type ?: Conversation::THREAD_MESSAGE;
        if ($convThread === Conversation::THREAD_COMMENT) {
            if (! in_array(Conversation::THREAD_COMMENT, $threadTypes, true)) {
                return false;
            }
        } elseif ($threadTypes !== [] && ! in_array(Conversation::THREAD_MESSAGE, $threadTypes, true)) {
            return false;
        }

        $keywords = $filter['keywords'] ?? [];
        if ($keywords !== []) {
            $haystack = mb_strtolower((string) ($context['inbound_body'] ?? $conv->last_message_preview ?? ''));
            $hit = false;
            foreach ($keywords as $kw) {
                if ($kw !== '' && str_contains($haystack, mb_strtolower((string) $kw))) {
                    $hit = true;
                    break;
                }
            }
            if (! $hit) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string,mixed> $context */
    private function conditionMet(AutoReplyRule $rule, Conversation $conv, array $context, CarbonImmutable $now): bool
    {
        $cfg = $rule->trigger_config ?? [];

        return match ($rule->trigger) {
            AutoReplyRule::TRIGGER_FIRST_MESSAGE => (int) $conv->message_count <= 1,
            AutoReplyRule::TRIGGER_ORDER_STATUS => ($cfg['order_status'] ?? null) === ($context['order_status'] ?? null),
            AutoReplyRule::TRIGGER_SCHEDULE => $this->inScheduleWindow($cfg, $now),
            AutoReplyRule::TRIGGER_AWAY_NO_RESPONSE => $this->awayThresholdMet($cfg, $conv, $now),
            // comment_any: mọi bình luận mới (thread_type đã lọc ở matchesFilter).
            AutoReplyRule::TRIGGER_COMMENT_ANY => $conv->thread_type === Conversation::THREAD_COMMENT,
            // keyword: matching đã xử lý riêng trong fireKeyword() — không vào đây.
            AutoReplyRule::TRIGGER_KEYWORD => false,
            default => false,
        };
    }

    /** @param array<string,mixed> $cfg */
    private function inScheduleWindow(array $cfg, CarbonImmutable $now): bool
    {
        $window = (string) ($cfg['window'] ?? '');
        if (! str_contains($window, '-')) {
            return false;
        }
        $tz = (string) ($cfg['tz'] ?? 'Asia/Ho_Chi_Minh');
        $local = $now->setTimezone($tz);

        $days = $cfg['days'] ?? [];
        if ($days !== [] && ! in_array(strtolower($local->format('D')), array_map('strtolower', $days), true)
            && ! in_array(strtolower($local->format('l')), array_map('strtolower', $days), true)) {
            // chấp nhận 'mon' (D) lẫn 'monday' (l)
            $shortDay = strtolower(substr($local->format('l'), 0, 3));
            if (! in_array($shortDay, array_map('strtolower', $days), true)) {
                return false;
            }
        }

        [$from, $to] = array_map('trim', explode('-', $window, 2));
        $cur = $local->format('H:i');

        // Window qua nửa đêm (22:00-08:00) ⇒ cur >= from HOẶC cur < to.
        if ($from > $to) {
            return $cur >= $from || $cur < $to;
        }

        return $cur >= $from && $cur < $to;
    }

    /** @param array<string,mixed> $cfg */
    private function awayThresholdMet(array $cfg, Conversation $conv, CarbonImmutable $now): bool
    {
        $minutes = (int) ($cfg['minutes'] ?? 15);
        if (! $conv->last_inbound_at) {
            return false;
        }
        // Đã có outbound sau inbound cuối ⇒ NV đã trả lời ⇒ không away.
        if ($conv->last_outbound_at && $conv->last_outbound_at->greaterThanOrEqualTo($conv->last_inbound_at)) {
            return false;
        }

        return $conv->last_inbound_at->lessThanOrEqualTo($now->subMinutes($minutes));
    }

    private function inCooldown(AutoReplyRule $rule, Conversation $conv, CarbonImmutable $now): bool
    {
        $cooldown = (int) ($rule->cooldown_seconds ?? 0);
        if ($cooldown <= 0) {
            return false;
        }

        $lastFiredAt = AutoReplyRun::withoutGlobalScope(TenantScope::class)
            ->where('rule_id', $rule->id)
            ->where('conversation_id', $conv->id)
            ->where('status', AutoReplyRun::STATUS_FIRED)
            ->max('fired_at');

        if (! $lastFiredAt) {
            return false;
        }

        return CarbonImmutable::parse($lastFiredAt)->greaterThan($now->subSeconds($cooldown));
    }

    /** @param array<string,mixed> $context */
    private function windowKey(AutoReplyRule $rule, Conversation $conv, array $context, CarbonImmutable $now): string
    {
        return match ($rule->trigger) {
            AutoReplyRule::TRIGGER_FIRST_MESSAGE => 'first',
            AutoReplyRule::TRIGGER_ORDER_STATUS => 'order:'.($context['order_id'] ?? '0').':status:'.($context['order_status'] ?? ''),
            AutoReplyRule::TRIGGER_AWAY_NO_RESPONSE => 'away:'.$conv->id.':'.optional($conv->last_inbound_at)->timestamp,
            AutoReplyRule::TRIGGER_SCHEDULE => 'sched:'.$now->format('Y-m-d-H'),
            // keyword: idempotency per message (dùng hash body để tránh double fire cùng tin)
            AutoReplyRule::TRIGGER_KEYWORD => 'kw:'.md5((string) ($context['inbound_body'] ?? '')),
            // comment_any: idempotency per comment (external_message_id), fallback message_id.
            AutoReplyRule::TRIGGER_COMMENT_ANY => 'cmt:'.($context['external_message_id'] ?? $context['message_id'] ?? md5((string) ($context['inbound_body'] ?? ''))),
            default => $now->format('Y-m-d-H-i-s'),
        };
    }

    /**
     * Resolve nội dung gửi. Trả `['body' => ..., 'ai_run_id' => ?int]` hoặc null
     * khi không resolve được / AI escalate.
     *
     * @param  array<string,mixed>  $context
     * @return array{body:string, ai_run_id:?int}|null
     */
    private function resolveAction(AutoReplyRule $rule, Conversation $conv, array $context): ?array
    {
        $action = $rule->action ?? [];
        $kind = $action['kind'] ?? AutoReplyRule::ACTION_RAW;

        if ($kind === AutoReplyRule::ACTION_RAW) {
            return ['body' => (string) ($action['raw_text'] ?? ''), 'ai_run_id' => null];
        }

        if ($kind === AutoReplyRule::ACTION_TEMPLATE) {
            $template = MessageTemplate::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $conv->tenant_id)
                ->where('id', $action['template_id'] ?? 0)
                ->where('enabled', true)
                ->first();
            if (! $template) {
                return null;
            }
            $ctx = $this->contextBuilder->forConversation($conv, (array) ($action['vars'] ?? []));

            return ['body' => $this->resolver->resolve($template->body, $ctx)->text, 'ai_run_id' => null];
        }

        if ($kind === AutoReplyRule::ACTION_AI_REPLY) {
            // AI tự sinh nội dung qua CÙNG guardrail intent với auto-mode DM. Escalate
            // (complaint/refund/urgent/legal/abuse) ⇒ không gửi (để NV). Provider lỗi /
            // hết hạn mức ⇒ skip im lặng (không spam, không ném).
            $inbound = (string) ($context['inbound_body'] ?? '');
            if (trim($inbound) === '') {
                return null;
            }
            try {
                $r = $this->ai->draftAutoReply($conv, $inbound);
            } catch (\Throwable) {
                return null;
            }
            if ($r['action'] !== 'generated' || ($r['body'] ?? '') === '') {
                return null;
            }

            return ['body' => (string) $r['body'], 'ai_run_id' => $r['run_id'] ?? null];
        }

        return null;
    }
}
