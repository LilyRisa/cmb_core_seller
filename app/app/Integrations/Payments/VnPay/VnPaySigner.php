<?php

namespace CMBcoreSeller\Integrations\Payments\VnPay;

/**
 * Ký HMAC-SHA512 theo chuẩn VNPay 2.1.0.
 *
 * Quy tắc:
 *   1. Lấy tất cả params bắt đầu bằng `vnp_` (loại `vnp_SecureHash`, `vnp_SecureHashType`).
 *   2. Sort theo key ASC.
 *   3. URL-encode value (RFC 3986 — PHP `urlencode` thay '+' bằng '%20'? KHÔNG — VNPay dùng "+").
 *      Thực ra VNPay đề xuất `http_build_query` → dùng PHP_QUERY_RFC1738 (chuẩn 'a+b').
 *   4. Build `key=value&key=value...` (đã encode).
 *   5. HMAC-SHA512 với `hash_secret`.
 *
 * Áp dụng cho cả pay URL outbound (server → VNPay) và IPN verify (VNPay → server).
 */
class VnPaySigner
{
    public function __construct(protected string $hashSecret) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function sign(array $params): string
    {
        $data = $this->canonicalString($params);

        return strtoupper(hash_hmac('sha512', $data, $this->hashSecret));
    }

    /**
     * Verify chữ ký từ VNPay (case-insensitive — VNPay đôi khi trả lowercase).
     *
     * @param  array<string, mixed>  $params
     */
    public function verify(array $params, string $providedSignature): bool
    {
        if ($providedSignature === '') {
            return false;
        }
        $clean = $params;
        unset($clean['vnp_SecureHash'], $clean['vnp_SecureHashType']);
        $expected = $this->sign($clean);

        return hash_equals(strtoupper($expected), strtoupper($providedSignature));
    }

    /**
     * Build canonical query string for signing — same encoding VNPay expects.
     *
     * @param  array<string, mixed>  $params
     */
    public function canonicalString(array $params): string
    {
        // Loại các field không được include khi ký.
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        // Loại empty (theo doc — VNPay không truyền field rỗng).
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        ksort($params);

        return http_build_query($params, '', '&', PHP_QUERY_RFC1738);
    }
}
