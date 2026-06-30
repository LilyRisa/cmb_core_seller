<?php

namespace CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice;

use CMBcoreSeller\Integrations\EInvoice\Contracts\EInvoiceConnector;
use CMBcoreSeller\Integrations\EInvoice\DTO\CompanyInfoDTO;
use CMBcoreSeller\Integrations\EInvoice\DTO\TemplateDTO;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceNotConfigured;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceProviderError;

/** Connector MISA meInvoice. Phần A: verify + company info + templates. */
final class MisaMeInvoiceConnector implements EInvoiceConnector
{
    public function __construct(private array $config) {}

    public function code(): string
    {
        return 'misa';
    }

    public function displayName(): string
    {
        return 'MISA meInvoice';
    }

    public function capabilities(): array
    {
        return [
            'verify' => true, 'company_info' => true, 'templates' => true,
            // Phần B bật: 'issue_hsm', 'issue_mtt', 'preview', 'status', 'download', 'cancel', 'adjust', 'replace'.
            'issue_hsm' => false, 'issue_mtt' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function assertConfigured(array $account): void
    {
        $cred = (array) ($account['credentials'] ?? []);
        foreach (['appid', 'taxcode', 'username', 'password'] as $k) {
            if (empty($cred[$k])) {
                throw EInvoiceNotConfigured::for('misa', $k);
            }
        }
    }

    public function verifyCredentials(array $account): array
    {
        try {
            $this->assertConfigured($account);
        } catch (EInvoiceNotConfigured $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'error_code' => 'invalid_credentials', 'expires_at' => null];
        }

        try {
            $info = $this->client($account)->companyInfoRaw();
            $name = (string) ($info['CompanyName'] ?? $info['CompanyTaxCode'] ?? '');

            return ['ok' => true, 'message' => 'Kết nối MISA meInvoice OK'.($name !== '' ? ' — '.$name : '').'.', 'expires_at' => null];
        } catch (EInvoiceProviderError $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->errorClass === 'retryable' ? 'network' : 'invalid_credentials',
                'expires_at' => null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Lỗi kết nối MISA: '.$e->getMessage(), 'error_code' => 'network', 'expires_at' => null];
        }
    }

    public function getCompanyInfo(array $account): CompanyInfoDTO
    {
        $this->assertConfigured($account);

        return CompanyInfoDTO::fromMisa($this->client($account)->companyInfoRaw());
    }

    public function templates(array $account, int $year): array
    {
        $this->assertConfigured($account);

        return array_map(
            static fn (array $raw) => TemplateDTO::fromMisa($raw),
            $this->client($account)->templatesRaw($year),
        );
    }

    private function client(array $account): MisaClient
    {
        return new MisaClient($this->config, (array) ($account['credentials'] ?? []));
    }
}
