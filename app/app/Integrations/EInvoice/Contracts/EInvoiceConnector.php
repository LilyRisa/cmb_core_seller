<?php

namespace CMBcoreSeller\Integrations\EInvoice\Contracts;

use CMBcoreSeller\Integrations\EInvoice\DTO\CompanyInfoDTO;
use CMBcoreSeller\Integrations\EInvoice\DTO\TemplateDTO;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceNotConfigured;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\UnsupportedOperation;

/**
 * Hợp đồng mọi nhà cung cấp hóa đơn điện tử phải implement (MISA, VNPT, Viettel...).
 *
 * QUY TẮC VÀNG (như ChannelConnector/CarrierConnector/PaymentGatewayConnector):
 *   - Core không biết tên cụ thể của nhà cung cấp HĐĐT.
 *   - Thêm provider = 1 class + 1 dòng register trong EInvoiceRegistry + 1 block config.
 *   - Không `if ($provider === 'misa')` trong module EInvoice.
 *
 * Credentials per-tenant truyền qua tham số đầu `array $account` (giống CarrierConnector),
 * KHÔNG bake vào config. Thao tác không hỗ trợ ⇒ ném {@see UnsupportedOperation};
 * thiếu cấu hình ⇒ ném {@see EInvoiceNotConfigured}.
 *
 * (Phần B mở rộng interface này: issue/preview/cancel/adjust/replace/status/download.)
 */
interface EInvoiceConnector
{
    /** Stable code: 'misa'. */
    public function code(): string;

    public function displayName(): string;

    /** @return array<string, bool> vd ['verify'=>true,'company_info'=>true,'templates'=>true,'issue_hsm'=>false,'issue_mtt'=>false]. */
    public function capabilities(): array;

    public function supports(string $capability): bool;

    /**
     * @param  array<string, mixed>  $account
     *
     * @throws EInvoiceNotConfigured
     */
    public function assertConfigured(array $account): void;

    /**
     * @param  array<string, mixed>  $account
     * @return array{ok:bool, message:string, expires_at?:?string, error_code?:string}
     */
    public function verifyCredentials(array $account): array;

    /** @param array<string, mixed> $account */
    public function getCompanyInfo(array $account): CompanyInfoDTO;

    /**
     * @param  array<string, mixed>  $account
     * @return list<TemplateDTO>
     */
    public function templates(array $account, int $year): array;
}
