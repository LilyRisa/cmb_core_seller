<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Services\WebhookIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One webhook endpoint per provider: POST /webhook/{provider}. No CSRF, no auth
 * — the connector's verifier checks the signature. Verify -> store -> 200 fast
 * -> process async. See docs/05-api/webhooks-and-oauth.md §1.
 */
class WebhookController extends Controller
{
    public function handle(Request $request, string $provider, WebhookIngestService $ingest): JsonResponse
    {
        $result = $ingest->ingest($provider, $request);

        return response()->json($result['body'], $result['status']);
    }
}
