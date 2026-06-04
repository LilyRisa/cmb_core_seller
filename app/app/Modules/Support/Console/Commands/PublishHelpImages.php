<?php

namespace CMBcoreSeller\Modules\Support\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Tải ảnh minh hoạ của Trung tâm trợ giúp (support_doc/images/*) lên object storage
 * (mặc định disk `r2`) dưới prefix `help/`, để FE hiển thị qua domain công khai
 * (R2_URL, ví dụ https://static.cmbcore.com) → khớp HELP_IMAGE_BASE ở frontend.
 *
 *   php artisan support:publish-help-images
 *   php artisan support:publish-help-images --prefix=help --disk=r2
 *
 * Idempotent: ghi đè theo tên file. Cần disk có cấu hình (R2_* hoặc AWS_*).
 */
class PublishHelpImages extends Command
{
    protected $signature = 'support:publish-help-images
        {--disk=r2 : Tên disk filesystem (mặc định r2)}
        {--prefix=help : Tiền tố thư mục trên bucket}
        {--source= : Thư mục ảnh nguồn (mặc định <repo>/support_doc/images)}';

    protected $description = 'Tải ảnh Trung tâm trợ giúp (support_doc/images) lên R2/S3 dưới prefix help/';

    public function handle(): int
    {
        $diskName = (string) $this->option('disk');
        $prefix = trim((string) $this->option('prefix'), '/');
        $source = (string) ($this->option('source') ?: base_path('../support_doc/images'));

        if (! is_dir($source)) {
            $this->error("Không thấy thư mục ảnh nguồn: {$source}");

            return self::FAILURE;
        }

        try {
            $disk = Storage::disk($diskName);
        } catch (\Throwable $e) {
            $this->error("Disk '{$diskName}' không hợp lệ: ".$e->getMessage());

            return self::FAILURE;
        }

        $files = glob(rtrim($source, '/').'/*.{png,jpg,jpeg,webp,gif}', GLOB_BRACE) ?: [];
        if ($files === []) {
            $this->warn("Không có ảnh nào trong {$source}");

            return self::SUCCESS;
        }

        $this->info(sprintf('Tải %d ảnh lên disk "%s" prefix "%s/"…', count($files), $diskName, $prefix));

        $rows = [];
        $ok = 0;
        foreach ($files as $file) {
            $name = basename($file);
            $key = ($prefix !== '' ? $prefix.'/' : '').$name;

            try {
                $stream = fopen($file, 'rb');
                $disk->writeStream($key, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $ok++;

                try {
                    $url = $disk->url($key);
                } catch (\Throwable) {
                    $url = $key;
                }
                $rows[] = [$name, $url];
            } catch (\Throwable $e) {
                $rows[] = [$name, 'LỖI: '.$e->getMessage()];
            }
        }

        $this->table(['Ảnh', 'URL công khai'], $rows);
        $this->info("Xong: {$ok}/".count($files).' ảnh đã tải lên.');

        if ($ok < count($files)) {
            $this->warn('Có ảnh tải lỗi — kiểm tra cấu hình disk (R2_ACCESS_KEY_ID/SECRET/BUCKET/ENDPOINT/URL).');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
