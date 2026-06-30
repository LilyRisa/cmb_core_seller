<?php

namespace CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\Support;

/**
 * Mã lỗi MISA meInvoice → phân loại retry + thông điệp tiếng Việt.
 * Nguồn: https://doc.meinvoice.vn/api/ (ErrorCode).
 */
final class MisaErrorMap
{
    /**
     * Lỗi tạm thời — job có thể retry.
     * Duplicate/RefID-trùng KHÔNG retry — Phần B xử idempotency bằng tra cứu hóa đơn đã phát hành theo RefID, không retry.
     */
    private const RETRYABLE = [
        'TokenExpiredCode', 'InvalidTokenCode', 'InvoiceNumberNotContinuous', 'Exception',
    ];

    /** @var array<string, string> */
    private const MESSAGES = [
        'TokenExpiredCode' => 'Token đã hết hạn, hệ thống sẽ tự lấy lại.',
        'InvalidTokenCode' => 'Token không hợp lệ, cần đăng nhập lại.',
        'UnAuthorize' => 'Sai tài khoản hoặc mật khẩu MISA.',
        'InvalidAppID' => 'AppID không hợp lệ — liên hệ MISA để cấp.',
        'InactiveAppID' => 'AppID chưa được kích hoạt.',
        'InvoiceNumberNotContinuous' => 'Số hóa đơn không liên tục, sẽ thử lại.',
        'InvoiceDuplicated' => 'Hóa đơn đã được phát hành trước đó.',
        'DuplicateInvoiceRefID' => 'Trùng mã tham chiếu hóa đơn (RefID).',
        'InvalidTaxCode' => 'Mã số thuế không hợp lệ.',
        'InvalidInvoiceDate' => 'Ngày hóa đơn không hợp lệ (nhỏ hơn hóa đơn cuối).',
        'LicenseInfo_OutOfInvoice' => 'Đã hết số lượng hóa đơn — cần mua thêm.',
        'LicenseInfo_Expired' => 'Gói hóa đơn đã hết hạn/chưa thanh toán.',
        'LicenseInfo_NotBuy' => 'Chưa mua gói hóa đơn.',
        'InvalidXMLData' => 'Dữ liệu hóa đơn không hợp lệ.',
        'InvoiceQuantityTooLarge' => 'Vượt quá 50 hóa đơn mỗi lần gửi.',
    ];

    public static function classify(string $code): string
    {
        return in_array($code, self::RETRYABLE, true) ? 'retryable' : 'non_retryable';
    }

    public static function message(string $code): string
    {
        return self::MESSAGES[$code] ?? $code;
    }
}
