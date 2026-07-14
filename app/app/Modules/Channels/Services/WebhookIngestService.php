<?php

namespace CMBcoreSeller\Modules\Channels\Services;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
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

        // Dedupe theo `(provider, event_type, external_id, shop, order_raw_status)` — order-sync-pipeline §2
        // ("[, timestamp]"). external_id của order event = order_id, nên nếu CHỈ khử theo order_id thì mọi
        // transition sau lần đầu (AWAITING_SHIPMENT → AWAITING_COLLECTION → IN_TRANSIT → CANCEL) bị coi là
        // trùng và bỏ ⇒ webhook mất tác dụng real-time. Thêm raw_status để giữ các status khác nhau, vẫn
        // khử retry cùng status. (status null — vd push không kèm trạng thái — khử theo null như cũ.)
        if ($dedupeKey !== null && WebhookEvent::query()
            ->where('provider', $provider)->where('event_type', $event->type)->where('external_id', $dedupeKey)
            ->where('external_shop_id', $event->externalShopId)
            ->when(
                $event->orderRawStatus !== null,
                fn ($q) => $q->where('order_raw_status', $event->orderRawStatus),
                fn ($q) => $q->whereNull('order_raw_status'),
            )
            ->whereIn('status', [WebhookEvent::STATUS_PENDING, WebhookEvent::STATUS_PROCESSED])->exists()) {
            return ['status' => 200, 'body' => ['ok' => true, 'note' => 'duplicate']];
        }

        try {
            $row = WebhookEvent::create([
                'provider' => $provider,
                'event_type' => $event->type,
                'external_id' => $dedupeKey,
                'external_shop_id' => $event->externalShopId,
                'order_raw_status' => $event->orderRawStatus,
                // Design 2026-07-14 §2 — khoá dedupe phẳng (không NULL) để giai đoạn 2 đặt unique constraint
                // thật được (NULL không so bằng NULL trong unique index chuẩn SQL).
                'dedupe_status_key' => $event->orderRawStatus ?? '',
                'raw_type' => $event->raw['_raw_type'] ?? ($event->raw['type'] ?? null),
                'signature_ok' => true,
                'headers' => $this->safeHeaders($request),
                'payload' => $event->raw,
                'status' => WebhookEvent::STATUS_PENDING,
                'received_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Race hiếm: 2 webhook trùng đến giữa lúc exists() fast-path pass và create() này — unique
            // constraint (giai đoạn 2, design 2026-07-14 §2) chặn ở tầng DB, coi như duplicate bình thường.
            // Dùng type của exception (Laravel tự phân loại đúng theo từng driver) thay vì tự parse
            // SQLSTATE — SQLite báo chung 23000 cho MỌI vi phạm integrity constraint (NOT NULL, UNIQUE,
            // CHECK, FK), không riêng unique, nên hand-roll theo mã lỗi sẽ nuốt nhầm lỗi thật khác.
            Log::info('webhook.dedupe_race_caught', ['provider' => $provider, 'external_id' => $dedupeKey]);

            return ['status' => 200, 'body' => ['ok' => true, 'note' => 'duplicate']];
        }

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
