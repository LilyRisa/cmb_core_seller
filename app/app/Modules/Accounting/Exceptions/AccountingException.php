<?php

namespace CMBcoreSeller\Modules\Accounting\Exceptions;

use RuntimeException;

/**
 * Base exception cho module Accounting — gắn `code` để controller map sang error envelope chuẩn.
 *
 * Phase 7.1 — SPEC 0019 §6.7. Tất cả lỗi nghiệp vụ kế toán quy chuẩn các code này, không leak
 * raw RuntimeException message ra client.
 */
class AccountingException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $httpStatus = 422,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function periodClosed(string $periodCode): self
    {
        return new self(
            "Kỳ {$periodCode} đã đóng — không post được bút toán vào kỳ này.",
            'ACCOUNTING_PERIOD_CLOSED', 422, ['period' => $periodCode]
        );
    }

    public static function periodLocked(string $periodCode): self
    {
        return new self(
            "Kỳ {$periodCode} đã khoá — không thể đảo / sửa.",
            'ACCOUNTING_PERIOD_LOCKED', 422, ['period' => $periodCode]
        );
    }

    public static function unbalanced(int $debit, int $credit): self
    {
        return new self(
            "Bút toán không cân: Nợ {$debit} ≠ Có {$credit}.",
            'ACCOUNTING_UNBALANCED', 422, ['debit' => $debit, 'credit' => $credit]
        );
    }

    public static function accountNotPostable(string $code): self
    {
        return new self(
            "Tài khoản {$code} là TK tổng, không thể hạch toán trực tiếp.",
            'ACCOUNTING_ACCOUNT_NOT_POSTABLE', 422, ['account_code' => $code]
        );
    }

    public static function accountNotFound(string $code): self
    {
        return new self(
            "Không tìm thấy tài khoản {$code} trong hệ thống tài khoản.",
            'ACCOUNTING_ACCOUNT_NOT_FOUND', 422, ['account_code' => $code]
        );
    }

    public static function accountInUse(string $code): self
    {
        return new self(
            "Tài khoản {$code} đã có phát sinh, không xoá được.",
            'ACCOUNTING_ACCOUNT_IN_USE', 409, ['account_code' => $code]
        );
    }

    public static function invalidLines(string $reason): self
    {
        return new self(
            $reason, 'ACCOUNTING_INVALID_LINES', 422,
        );
    }

    public static function reopenBlocked(string $reason): self
    {
        return new self(
            $reason, 'ACCOUNTING_REOPEN_BLOCKED', 422,
        );
    }
}
