<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\DTO\CompanyInfoDTO;
use CMBcoreSeller\Integrations\EInvoice\DTO\TemplateDTO;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceProviderError;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\UnsupportedOperation;
use Tests\TestCase;

class EInvoiceDtoTest extends TestCase
{
    public function test_company_info_maps_from_misa_raw(): void
    {
        $dto = CompanyInfoDTO::fromMisa([
            'CompanyName' => 'Công ty ABC', 'CompanyTaxCode' => '0105922241',
            'CompanyAddress' => 'Hà Nội', 'IsInvoiceWithCode' => true, 'CompanyEmail' => 'a@b.vn',
        ]);
        $this->assertSame('Công ty ABC', $dto->companyName);
        $this->assertTrue($dto->isInvoiceWithCode);
        $this->assertSame('0105922241', $dto->toArray()['tax_code']);
    }

    public function test_template_maps_from_misa_raw(): void
    {
        $dto = TemplateDTO::fromMisa([
            'IPTemplateID' => 'guid-1', 'TemplateName' => '01GTKT', 'InvSeries' => '1C25TAA',
            'InvoiceType' => 1, 'IsPublished' => true, 'Inactive' => false,
        ]);
        $this->assertSame('guid-1', $dto->templateId);
        $this->assertSame('1C25TAA', $dto->toArray()['inv_series']);
        $this->assertSame(1, $dto->invoiceType);
    }

    public function test_unsupported_operation_message_is_vietnamese(): void
    {
        $e = UnsupportedOperation::for('misa', 'adjust');
        $this->assertStringContainsString('misa', $e->getMessage());
        $this->assertStringContainsString('adjust', $e->getMessage());
    }

    public function test_provider_error_carries_code_and_class(): void
    {
        $e = new EInvoiceProviderError('TokenExpiredCode', 'Token hết hạn', 'retryable');
        $this->assertSame('TokenExpiredCode', $e->errorCode);
        $this->assertSame('retryable', $e->errorClass);
    }
}
