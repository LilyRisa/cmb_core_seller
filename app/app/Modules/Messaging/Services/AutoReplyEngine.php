<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRun;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageTemplate;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
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
    ) {}

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

        $rules = AutoReplyRule::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $conv->tenant_id)
            ->where('trigger', $trigger)
            ->where('enabled', true)
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

            $body = $this->resolveAction($rule, $conv, $context);
            if ($body === null || trim($body) === '') {
                $run->update(['status' => AutoReplyRun::STATUS_FAILED, 'error' => 'unresolved_action']);

                continue;
            }

            $message = $this->outbound->queueText($conv, [
                'body' => $body,
                'sent_by_user_id' => null,
                'sent_by_ai' => true,
                'auto_rule_id' => $rule->id,
            ]);

            $run->update(['message_id' => $message->id]);

            return $run; // 1 auto-reply / trigger event
        }

        return null;
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

        $rules = AutoReplyRule::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $conv->tenant_id)
            ->where('trigger', AutoReplyRule::TRIGGER_KEYWORD)
            ->where('enabled', true)
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

        // Sắp xếp: matched DESC, priority ASC, id ASC.
        usort($candidates, static function (array $a, array $b): int {
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

            $body = $this->resolveAction($rule, $conv, $context);
            if ($body === null || trim($body) === '') {
                $run->update(['status' => AutoReplyRun::STATUS_FAILED, 'error' => 'unresolved_action']);

                continue;
            }

            $message = $this->outbound->queueText($conv, [
                'body' => $body,
                'sent_by_user_id' => null,
                'sent_by_ai' => true,
                'auto_rule_id' => $rule->id,
            ]);

            $run->update(['message_id' => $message->id]);

            return $run;
        }

        return null;
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
            default => $now->format('Y-m-d-H-i-s'),
        };
    }

    /** @param array<string,mixed> $context */
    private function resolveAction(AutoReplyRule $rule, Conversation $conv, array $context): ?string
    {
        $action = $rule->action ?? [];
        $kind = $action['kind'] ?? AutoReplyRule::ACTION_RAW;

        if ($kind === AutoReplyRule::ACTION_RAW) {
            return (string) ($action['raw_text'] ?? '');
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

            return $this->resolver->resolve($template->body, $ctx)->text;
        }

        // ACTION_AI_REPLY: auto-gửi AI là S7 (cần guardrail intent). S5 KHÔNG auto-send AI.
        return null;
    }
}
