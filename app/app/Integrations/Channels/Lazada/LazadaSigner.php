<?php

namespace CMBcoreSeller\Integrations\Channels\Lazada;

/**
 * Lazada Open Platform request signing (sign_method=sha256).
 *
 * Algorithm (mirrors the official SDK / docs — see docs/04-channels/lazada.md §2):
 *   1. take all request params (system + business), EXCLUDE `sign`, sort keys ascending
 *   2. concatenate as `{key}{value}` with no separators  →  concatenated
 *   3. prepend the API path  →  apiPath + concatenated
 *   4. HMAC-SHA256 over that string with `app_secret` as the key
 *   5. UPPERCASE hex digest = `sign`
 *
 * (No request body is included — for POST endpoints Lazada passes business params in the
 * query string / form too, so they're already in the param map.) Pure & deterministic.
 */
final class LazadaSigner
{
    /**
     * @param  array<string, scalar|null>  $params  all request params (system + business), string-ified
     */
    public static function sign(string $appSecret, string $apiPath, array $params): string
    {
        unset($params['sign']);
        ksort($params, SORT_STRING);

        $str = $apiPath;
        foreach ($params as $k => $v) {
            $str .= $k.($v === null ? '' : (string) $v);
        }

        return strtoupper(hash_hmac('sha256', $str, $appSecret));
    }
}
