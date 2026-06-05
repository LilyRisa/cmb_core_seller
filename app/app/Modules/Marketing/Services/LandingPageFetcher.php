<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fetches a landing page server-side (one lightweight GET, capped + cached) and
 * extracts a cohesive summary for the AI: title, meta description, headings,
 * visible text, CTA labels, whether there's a form, and which tracking pixels are
 * present. Used to give richer context for website-conversion campaigns.
 *
 * (Browsers can't read a cross-origin page's HTML — CORS blocks it — so this runs
 * on the server; the extracted summary is small and cached 1h, so the load is tiny.)
 */
class LandingPageFetcher
{
    private const MAX_BYTES = 600_000;

    private const MAX_TEXT = 4000;

    /**
     * @return array{url:string, final_url:string, title:?string, description:?string, headings:list<string>, text:string, ctas:list<string>, has_form:bool, pixels:list<string>}|null
     */
    public function fetch(string $url): ?array
    {
        $url = trim($url);
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return null;
        }

        return Cache::remember('ads.landing.'.md5($url), 3600, function () use ($url): ?array {
            try {
                $res = Http::timeout(8)->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CMBcoreSellerBot/1.0; +https://cmbcore.com)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])->get($url);
            } catch (\Throwable $e) {
                return null;
            }
            if (! $res->successful()) {
                return null;
            }
            $html = substr((string) $res->body(), 0, self::MAX_BYTES);

            return $this->extract($url, (string) ($res->effectiveUri() ?? $url), $html);
        });
    }

    /**
     * @return array{url:string, final_url:string, title:?string, description:?string, headings:list<string>, text:string, ctas:list<string>, has_form:bool, pixels:list<string>}
     */
    private function extract(string $url, string $finalUrl, string $html): array
    {
        $title = $this->firstMatch('/<title[^>]*>(.*?)<\/title>/is', $html);
        $description = $this->metaContent($html, 'description') ?? $this->metaContent($html, 'og:description');

        // Headings h1-h3.
        preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, $hm);
        $headings = array_values(array_filter(array_map(fn ($h) => $this->clean($h), $hm[1]), fn ($s) => $s !== ''));
        $headings = array_slice(array_values(array_unique($headings)), 0, 15);

        // CTA-ish labels: button text + anchor text.
        preg_match_all('/<(?:button|a)[^>]*>(.*?)<\/(?:button|a)>/is', $html, $cm);
        $ctas = array_values(array_filter(array_map(fn ($c) => $this->clean($c), $cm[1]), fn ($s) => $s !== '' && mb_strlen($s) <= 40));
        $ctas = array_slice(array_values(array_unique($ctas)), 0, 20);

        // Tracking pixels.
        $pixels = [];
        if (preg_match('/fbq\(|connect\.facebook\.net\/.+\/fbevents\.js/i', $html)) {
            $pixels[] = 'facebook_pixel';
        }
        if (preg_match('/gtag\(|googletagmanager\.com\/gtag/i', $html)) {
            $pixels[] = 'google_tag';
        }
        if (preg_match('/dataLayer|googletagmanager\.com\/gtm/i', $html)) {
            $pixels[] = 'gtm';
        }
        if (preg_match('/ttq\.|analytics\.tiktok\.com/i', $html)) {
            $pixels[] = 'tiktok_pixel';
        }

        $hasForm = (bool) preg_match('/<form[\s>]/i', $html);

        return [
            'url' => $url,
            'final_url' => $finalUrl,
            'title' => $title !== '' ? $title : null,
            'description' => $description,
            'headings' => $headings,
            'text' => $this->visibleText($html),
            'ctas' => $ctas,
            'has_form' => $hasForm,
            'pixels' => $pixels,
        ];
    }

    private function visibleText(string $html): string
    {
        // Drop script/style/noscript, strip tags, collapse whitespace, truncate.
        $stripped = preg_replace('/<(script|style|noscript|svg)[^>]*>.*?<\/\1>/is', ' ', $html) ?? $html;
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($stripped)));

        return Str::limit(html_entity_decode($text, ENT_QUOTES | ENT_HTML5), self::MAX_TEXT, '…');
    }

    private function metaContent(string $html, string $name): ?string
    {
        $val = $this->firstMatch('/<meta[^>]+(?:name|property)=["\']'.preg_quote($name, '/').'["\'][^>]+content=["\'](.*?)["\']/is', $html)
            ?: $this->firstMatch('/<meta[^>]+content=["\'](.*?)["\'][^>]+(?:name|property)=["\']'.preg_quote($name, '/').'["\']/is', $html);

        return $val !== '' ? $this->clean($val) : null;
    }

    private function firstMatch(string $pattern, string $subject): string
    {
        return preg_match($pattern, $subject, $m) ? $this->clean($m[1]) : '';
    }

    private function clean(string $s): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5)));
    }
}
