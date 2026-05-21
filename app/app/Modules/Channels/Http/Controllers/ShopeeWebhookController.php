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
 * Shopee dùng MỘT push URL/app cho cả đơn hàng lẫn chat (push code 10 = Webchat).
 * Controller demux theo `code`: chat → pipeline messaging (shopee_chat); còn lại
 * → pipeline đơn hàng (Channels). Mỗi ingest service tự verify chữ ký push.
 *
 * Shopee là ngoại lệ của "1 webhook controller chung" (ADR-0017) vì 1 URL gánh 2
 * domain — tiktok/lazada vẫn dùng WebhookController chung.
 */
class ShopeeWebhookController extends Controller
{
    public function handle(
        Request $request,
        WebhookIngestService $orders,
        MessagingWebhookIngestService $messaging,
        MessagingRegistry $registry,
    ): JsonResponse {
        $code = (int) ($request->json('code') ?? -1);
        $chatCodes = array_map('intval', (array) config('integrations.shopee.chat_push_codes', [10]));

        if (in_array($code, $chatCodes, true)) {
            // Chat push (code 10) nhưng connector chưa bật ⇒ ack + log, KHÔNG nhét vào pipeline đơn hàng.
            if (! $registry->has('shopee_chat')) {
                Log::warning('shopee.webhook.chat_connector_disabled', ['code' => $code]);

                return response()->json(['ok' => true, 'note' => 'chat_connector_disabled'], 200);
            }
            $result = $messaging->ingest('shopee_chat', $request);
        } else {
            $result = $orders->ingest('shopee', $request);
        }

        return response()->json($result['body'], $result['status']);
    }
}
