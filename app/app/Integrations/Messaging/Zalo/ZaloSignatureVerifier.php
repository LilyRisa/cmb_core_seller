<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

use Symfony\Component\HttpFoundation\Request;

/**
 * Xác minh chữ ký webhook Zalo OA.
 * Header `X-ZEvent-Signature: mac=<hex>`, mac = sha256(app_id + raw_body + timestamp + oa_secret).
 * Lưu ý: SHA256 thường (KHÔNG hmac); timestamp đọc từ body. // NEEDS-VERIFY (Zalo Open Platform)
 */
class ZaloSignatureVerifier
{
    public function verify(Request $request, string $appId, string $oaSecret): bool
    {
        if ($oaSecret === '' || $appId === '') {
            return false;
        }

        $header = (string) $request->headers->get('X-ZEvent-Signature', '');
        if (! str_starts_with($header, 'mac=')) {
            return false;
        }
        $provided = substr($header, 4);

        $body = $request->getContent();
        $decoded = json_decode($body, true);
        $timestamp = is_array($decoded) ? (string) ($decoded['timestamp'] ?? '') : '';
        if ($timestamp === '') {
            return false;
        }

        $expected = hash('sha256', $appId.$body.$timestamp.$oaSecret);

        return hash_equals($expected, $provided);
    }
}
