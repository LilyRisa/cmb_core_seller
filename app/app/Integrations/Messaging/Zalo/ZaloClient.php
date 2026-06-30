<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client cho Zalo Open API. Host openapi.zalo.me, auth bằng header `access_token` (KHÔNG Bearer).
 * Envelope {error,message,data}: error !== 0 là lỗi dù HTTP 200 → ném ZaloApiException.
 */
class ZaloClient
{
    private const API_BASE = 'https://openapi.zalo.me/';

    private const OAUTH_TOKEN_URL = 'https://oauth.zaloapp.com/v4/oa/access_token';

    private function base(string $accessToken): PendingRequest
    {
        return Http::baseUrl(self::API_BASE)
            ->withHeaders(['access_token' => $accessToken])
            ->timeout(30)
            ->retry(2, 500, throw: false);
    }

    /** @param array<string,mixed> $query @return array<string,mixed> */
    public function get(string $accessToken, string $path, array $query = []): array
    {
        return $this->unwrap($this->base($accessToken)->get($path, $query)->json() ?? []);
    }

    /** @param array<string,mixed> $json @return array<string,mixed> */
    public function post(string $accessToken, string $path, array $json): array
    {
        return $this->unwrap($this->base($accessToken)->asJson()->post($path, $json)->json() ?? []);
    }

    /** @return array<string,mixed> */
    public function uploadMultipart(string $accessToken, string $path, string $fieldName, string $contents, string $filename, string $mime): array
    {
        $res = $this->base($accessToken)
            ->attach($fieldName, $contents, $filename, ['Content-Type' => $mime])
            ->post($path)
            ->json() ?? [];

        return $this->unwrap($res);
    }

    /**
     * Đổi/refresh token: POST form-urlencoded tới oauth.zaloapp.com, secret ở header `secret_key`.
     *
     * @param  array<string,string>  $form  @return array<string,mixed>
     */
    public function oauthToken(array $form, string $appSecret): array
    {
        $res = Http::asForm()
            ->withHeaders(['secret_key' => $appSecret])
            ->timeout(30)
            ->retry(2, 500, throw: false)
            ->post(self::OAUTH_TOKEN_URL, $form);

        $json = $res->json() ?? [];
        if (isset($json['error']) && (int) $json['error'] !== 0) {
            throw ZaloApiException::from((int) $json['error'], (string) ($json['message'] ?? 'oauth error'));
        }
        if (empty($json['access_token'])) {
            throw ZaloApiException::from(-1, 'Zalo oauth: missing access_token: '.json_encode($json));
        }

        return $json;
    }

    /** @param array<string,mixed> $json @return array<string,mixed> */
    private function unwrap(array $json): array
    {
        $error = (int) ($json['error'] ?? 0);
        if ($error !== 0) {
            throw ZaloApiException::from($error, (string) ($json['message'] ?? 'unknown'));
        }

        return (array) ($json['data'] ?? []);
    }
}
