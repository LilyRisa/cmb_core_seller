<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

/**
 * TikTok Shop Partner API request signing (the "202309" generation).
 *
 * Algorithm (mirrors the official SDK, see sdk_tiktok_seller/utils/generate-sign.ts
 * and docs/04-channels/tiktok-shop.md §2):
 *   1. take all query params except `sign` and `access_token`, sort keys ascending
 *   2. concatenate them as `{key}{value}` with no separators
 *   3. prepend the API request path  →  path + concatenated-params
 *   4. if Content-Type is not multipart/form-data and the body is non-empty,
 *      append the raw JSON body string
 *   5. wrap the whole string with app_secret on both ends:  secret + str + secret
 *   6. HMAC-SHA256 with app_secret as the key; lowercase hex digest = `sign`
 *
 * Pure & deterministic so it can be tested against a fixed vector.
 */
final class TikTokSigner
{
    private const EXCLUDE = ['sign', 'access_token'];

    /**
     * @param  array<string, scalar|null>  $query  query params (already string-ified; arrays joined with ",")
     */
    public static function sign(string $appSecret, string $path, array $query, string $body = '', bool $multipart = false): string
    {
        $params = $query;
        foreach (self::EXCLUDE as $k) {
            unset($params[$k]);
        }
        ksort($params, SORT_STRING);

        $signString = $path;
        foreach ($params as $k => $v) {
            $signString .= $k.($v === null ? '' : (string) $v);
        }
        if (! $multipart && $body !== '' && $body !== '{}' && $body !== '[]') {
            $signString .= $body;
        }

        $wrapped = $appSecret.$signString.$appSecret;

        return hash_hmac('sha256', $wrapped, $appSecret);
    }
}
