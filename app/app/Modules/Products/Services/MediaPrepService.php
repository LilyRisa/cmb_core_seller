<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Services;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Prepares product images for a given marketplace before publishing.
 *
 * For each source URL: enforce the provider's image constraints (resize /
 * recompress with GD only when the original violates them), upload via the
 * provider's publisher, and return the resulting media refs. Upload results are
 * cached per (provider, source-url) so the same image is not re-uploaded.
 *
 * GD-only by design (no Intervention/Imagick). Defensive: any decode/fetch
 * failure falls back to passing the original URL straight to the publisher.
 */
final class MediaPrepService
{
    public function __construct(private PublisherRegistry $publishers) {}

    /**
     * @param  string[]  $sourceUrls
     * @return MediaRefDTO[]
     */
    public function prepare(string $provider, AuthContext $auth, array $sourceUrls, string $useCase = 'main'): array
    {
        $refs = [];

        foreach ($sourceUrls as $url) {
            $cacheKey = 'listing_media:'.$provider.':'.sha1($url);
            $cached = Cache::get($cacheKey);

            if ($cached instanceof MediaRefDTO) {
                $refs[] = $cached;

                continue;
            }

            $prepared = $this->fetchAndConstrain($provider, $url);
            $ref = $this->publishers->for($provider)->uploadMedia($auth, $prepared, $useCase);

            Cache::put($cacheKey, $ref, now()->addHours(12));
            $refs[] = $ref;
        }

        return $refs;
    }

    /**
     * Download the image and, if it violates the provider's edge constraints,
     * resize/recompress via GD into a temp file. Returns a local path when a
     * new file was produced, or the original URL when no change is needed.
     */
    protected function fetchAndConstrain(string $provider, string $url): string
    {
        // Shopee has no dimension rule (size-only cap) — keep it simple and skip
        // recompression in this task.
        if ($provider === 'shopee') {
            return $url;
        }

        try {
            $bytes = Http::get($url)->body();
        } catch (\Throwable) {
            return $url;
        }

        if ($bytes === '') {
            return $url;
        }

        $size = @getimagesizefromstring($bytes);
        if ($size === false) {
            return $url;
        }

        [$width, $height] = $size;
        [$minEdge, $maxEdge] = $this->rules($provider);
        $longest = max($width, $height);
        $shortest = min($width, $height);

        $scale = 1.0;
        if ($longest > $maxEdge) {
            $scale = $maxEdge / $longest;
        } elseif ($shortest < $minEdge) {
            $scale = $minEdge / $shortest;
        }

        if ($scale === 1.0) {
            return $url;
        }

        $img = @imagecreatefromstring($bytes);
        if ($img === false) {
            return $url;
        }

        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));

        $scaled = @imagescale($img, $newW, $newH);
        if ($scaled === false) {
            imagedestroy($img);

            return $url;
        }

        $path = (string) tempnam(sys_get_temp_dir(), 'mediaprep');
        @imagejpeg($scaled, $path, 85);

        imagedestroy($img);
        imagedestroy($scaled);

        return $path;
    }

    /**
     * Min/max allowed edge length (px) per provider.
     *
     * @return array{0:int,1:int} [minEdge, maxEdge]
     */
    private function rules(string $provider): array
    {
        return match ($provider) {
            'lazada' => [0, 5000],
            'tiktok' => [300, 4000],
            default => [0, PHP_INT_MAX],
        };
    }
}
