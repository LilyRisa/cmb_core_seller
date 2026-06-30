<?php

namespace CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice;

use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceProviderError;
use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\Support\MisaErrorMap;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client cô lập cho MISA meInvoice Open API v3.
 * - Token cache theo (appid, taxcode, username), TTL = token_ttl_days - 1.
 * - Parse envelope 2 tầng: {Success, Data(JSON string), ErrorCode, Errors}.
 */
final class MisaClient
{
    public function __construct(
        private readonly array $config,        // base_url, token_ttl_days, http{timeout,retries,retry_sleep_ms}
        private readonly array $credentials,   // appid, taxcode, username, password
    ) {}

    private function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/');
    }

    private function timeout(): int
    {
        return (int) ($this->config['http']['timeout'] ?? 30);
    }

    private function cred(string $k): string
    {
        return (string) ($this->credentials[$k] ?? '');
    }

    public function token(): string
    {
        $ttl = max(1, (int) ($this->config['token_ttl_days'] ?? 14) - 1);
        $key = 'einvoice:misa:token:'.md5($this->cred('appid').'|'.$this->cred('taxcode').'|'.$this->cred('username'));

        return Cache::remember($key, now()->addDays($ttl), function () {
            $res = Http::baseUrl($this->baseUrl())->timeout($this->timeout())->acceptJson()
                ->post('/auth/token', [
                    'appid' => $this->cred('appid'),
                    'taxcode' => $this->cred('taxcode'),
                    'username' => $this->cred('username'),
                    'password' => $this->cred('password'),
                ]);
            $token = $this->unwrap($res);
            if (! is_string($token) || $token === '') {
                throw new EInvoiceProviderError('UnAuthorize', 'MISA không trả về token.');
            }

            return $token;
        });
    }

    private function http(): PendingRequest
    {
        $retries = (int) ($this->config['http']['retries'] ?? 0);
        $sleep = (int) ($this->config['http']['retry_sleep_ms'] ?? 0);
        $req = Http::baseUrl($this->baseUrl())
            ->withToken($this->token())
            ->withHeaders(['CompanyTaxCode' => $this->cred('taxcode')])
            ->timeout($this->timeout())->acceptJson();

        return $retries > 0 ? $req->retry($retries, $sleep) : $req;
    }

    /** Parse envelope 2 tầng; ném EInvoiceProviderError nếu Success=false hoặc ErrorCode ngoài != ''. */
    private function unwrap(Response $res): mixed
    {
        $body = $res->json();
        if (! is_array($body)) {
            throw new EInvoiceProviderError('Exception', 'MISA trả phản hồi không hợp lệ.', 'retryable');
        }
        $success = (bool) ($body['Success'] ?? false);
        $errorCode = (string) ($body['ErrorCode'] ?? '');
        if (! $success || $errorCode !== '') {
            $code = $errorCode !== '' ? $errorCode : 'Exception';
            throw new EInvoiceProviderError($code, MisaErrorMap::message($code), MisaErrorMap::classify($code));
        }
        $data = $body['Data'] ?? null;
        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $data;
        }

        return $data;
    }

    /** GET /company?taxcode= — trả mảng thông tin công ty. */
    public function companyInfoRaw(): array
    {
        $data = $this->unwrap($this->http()->get('/company', ['taxcode' => $this->cred('taxcode')]));

        return is_array($data) ? $data : [];
    }

    /** GET /itg/InvoicePublishing/templates?invyear= — list mẫu HĐ. */
    public function templatesRaw(int $year): array
    {
        $data = $this->unwrap($this->http()->get('/itg/InvoicePublishing/templates', ['invyear' => $year]));

        return is_array($data) ? array_values($data) : [];
    }
}
