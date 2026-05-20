<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Jobs\SendMessage;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Một entry point DUY NHẤT cho việc ghi outbound message (text / template-resolved /
 * AI-accepted) ở trạng thái `pending` rồi dispatch `SendMessage`.
 *
 * Tách ra để KHÔNG có 2 codepath gửi tin song song (manual reply vs AI accept) —
 * tránh lệch logic header conversation / idempotency (SPEC-0024 §4.1). Window
 * guard + capability check vẫn ở controller (HTTP concern); service chỉ lo phần
 * "ghi DB trước, dispatch sau".
 */
class OutboundMessageService
{
    /**
     * @param  array{body:string, sent_by_user_id?:?int, sent_by_ai?:bool, message_tag?:?string, template_id?:?int, ai_run_id?:?int, auto_rule_id?:?int, kind?:string}  $opts
     */
    public function queueText(Conversation $conv, array $opts): Message
    {
        $body = (string) $opts['body'];

        $message = DB::transaction(function () use ($conv, $opts, $body) {
            $message = Message::create([
                'tenant_id' => $conv->tenant_id,
                'conversation_id' => $conv->id,
                'external_message_id' => null,
                'direction' => Message::DIRECTION_OUTBOUND,
                'kind' => $opts['kind'] ?? Message::KIND_TEXT,
                'body' => $body,
                'sent_by_user_id' => $opts['sent_by_user_id'] ?? null,
                'sent_by_ai' => $opts['sent_by_ai'] ?? false,
                'delivery_status' => Message::STATUS_PENDING,
                'meta' => array_filter([
                    'message_tag' => $opts['message_tag'] ?? null,
                    'template_id' => $opts['template_id'] ?? null,
                    'ai_run_id' => $opts['ai_run_id'] ?? null,
                    'auto_rule_id' => $opts['auto_rule_id'] ?? null,
                ]),
            ]);

            $conv->update([
                'last_message_at' => $message->created_at,
                'last_outbound_at' => $message->created_at,
                'last_message_preview' => Str::limit($body, 197),
                'message_count' => $conv->message_count + 1,
            ]);

            return $message;
        });

        SendMessage::dispatch($message->id);

        return $message;
    }
}
