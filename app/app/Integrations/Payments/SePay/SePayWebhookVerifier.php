<?php

namespace CMBcoreSeller\Integrations\Payments\SePay;

use Illuminate\Http\Request;

/**
 * Verify chữ ký webhook SePay.
 *
 * SePay docs: webhook gửi `Authorization: Apikey <api-key>` header. Verify khớp
 * `config('integrations.payments.sepay.api_key')`. (Có thể nâng cấp HMAC khi SePay
 * mở rộng — hiện api-key static là cách họ recommend cho merchant nhỏ/vừa.)
 *
 * Sai khoá / thiếu header ⇒ trả false, controller trả 401.
 */
class SePayWebhookVerifier
{
    public function __construct(protected ?string $apiKey) {}

    public function verify(Request $request): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        $header = (string) $request->header('Authorization', '');
        // SePay gửi "Apikey xxx" (theo docs); một số tài liệu cũ dùng "Bearer xxx".
        // Hỗ trợ cả hai để tương thích.
        if (preg_match('/^(Apikey|Bearer)\s+(.+)$/i', $header, $m) !== 1) {
            return false;
        }
        $provided = trim($m[2]);

        // Constant-time compare để không leak qua timing.
        return hash_equals($this->apiKey, $provided);
    }
}
