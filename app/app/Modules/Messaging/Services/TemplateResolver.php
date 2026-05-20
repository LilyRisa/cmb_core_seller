<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

/**
 * Resolver thuần (pure) cho body template tin nhắn — KHÔNG chạm DB / model.
 * Tách riêng để unit-test dễ và tái dùng cho cả send-template lẫn auto-reply (S5).
 *
 * Cú pháp biến (xem SPEC-0024 §5.5):
 *   - `{{customer.name}}`      — thay bằng `$context['customer.name']`
 *   - `{{ order.code }}`       — cho phép khoảng trắng quanh tên biến
 *   - `{{customer.name|Quý khách}}` — default khi biến thiếu / rỗng
 *
 * Quy tắc:
 *   - Biến có giá trị (khác null & khác '') ⇒ thay giá trị, ghi vào `used`.
 *   - Biến thiếu nhưng có default ⇒ thay default (KHÔNG tính là missing).
 *   - Biến thiếu & không default ⇒ thay '' và ghi vào `missing`.
 *   - Token không hợp lệ (vd `{{ }}`) ⇒ giữ nguyên, không xử lý.
 *
 * Context là map phẳng dotted-key (do {@see TemplateContextBuilder} dựng):
 *   `['customer.name' => 'Anh A', 'order.code' => 'SO123', ...]`.
 */
class TemplateResolver
{
    /** `{{ key }}` hoặc `{{ key | default }}` — key là dotted alnum/underscore. */
    private const TOKEN = '/\{\{\s*([a-zA-Z0-9_]+(?:\.[a-zA-Z0-9_]+)*)\s*(?:\|\s*(.*?)\s*)?\}\}/u';

    /**
     * @param  array<string,scalar|null>  $context
     */
    public function resolve(string $body, array $context): TemplateRenderResult
    {
        $missing = [];
        $used = [];

        $text = preg_replace_callback(self::TOKEN, function (array $m) use ($context, &$missing, &$used): string {
            $key = $m[1];
            $default = $m[2] ?? null; // null nếu không khai báo default

            $value = $context[$key] ?? null;

            if ($value !== null && $value !== '') {
                $str = (string) $value;
                $used[$key] = $str;

                return $str;
            }

            if ($default !== null) {
                return $default;
            }

            if (! in_array($key, $missing, true)) {
                $missing[] = $key;
            }

            return '';
        }, $body);

        return new TemplateRenderResult(
            text: $text ?? $body,
            missing: $missing,
            used: $used,
        );
    }

    /**
     * Liệt kê các biến `{{...}}` khai báo trong body (để FE gợi ý / validate
     * cột `vars`). Trả dotted-keys duy nhất, giữ thứ tự xuất hiện.
     *
     * @return list<string>
     */
    public function declaredVariables(string $body): array
    {
        preg_match_all(self::TOKEN, $body, $matches);

        $vars = [];
        foreach ($matches[1] as $key) {
            if (! in_array($key, $vars, true)) {
                $vars[] = $key;
            }
        }

        return $vars;
    }
}
