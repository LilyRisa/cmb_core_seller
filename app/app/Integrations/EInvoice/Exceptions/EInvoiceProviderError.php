<?php

namespace CMBcoreSeller\Integrations\EInvoice\Exceptions;

use RuntimeException;

/** Lỗi do nhà cung cấp HĐĐT trả về. Mang mã lỗi gốc + phân loại retry. */
class EInvoiceProviderError extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly string $errorClass = 'non_retryable',
    ) {
        parent::__construct($message);
    }
}
