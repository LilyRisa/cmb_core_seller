<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Messaging\Services\MessagingWebhookIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Một endpoint webhook chung cho mọi nền tảng messaging:
 *   POST /webhook/messaging/{provider}
 *
 * No CSRF, no auth. Verifier của connector check chữ ký trước. Verify ⇒ store ⇒
 * 200 fast ⇒ process async. Mirror `Channels\Http\Controllers\WebhookController`.
 *
 * Facebook setup yêu cầu thêm GET cho `hub.verify_token` challenge — handle ở
 * `verify()` method tách (S2).
 */
class MessagingWebhookController extends Controller
{
    public function handle(Request $request, string $provider, MessagingWebhookIngestService $ingest): JsonResponse
    {
        // Alias: 'facebook' (route URL ngắn cho Meta setup) → connector code 'facebook_page'.
        if ($provider === 'facebook') {
            $provider = 'facebook_page';
        }
        $result = $ingest->ingest($provider, $request);

        return response()->json($result['body'], $result['status']);
    }

    /**
     * Facebook Messenger hub.challenge verify (GET với `hub.mode=subscribe`).
     * S2 sẽ implement đầy đủ; S1 chỉ stub trả 200 nếu verify_token match config.
     */
    public function verify(Request $request, string $provider): JsonResponse|\Illuminate\Http\Response
    {
        if ($provider !== 'facebook' && $provider !== 'facebook_page') {
            return response()->json(['error' => ['code' => 'UNSUPPORTED', 'message' => 'GET verify chỉ dùng cho Facebook.']], 400);
        }

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = (string) (config('integrations.messaging.facebook_page.verify_token')
            ?? env('MESSAGING_FACEBOOK_VERIFY_TOKEN', ''));

        if ($mode === 'subscribe' && $expectedToken !== '' && hash_equals($expectedToken, (string) $token)) {
            return response((string) $challenge, 200);
        }

        return response()->json(['error' => ['code' => 'VERIFY_FAILED', 'message' => 'Facebook verify token mismatch.']], 403);
    }
}
