<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Services\WebhookIngestService;
use CMBcoreSeller\Modules\Messaging\Services\MessagingWebhookIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * TikTok dùng MỘT push URL/app (Partner console) cho cả đơn hàng lẫn tin nhắn CS.
 * Controller demux theo `type`: tin nhắn (type ∈ chat_push_types = 13/14/33) →
 * pipeline messaging (tiktok_chat); còn lại → pipeline đơn hàng (Channels). Mỗi
 * ingest service tự verify chữ ký push.
 *
 * TikTok là ngoại lệ của "1 webhook controller chung" (ADR-0017) — giống Shopee —
 * vì 1 URL gánh 2 domain. Lazada vẫn dùng WebhookController chung (chat Lazada qua
 * polling, không webhook). Xem docs/05-api/webhooks-and-oauth.md §1.
 */
class TikTokWebhookController extends Controller
{
    public function handle(
        Request $request,
        WebhookIngestService $orders,
        MessagingWebhookIngestService $messaging,
        MessagingRegistry $registry,
    ): JsonResponse {
        $type = (int) ($request->json('type') ?? -1);
        $chatTypes = array_map('intval', (array) config('integrations.tiktok.chat_push_types', [13, 14, 33]));

        if (in_array($type, $chatTypes, true)) {
            // Tin chat nhưng connector chưa bật ⇒ ack + log, KHÔNG nhét vào pipeline đơn hàng
            // (nếu rơi vào orders sẽ bị map sai — mất tin hoặc tệ hơn là revoke shop).
            if (! $registry->has('tiktok_chat')) {
                Log::warning('tiktok.webhook.chat_connector_disabled', ['type' => $type]);

                return response()->json(['ok' => true, 'note' => 'chat_connector_disabled'], 200);
            }
            $result = $messaging->ingest('tiktok_chat', $request);
        } else {
            $result = $orders->ingest('tiktok', $request);
        }

        return response()->json($result['body'], $result['status']);
    }
}
