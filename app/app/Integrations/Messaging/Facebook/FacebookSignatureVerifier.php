<?php

namespace CMBcoreSeller\Integrations\Messaging\Facebook;

use Symfony\Component\HttpFoundation\Request;

/**
 * Verify chữ ký webhook Messenger: header `X-Hub-Signature-256` =
 * `sha256=` + HMAC-SHA256(raw body, app_secret). So sánh hằng-thời-gian.
 *
 * Sai / thiếu app_secret ⇒ false (controller trả 401, KHÔNG lưu payload — §8.6).
 * Tách class để unit-test với key + payload cố định mà không dựng connector.
 */
class FacebookSignatureVerifier
{
    public function verify(Request $request, ?string $appSecret): bool
    {
        if (! $appSecret) {
            return false;
        }

        $header = (string) $request->headers->get('X-Hub-Signature-256', '');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $header);
    }
}
