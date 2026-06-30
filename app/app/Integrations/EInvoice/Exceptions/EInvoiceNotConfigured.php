<?php

namespace CMBcoreSeller\Integrations\EInvoice\Exceptions;

use RuntimeException;

/** Nhà cung cấp HĐĐT thiếu credentials. Caller bắt → 422. */
class EInvoiceNotConfigured extends RuntimeException
{
    public static function for(string $provider, string $missing): self
    {
        return new self("Nhà cung cấp HĐĐT `{$provider}` chưa cấu hình — thiếu `{$missing}`.");
    }
}
