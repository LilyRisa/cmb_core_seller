<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

/**
 * Redact PII trong text trước khi gửi qua LLM ngoài (Claude/OpenAI/Gemini).
 *
 * Patterns (VN-centric):
 *   - SĐT VN: `(?:\+84|84|0)\d{9,10}`  → `[PHONE_N]`
 *   - Email: chuẩn  → `[EMAIL_N]`
 *   - STK ngân hàng: 9-19 chữ số sau từ khoá "stk|tk|account|số tài khoản|tk:|stk:" → `[ACCOUNT_N]`
 *   - CMND/CCCD: 9 hoặc 12 chữ số sau "cmnd|cccd|chứng minh|căn cước" → `[ID_N]`
 *
 * Mapping `placeholder → original` được giữ ở caller (nếu cần restore sau khi LLM trả).
 * Caller gọi `redact($text)` → `RedactResult { redacted, mapping }`. Pure helper —
 * không stateful, không log.
 *
 * `08-security-and-privacy.md` §6b §3.
 */
class PiiRedactor
{
    public function redact(string $text): RedactResult
    {
        $mapping = [];
        $counter = ['PHONE' => 0, 'EMAIL' => 0, 'ACCOUNT' => 0, 'ID' => 0];

        // Email (chạy trước SĐT để không nuốt số trong domain)
        $text = preg_replace_callback(
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/u',
            function (array $m) use (&$mapping, &$counter): string {
                $counter['EMAIL']++;
                $placeholder = '[EMAIL_'.$counter['EMAIL'].']';
                $mapping[$placeholder] = $m[0];
                return $placeholder;
            },
            $text
        );

        // STK ngân hàng (cần keyword đứng trước)
        $text = preg_replace_callback(
            '/(?:stk|tk|account|số tài khoản|tk\s*[:.]|stk\s*[:.])\s*[:.]?\s*(\d{9,19})/iu',
            function (array $m) use (&$mapping, &$counter): string {
                $counter['ACCOUNT']++;
                $placeholder = '[ACCOUNT_'.$counter['ACCOUNT'].']';
                $mapping[$placeholder] = $m[1];
                return str_replace($m[1], $placeholder, $m[0]);
            },
            $text
        );

        // CMND/CCCD (cần keyword đứng trước)
        $text = preg_replace_callback(
            '/(?:cmnd|cccd|chứng minh|căn cước)\s*[:.]?\s*(\d{9}|\d{12})/iu',
            function (array $m) use (&$mapping, &$counter): string {
                $counter['ID']++;
                $placeholder = '[ID_'.$counter['ID'].']';
                $mapping[$placeholder] = $m[1];
                return str_replace($m[1], $placeholder, $m[0]);
            },
            $text
        );

        // SĐT VN (chặt: không nuốt số bất kỳ — phải có format đầy đủ)
        $text = preg_replace_callback(
            '/(?<!\d)(?:\+84|84|0)(?:3|5|7|8|9)\d{8}(?!\d)/u',
            function (array $m) use (&$mapping, &$counter): string {
                $counter['PHONE']++;
                $placeholder = '[PHONE_'.$counter['PHONE'].']';
                $mapping[$placeholder] = $m[0];
                return $placeholder;
            },
            $text
        );

        return new RedactResult(
            redacted: $text,
            mapping: $mapping,
            counts: $counter,
        );
    }
}
