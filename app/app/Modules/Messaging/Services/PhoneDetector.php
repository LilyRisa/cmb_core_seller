<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

/**
 * Nhận diện SĐT Việt Nam trong text. Dùng chung pattern với PiiRedactor
 * (08-security-and-privacy §6b §3). Pure helper — không stateful.
 */
class PhoneDetector
{
    /** SĐT VN: +84 / 84 / 0 + đầu số 3/5/7/8/9 + 8 chữ số. */
    public const PATTERN = '/(?<!\d)(?:\+84|84|0)(?:3|5|7|8|9)\d{8}(?!\d)/u';

    /** Trả SĐT đầu tiên tìm thấy (chuẩn hoá nguyên trạng), hoặc null. */
    public function firstPhone(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }
        if (preg_match(self::PATTERN, $text, $m) === 1) {
            return $m[0];
        }

        return null;
    }

    public function hasPhone(?string $text): bool
    {
        return $this->firstPhone($text) !== null;
    }
}
