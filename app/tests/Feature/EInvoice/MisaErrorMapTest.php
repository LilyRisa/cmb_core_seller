<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\Support\MisaErrorMap;
use Tests\TestCase;

class MisaErrorMapTest extends TestCase
{
    public function test_transient_codes_are_retryable(): void
    {
        $this->assertSame('retryable', MisaErrorMap::classify('TokenExpiredCode'));
        $this->assertSame('retryable', MisaErrorMap::classify('InvoiceNumberNotContinuous'));
        $this->assertSame('retryable', MisaErrorMap::classify('Exception'));
    }

    public function test_business_codes_are_non_retryable(): void
    {
        $this->assertSame('non_retryable', MisaErrorMap::classify('InvalidTaxCode'));
        $this->assertSame('non_retryable', MisaErrorMap::classify('LicenseInfo_OutOfInvoice'));
        $this->assertSame('non_retryable', MisaErrorMap::classify('SomethingUnknown'));
    }

    public function test_message_is_vietnamese_with_fallback(): void
    {
        $this->assertStringContainsString('Token', MisaErrorMap::message('TokenExpiredCode'));
        $this->assertSame('XYZ_UNKNOWN', MisaErrorMap::message('XYZ_UNKNOWN'));
    }

    /** InvoiceDuplicated và DuplicateInvoiceRefID KHÔNG phải lỗi tạm thời — không retry. */
    public function test_duplicate_codes_are_non_retryable(): void
    {
        $this->assertSame('non_retryable', MisaErrorMap::classify('InvoiceDuplicated'));
        $this->assertSame('non_retryable', MisaErrorMap::classify('DuplicateInvoiceRefID'));
    }
}
