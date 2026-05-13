<?php

namespace CMBcoreSeller\Modules\Channels\Services;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook receive path: verify signature -> store the raw event -> 200 fast ->
 * process async. Never calls the marketplace API here. Dedupes by
 * (provider, event_type, external_id|notification_id). See docs/03-domain/order-sync-pipeline.md §2.
 */
class WebhookIngestService
{
    public function __construct(private ChannelRegistry $registry) {}

    /** @return array{status:int, body:array<string,mixed>} */
    public function ingest(string $provider, Request $request): array
    {
        if (! $this->registry->has($provider)) {
            return ['status' => 404, 'body' => ['error' => ['code' => 'UNKNOWN_PROVIDER', 'message' => "Unknown provider [{$provider}]."]]];
        }
        $connector = $this->registry->for($provider);

        if (! $connector->verifyWebhookSignature($request)) {
            Log::warning('webhook.signature_invalid', ['provider' => $provider]);

            return ['status' => 401, 'body' => ['error' => ['code' => 'INVALID_SIGNATURE', 'message' => 'Webhook signature verification failed.']]];
        }

        try {
            $event = $connector->parseWebhook($request);
        } catch (\Throwable $e) {
            Log::warning('webhook.parse_failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return ['status' => 202, 'body' => ['ok' => true, 'note' => 'unparseable']]; // ack — TikTok won't retry forever; nothing to do
        }

        $dedupeKey = $event->externalOrderId ?: ($event->externalIds['notification_id'] ?? ($event->externalIds['return_id'] ?? null));

        if ($dedupeKey !== null && WebhookEvent::query()
            ->where('provider', $provider)->where('event_type', $event->type)->where('external_id', $dedupeKey)
            ->where('external_shop_id', $event->externalShopId)
            ->whereIn('status', [WebhookEvent::STATUS_PENDING, WebhookEvent::STATUS_PROCESSED])->exists()) {
            return ['status' => 200, 'body' => ['ok' => true, 'note' => 'duplicate']];
        }

        $row = WebhookEvent::create([
            'provider' => $provider,
            'event_type' => $event->type,
            'external_id' => $dedupeKey,
            'external_shop_id' => $event->externalShopId,
            'order_raw_status' => $event->orderRawStatus,
            'raw_type' => $event->raw['_raw_type'] ?? ($event->raw['type'] ?? null),
            'signature_ok' => true,
            'headers' => $this->safeHeaders($request),
            'payload' => $event->raw,
            'status' => WebhookEvent::STATUS_PENDING,
            'received_at' => now(),
        ]);

        ProcessWebhookEvent::dispatch((int) $row->getKey());

        return ['status' => 200, 'body' => ['ok' => true]];
    }

    /** @return array<string,string> a non-sensitive subset of request headers */
    private function safeHeaders(Request $request): array
    {
        $keep = ['content-type', 'user-agent', 'x-tts-event-name', 'x-request-id', 'x-tts-signature'];
        $out = [];
        foreach ($keep as $h) {
            if ($request->headers->has($h)) {
                $out[$h] = (string) $request->headers->get($h);
            }
        }

        return $out;
    }
}
