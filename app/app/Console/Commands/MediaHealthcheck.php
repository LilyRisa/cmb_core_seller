<?php

namespace CMBcoreSeller\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * `php artisan media:healthcheck` — kiểm tra disk lưu media (Cloudflare R2 ở prod): ghi `healthcheck.txt`,
 * đọc lại, in URL công khai, rồi xoá. Báo rõ cấu hình (disk/bucket/endpoint/url) để soi nhanh khi upload
 * ảnh không hoạt động. Xem docs/07-infra/cloudflare-r2-uploads.md §4.
 */
class MediaHealthcheck extends Command
{
    protected $signature = 'media:healthcheck {--keep : Không xoá healthcheck.txt sau khi kiểm}';

    protected $description = 'Kiểm tra ghi/đọc/URL trên disk lưu media (R2 ở prod)';

    public function handle(): int
    {
        $disk = (string) config('media.disk');
        $this->line('media.disk = '.$disk);
        if ($disk === 'r2') {
            $this->line('R2: bucket='.(config('filesystems.disks.r2.bucket') ?: '(trống!)')
                .' · endpoint='.(config('filesystems.disks.r2.endpoint') ?: '(trống!)')
                .' · url='.(config('filesystems.disks.r2.url') ?: '(trống!)')
                .' · region='.config('filesystems.disks.r2.region')
                .' · path_style='.(config('filesystems.disks.r2.use_path_style_endpoint') ? 'true' : 'false'));
            foreach (['bucket' => 'R2_BUCKET', 'key' => 'R2_ACCESS_KEY_ID', 'secret' => 'R2_SECRET_ACCESS_KEY', 'endpoint' => 'R2_ENDPOINT', 'url' => 'R2_URL'] as $k => $env) {
                if (blank(config("filesystems.disks.r2.$k"))) {
                    $this->error("Thiếu cấu hình R2: $env chưa được đặt.");
                }
            }
        }

        $key = 'healthcheck.txt';
        $body = 'ok '.now()->toIso8601String();
        try {
            Storage::disk($disk)->put($key, $body);
        } catch (\Throwable $e) {
            $this->error('Ghi thất bại: '.$e->getMessage());
            $this->line('Gợi ý: SignatureDoesNotMatch ⇒ sai key/secret hoặc sai bucket-scope của token; lỗi endpoint/region ⇒ R2_ENDPOINT sai hoặc R2_DEFAULT_REGION khác "auto".');

            return self::FAILURE;
        }
        $read = Storage::disk($disk)->get($key);
        $url = Storage::disk($disk)->url($key);
        $this->info('Ghi OK · đọc lại '.($read === $body ? 'KHỚP' : 'KHÔNG khớp (!)'));
        $this->info('URL công khai: '.$url);
        $this->line('→ Mở URL trên trình duyệt: phải tải được tệp text. 401/403/404 ⇒ chưa bật public access cho bucket hoặc R2_URL sai.');
        if (! $this->option('keep')) {
            Storage::disk($disk)->delete($key);
            $this->line('Đã xoá '.$key.'.');
        }

        return self::SUCCESS;
    }
}
