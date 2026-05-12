<?php

namespace CMBcoreSeller\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores user-uploaded media on the configured media disk (Cloudflare R2 in
 * production — see config/media.php & docs/07-ops/cloudflare-r2-uploads.md) and
 * returns the public URL. Tenant-scoped paths keep one tenant's files together.
 */
class MediaUploader
{
    /**
     * @return array{path: string, url: string} the stored object key and its public URL
     */
    public function storeImage(UploadedFile $file, int $tenantId, string $folder): array
    {
        $disk = (string) config('media.disk');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $name = Str::ulid().'.'.$ext;
        $dir = "tenants/{$tenantId}/{$folder}";

        // putFileAs() with 'public' visibility — on R2 the ACL is ignored (bucket-level),
        // on the local "public" disk it makes the file world-readable via /storage.
        $path = Storage::disk($disk)->putFileAs($dir, $file, $name, 'public');
        if ($path === false) {
            throw new \RuntimeException('Không lưu được tệp lên kho lưu trữ.');
        }

        return ['path' => $path, 'url' => $this->url($path)];
    }

    /** Public URL for a stored object key (uses the disk's configured `url`). */
    public function url(string $path): string
    {
        return Storage::disk((string) config('media.disk'))->url($path);
    }

    /** Best-effort delete; ignores "not found". Pass the stored object key (not the URL). */
    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }
        $disk = Storage::disk((string) config('media.disk'));
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
