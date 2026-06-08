<?php

namespace CMBcoreSeller\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores user-uploaded media on the configured media disk (Cloudflare R2 in
 * production — see config/media.php & docs/07-infra/cloudflare-r2-uploads.md) and
 * returns the public URL. Tenant-scoped paths keep one tenant's files together.
 */
class MediaUploader
{
    /**
     * Configured media disk name, normalised (lowercase/trim) + validated.
     *
     * Normalise here regardless of source: `config('media.disk')` is already
     * normalised, but a value read from DB via `system_setting('storage.media_disk')`
     * is NOT — e.g. admin/env "R2" must still resolve to the lowercase "r2" disk
     * declared in config/filesystems.php (regression after moving config to DB).
     */
    public function diskName(): string
    {
        $name = strtolower(trim((string) system_setting('storage.media_disk', config('media.disk', 'public'))));
        if (! is_array(config("filesystems.disks.$name"))) {
            $known = implode(', ', array_keys((array) config('filesystems.disks', [])));
            throw new \RuntimeException("Disk lưu media [{$name}] chưa được khai trong config/filesystems.php (có: {$known}). Kiểm tra MEDIA_DISK / xem docs/07-infra/cloudflare-r2-uploads.md.");
        }

        return $name;
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk($this->diskName());
    }

    /**
     * @return array{path: string, url: string} the stored object key and its public URL
     */
    public function storeImage(UploadedFile $file, int $tenantId, string $folder): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $name = Str::ulid().'.'.$ext;
        $dir = "tenants/{$tenantId}/{$folder}";

        // No explicit visibility/ACL: R2 serves public at the bucket level (per-object ACL is ignored
        // and can error on R2), and the local "public" disk already has visibility=public.
        $path = $this->disk()->putFileAs($dir, $file, $name);
        if ($path === false) {
            throw new \RuntimeException('Không lưu được tệp lên kho lưu trữ.');
        }

        return ['path' => $path, 'url' => $this->url($path)];
    }

    /**
     * Store a NON-tenant (system-wide) file under <folder>/<ulid>.<ext> — e.g. ảnh/video
     * trong nội dung announcement do super-admin tạo (SPEC 0037). Public-read như media khác.
     *
     * @return array{path: string, url: string}
     */
    public function storePublic(UploadedFile $file, string $folder): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $name = Str::ulid().'.'.$ext;
        $dir = preg_replace('/[^a-z0-9_-]/', '', strtolower($folder)) ?: 'misc';

        $path = $this->disk()->putFileAs($dir, $file, $name);
        if ($path === false) {
            throw new \RuntimeException('Không lưu được tệp lên kho lưu trữ.');
        }

        return ['path' => $path, 'url' => $this->url($path)];
    }

    /**
     * Store raw bytes (e.g. a generated PDF) under tenants/<id>/<folder>/<name>.<ext>.
     *
     * @return array{path: string, url: string}
     */
    public function storeBytes(string $contents, int $tenantId, string $folder, string $name, string $ext): array
    {
        $path = "tenants/{$tenantId}/{$folder}/{$name}.{$ext}";
        if ($this->disk()->put($path, $contents) === false) {
            throw new \RuntimeException('Không lưu được tệp lên kho lưu trữ.');
        }

        return ['path' => $path, 'url' => $this->url($path)];
    }

    /**
     * Fetch a remote image (e.g. a marketplace product image URL) and store it on the media
     * disk — so the image lives in our storage (R2) instead of hot-linking a marketplace CDN
     * that may expire or block. Best-effort: returns null on any failure (bad URL, non-image,
     * network error, oversized) — callers degrade gracefully. The body is validated by magic
     * bytes so an HTML error page is never stored as an image.
     *
     * @return array{path: string, url: string}|null
     */
    public function storeImageFromUrl(string $url, int $tenantId, string $folder): ?array
    {
        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }
        try {
            $resp = Http::timeout(12)->connectTimeout(5)->retry(1, 300, throw: false)->get($url);
        } catch (\Throwable) {
            return null;
        }
        if (! $resp->successful()) {
            return null;
        }
        $body = (string) $resp->body();
        // Reject empty or oversized (>15MB) payloads.
        if ($body === '' || strlen($body) > 15 * 1024 * 1024) {
            return null;
        }
        $ext = self::sniffImageExtension($body);
        if ($ext === null) {
            return null;   // not a real image (magic bytes didn't match) — don't store
        }

        return $this->storeBytes($body, $tenantId, $folder, Str::ulid()->__toString(), $ext);
    }

    /** Detect image type from magic bytes; null if not a supported image. */
    private static function sniffImageExtension(string $bytes): ?string
    {
        return match (true) {
            str_starts_with($bytes, "\xFF\xD8\xFF") => 'jpg',
            str_starts_with($bytes, "\x89PNG\x0D\x0A\x1A\x0A") => 'png',
            str_starts_with($bytes, 'GIF87a') || str_starts_with($bytes, 'GIF89a') => 'gif',
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => 'webp',
            default => null,
        };
    }

    /** Read raw bytes back from a stored object key (used to merge label PDFs). */
    public function get(string $path): ?string
    {
        $disk = $this->disk();

        return $disk->exists($path) ? $disk->get($path) : null;
    }

    /** Public URL for a stored object key (uses the disk's configured `url`). */
    public function url(string $path): string
    {
        return $this->disk()->url($path);
    }

    /** Best-effort delete; ignores "not found". Pass the stored object key (not the URL). */
    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }
        $disk = $this->disk();
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
