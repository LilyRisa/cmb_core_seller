<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Messaging webhook receive path: verify chữ ký → store webhook_event verbatim
 * → 200 fast → process async qua `ProcessMessagingWebhook`.
 *
 * Dùng chung bảng `webhook_events` với Channels (rule 9 partition trong tương lai
 * vẫn áp), nhưng provider prefix `messaging.<code>` để phân biệt.
 *
 * Dedupe theo `(provider, event_type, external_id)` — `external_id` = external
 * message id (an toàn hơn conversation id vì 2 tin khác nhau cùng conv).
 *
 * Mirror `Channels\Services\WebhookIngestService`. SPEC-0024 §6.1.
 */
class MessagingWebhookIngestService
{
    public function __construct(private MessagingRegistry $registry) {}

    /** @return array{status:int, body:array<string,mixed>} */
    public function ingest(string $provider, Request $request): array
    {
        if (! $this->registry->has($provider)) {
            return [
                'status' => 404,
                'body' => ['error' => ['code' => 'UNKNOWN_MESSAGING_PROVIDER', 'message' => "Unknown messaging provider [{$provider}]."]],
            ];
        }
        $connector = $this->registry->for($provider);

        if (! $connector->verifyWebhookSignature($request)) {
            Log::warning('messaging.webhook.signature_invalid', ['provider' => $provider]);

            // Một số nền tảng (Zalo) BẮT BUỘC phản hồi 200 cho mọi webhook, kể cả request xác
            // minh khi chưa cấu hình secret. Trả ≠200 sẽ khiến Zalo vô hiệu hóa webhook → không
            // thể hoàn tất cấu hình để lấy OA Secret Key (deadlock). Ack 200 nhưng KHÔNG ingest
            // event chưa xác thực (an toàn — dữ liệu giả mạo không bao giờ được lưu/xử lý).
            if ($connector->supports('inbound.webhook_always_ack')) {
                return ['status' => 200, 'body' => ['ok' => true, 'note' => 'unverified_ack']];
            }

            return [
                'status' => 401,
                'body' => ['error' => ['code' => 'INVALID_SIGNATURE', 'message' => 'Messaging webhook signature verification failed.']],
            ];
        }

        // 1 HTTP POST có thể chứa NHIỀU event (Facebook entry[].messaging[]). Parse
        // tất cả → store + dispatch TỪNG event (dedupe theo message id) để không mất tin.
        try {
            $events = $connector->parseWebhookEvents($request);
        } catch (\Throwable $e) {
            Log::warning('messaging.webhook.parse_failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return ['status' => 202, 'body' => ['ok' => true, 'note' => 'unparseable']];
        }

        if ($events === []) {
            return ['status' => 200, 'body' => ['ok' => true, 'note' => 'no_events']];
        }

        $storedProvider = 'messaging.'.$provider;
        $headers = $this->safeHeaders($request);
        $stored = 0;
        $duplicates = 0;

        foreach ($events as $event) {
            // Bỏ event không có gì để xử lý (không conversation & không message — vd ping rỗng).
            if (! $event->externalMessageId && ! $event->externalConversationId) {
                continue;
            }

            // For reaction events, append the action (react|unreact) so that both a react
            // and a subsequent unreact on the same mid are treated as distinct events.
            if ($event->type === MessagingWebhookEventDTO::TYPE_MESSAGE_REACTION) {
                $reactionData = is_array($event->raw['reaction'] ?? null) ? $event->raw['reaction'] : [];
                $action = is_string($reactionData['action'] ?? null) ? $reactionData['action'] : 'react';
                $dedupeKey = ($event->externalMessageId ?: 'noid').'@reaction@'.$action;
            } elseif ($event->type === MessagingWebhookEventDTO::TYPE_POSTBACK) {
                // Postback của Messenger có `mid` ⇒ dedupe theo mid (retry trùng mid; 2 lần
                // bấm khác mid). Thiếu mid (hiếm) ⇒ rơi về payload+timestamp để tránh gộp nhầm.
                $pl = is_string($event->meta['postback_payload'] ?? null) ? $event->meta['postback_payload'] : '';
                $ts = $event->occurredAt?->getTimestampMs() ?? 0;
                $dedupeKey = ($event->externalMessageId ?: ('noid@'.md5($pl).'@'.$ts)).'@postback';
            } else {
                $dedupeKey = $event->externalMessageId
                    ?: ($event->externalConversationId.'@'.$event->type);
            }

            $exists = WebhookEvent::query()
                ->where('provider', $storedProvider)
                ->where('event_type', $event->type)
                ->where('external_id', $dedupeKey)
                ->where('external_shop_id', $event->externalShopId)
                ->whereIn('status', [WebhookEvent::STATUS_PENDING, WebhookEvent::STATUS_PROCESSED])
                ->exists();

            if ($exists) {
                $duplicates++;

                continue;
            }

            // payload đầy đủ + shortcut fields (job đọc external_conversation_id/message_id từ đây).
            $row = WebhookEvent::create([
                'provider' => $storedProvider,
                'event_type' => $event->type,
                'external_id' => $dedupeKey,
                'external_shop_id' => $event->externalShopId,
                'signature_ok' => true,
                'headers' => $headers,
                'payload' => array_merge($event->raw, [
                    'external_conversation_id' => $event->externalConversationId,
                    'external_message_id' => $event->externalMessageId,
                    'buyer_external_id' => $event->buyerExternalId,
                    // Normalized content keys (Phase B) — read back by ProcessMessagingWebhook.
                    '_kind' => $event->kind?->value,
                    '_body' => $event->body,
                    '_attachments' => array_map(fn ($m) => [
                        'kind' => $m->kind->value,
                        'mime' => $m->mime,
                        'size_bytes' => $m->sizeBytes,
                        'external_url' => $m->externalUrl,
                        'storage_path' => $m->storagePath,
                        'filename' => $m->filename,
                        'width' => $m->width,
                        'height' => $m->height,
                        'duration_ms' => $m->durationMs,
                    ], $event->attachments),
                    // Thread context (Phase C) — set by connectors for non-DM threads
                    // (e.g. Facebook feed comment). ProcessMessagingWebhook reads these to
                    // upsert the correct conversation type before ingesting.
                    '_thread_type' => $event->threadType,
                    '_thread_meta' => $event->threadMeta !== [] ? $event->threadMeta : null,
                    // Direction + structured meta (vd nút bấm) — read back by ProcessMessagingWebhook.
                    '_direction' => $event->direction->value,
                    '_meta' => $event->meta !== [] ? $event->meta : null,
                ]),
                'status' => WebhookEvent::STATUS_PENDING,
                'received_at' => now(),
            ]);

            ProcessMessagingWebhook::dispatch((int) $row->getKey());
            $stored++;
        }

        // Tất cả là trùng ⇒ giữ response cũ `note=duplicate` (Meta/sàn retry an toàn).
        if ($stored === 0 && $duplicates > 0) {
            return ['status' => 200, 'body' => ['ok' => true, 'note' => 'duplicate']];
        }

        return ['status' => 200, 'body' => ['ok' => true, 'stored' => $stored, 'duplicates' => $duplicates]];
    }

    /** @return array<string,string> */
    private function safeHeaders(Request $request): array
    {
        $keep = [
            'content-type', 'user-agent', 'x-request-id',
            'x-hub-signature', 'x-hub-signature-256',
            'x-line-signature', 'x-shopee-signature', 'x-tts-signature',
        ];
        $out = [];
        foreach ($keep as $h) {
            if ($request->headers->has($h)) {
                $out[$h] = (string) $request->headers->get($h);
            }
        }

        return $out;
    }
}
