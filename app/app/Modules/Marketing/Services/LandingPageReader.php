<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Đọc nội dung trang đích (landing page) để làm ngữ cảnh cho AI sinh chiến dịch:
 * fetch URL → bỏ thẻ HTML → text gọn. Best-effort: lỗi/timeout/URL sai ⇒ null.
 */
final class LandingPageReader
{
    public function read(string $url, int $maxChars = 4000): ?string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        try {
            $res = Http::timeout(15)->withHeaders(['User-Agent' => 'CMBcoreSellerBot/1.0'])->get($url);
        } catch (\Throwable $e) {
            Log::info('marketing.landing_read.failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $res->successful()) {
            return null;
        }

        // Bỏ script/style rồi strip tag — đủ cho ngữ cảnh từ khoá.
        $body = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $res->body());
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($body))));
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $maxChars);
    }
}
