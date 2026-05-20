<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

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
            return [
                'status' => 401,
                'body' => ['error' => ['code' => 'INVALID_SIGNATURE', 'message' => 'Messaging webhook signature verification failed.']],
            ];
        }

        try {
            $event = $connector->parseWebhook($request);
        } catch (\Throwable $e) {
            Log::warning('messaging.webhook.parse_failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            return ['status' => 202, 'body' => ['ok' => true, 'note' => 'unparseable']];
        }

        $dedupeKey = $event->externalMessageId
            ?: ($event->externalConversationId.'@'.$event->type);

        $storedProvider = 'messaging.'.$provider;

        // Dedupe
        $exists = WebhookEvent::query()
            ->where('provider', $storedProvider)
            ->where('event_type', $event->type)
            ->where('external_id', $dedupeKey)
            ->where('external_shop_id', $event->externalShopId)
            ->whereIn('status', [WebhookEvent::STATUS_PENDING, WebhookEvent::STATUS_PROCESSED])
            ->exists();

        if ($exists) {
            return ['status' => 200, 'body' => ['ok' => true, 'note' => 'duplicate']];
        }

        // Lưu event — payload đầy đủ + 4 field shortcut (provider, type, external_id, external_shop_id)
        $row = WebhookEvent::create([
            'provider' => $storedProvider,
            'event_type' => $event->type,
            'external_id' => $dedupeKey,
            'external_shop_id' => $event->externalShopId,
            'signature_ok' => true,
            'headers' => $this->safeHeaders($request),
            'payload' => array_merge($event->raw, [
                'external_conversation_id' => $event->externalConversationId,
                'external_message_id' => $event->externalMessageId,
                'buyer_external_id' => $event->buyerExternalId,
            ]),
            'status' => WebhookEvent::STATUS_PENDING,
            'received_at' => now(),
        ]);

        ProcessMessagingWebhook::dispatch((int) $row->getKey());

        return ['status' => 200, 'body' => ['ok' => true]];
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
