<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Jobs\SendMessage;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
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

    /**
     * Ghi 1 outbound media message (image/video/audio/file) từ media ĐÃ upload sẵn
     * (storage_path) rồi dispatch SendMessage. Dùng cho flow node gửi đa phương tiện:
     * file được upload lúc dựng kịch bản, runtime chỉ tạo Message + MessageAttachment
     * (status=downloaded) trỏ tới storage_path đó. SendMessage tự sinh signed URL.
     *
     * @param  array{kind:string, storage_path:string, mime?:?string, filename?:?string, size_bytes?:?int, width?:?int, height?:?int, duration_ms?:?int}  $media
     * @param  array{caption?:?string, sent_by_ai?:bool, message_tag?:?string, flow_id?:?int, flow_run_id?:?int, node_id?:?string}  $opts
     */
    public function queueMedia(Conversation $conv, array $media, array $opts = []): Message
    {
        $kind = (string) $media['kind'];
        $caption = isset($opts['caption']) && (string) $opts['caption'] !== '' ? (string) $opts['caption'] : null;

        $message = DB::transaction(function () use ($conv, $media, $kind, $caption, $opts) {
            $message = Message::create([
                'tenant_id' => $conv->tenant_id,
                'conversation_id' => $conv->id,
                'external_message_id' => null,
                'direction' => Message::DIRECTION_OUTBOUND,
                'kind' => $kind,
                'body' => $caption,
                'attachments_count' => 1,
                'sent_by_ai' => $opts['sent_by_ai'] ?? false,
                'delivery_status' => Message::STATUS_PENDING,
                'meta' => array_filter([
                    'message_tag' => $opts['message_tag'] ?? null,
                    'flow_id' => $opts['flow_id'] ?? null,
                    'flow_run_id' => $opts['flow_run_id'] ?? null,
                    'node_id' => $opts['node_id'] ?? null,
                ], fn ($v) => $v !== null),
            ]);

            MessageAttachment::create([
                'tenant_id' => $conv->tenant_id,
                'message_id' => $message->id,
                'kind' => $kind,
                'mime' => (string) ($media['mime'] ?? 'application/octet-stream'),
                'size_bytes' => isset($media['size_bytes']) ? (int) $media['size_bytes'] : null,
                'storage_path' => (string) $media['storage_path'],
                'filename' => $media['filename'] ?? null,
                'width' => isset($media['width']) ? (int) $media['width'] : null,
                'height' => isset($media['height']) ? (int) $media['height'] : null,
                'duration_ms' => isset($media['duration_ms']) ? (int) $media['duration_ms'] : null,
                'status' => MessageAttachment::STATUS_DOWNLOADED,
            ]);

            $conv->update([
                'last_message_at' => $message->created_at,
                'last_outbound_at' => $message->created_at,
                'last_message_preview' => Str::limit($caption ?? '['.$kind.']', 197),
                'message_count' => $conv->message_count + 1,
            ]);

            return $message;
        });

        SendMessage::dispatch($message->id);

        return $message;
    }

    /**
     * Ghi 1 outbound interactive message (tin có nút bấm) rồi dispatch SendMessage.
     * `structure` = { text, buttons:[ {type, title|label, payload?, url?} ] } — lưu
     * nguyên vào meta.interactive cho connector; meta.buttons chỉ giữ nhãn hiển thị
     * (đồng bộ cách inbox render nút như tin echo/quick-reply). SendMessage gate qua
     * InteractiveMessagingConnector + capability.
     *
     * @param  array{text?:string, buttons?:list<array<string,mixed>>}  $structure
     * @param  array{sent_by_ai?:bool, message_tag?:?string, flow_id?:?int, flow_run_id?:?int, node_id?:?string}  $opts
     */
    public function queueInteractive(Conversation $conv, array $structure, array $opts = []): Message
    {
        $text = (string) ($structure['text'] ?? '');

        $displayButtons = [];
        foreach ((array) ($structure['buttons'] ?? []) as $b) {
            $b = (array) $b;
            $title = (string) ($b['label'] ?? $b['title'] ?? '');
            if ($title === '') {
                continue;
            }
            $displayButtons[] = array_filter([
                'title' => $title,
                'url' => isset($b['url']) ? (string) $b['url'] : null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        $message = DB::transaction(function () use ($conv, $opts, $text, $structure, $displayButtons) {
            $message = Message::create([
                'tenant_id' => $conv->tenant_id,
                'conversation_id' => $conv->id,
                'external_message_id' => null,
                'direction' => Message::DIRECTION_OUTBOUND,
                'kind' => Message::KIND_INTERACTIVE,
                'body' => $text,
                'sent_by_ai' => $opts['sent_by_ai'] ?? false,
                'delivery_status' => Message::STATUS_PENDING,
                'meta' => array_filter([
                    'interactive' => $structure,
                    'buttons' => $displayButtons,
                    'message_tag' => $opts['message_tag'] ?? null,
                    'flow_id' => $opts['flow_id'] ?? null,
                    'flow_run_id' => $opts['flow_run_id'] ?? null,
                    'node_id' => $opts['node_id'] ?? null,
                ], fn ($v) => $v !== null && $v !== []),
            ]);

            $conv->update([
                'last_message_at' => $message->created_at,
                'last_outbound_at' => $message->created_at,
                'last_message_preview' => Str::limit($text, 197),
                'message_count' => $conv->message_count + 1,
            ]);

            return $message;
        });

        SendMessage::dispatch($message->id);

        return $message;
    }
}
