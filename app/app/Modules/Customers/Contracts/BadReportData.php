<?php

namespace CMBcoreSeller\Modules\Customers\Contracts;

/**
 * DTO chuẩn cho kết quả tra cứu "bom hàng" từ một nhà cung cấp bên ngoài
 * (hiện tại: Pancake POS — SPEC 0038). Trung lập với nhà cung cấp: Customers
 * module chỉ biết DTO này, không biết Pancake.
 *
 * `matched` = nhà cung cấp gọi THÀNH CÔNG và xét tới số này (kể cả khi sạch,
 * mọi số = 0). Phân biệt "đã tra, sạch" với "lỗi/chưa tra" để lớp cache biết
 * có nên ghi đè hay không.
 */
final class BadReportData
{
    /**
     * @param  array<int,array{reason:string,reported_at:?string}>  $warnings
     */
    public function __construct(
        public readonly int $orderFail,
        public readonly int $orderSuccess,
        public readonly int $warningCount,
        public readonly array $warnings,
        public readonly string $matchedPhone,
        public readonly bool $matched,
    ) {}

    /** Một kết quả "sạch" (gọi thành công nhưng không có dữ liệu xấu cho số này). */
    public static function clean(string $phone): self
    {
        return new self(0, 0, 0, [], $phone, true);
    }

    /** Có gì đáng hiển thị cho người bán không (đếm > 0 hoặc có lý do bom). */
    public function hasData(): bool
    {
        return $this->orderFail > 0
            || $this->orderSuccess > 0
            || $this->warningCount > 0
            || $this->warnings !== [];
    }
}
