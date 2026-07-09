<?php

namespace CMBcoreSeller\Console\Commands;

use Illuminate\Console\Command;

/**
 * Gộp các bước SAU redeploy vào 1 lệnh — chạy 1 phát là xong.
 *
 *   php artisan app:deploy              # thường lệ: migrate (pending) + restart queue
 *   php artisan app:deploy --reindex    # + nạp lại vector RAG (text + ảnh) — chỉ cần 1 LẦN khi
 *                                        #   bật/sửa embedding hoặc Qdrant trống (không chạy mỗi deploy)
 *   php artisan app:deploy --reindex --fresh --tenant=1
 *
 * LƯU Ý: reindex là backfill TỐN kém (gọi API embedding) — KHÔNG để mặc định mỗi deploy; Qdrant
 * persistent nên dữ liệu cũ còn nguyên và dữ liệu MỚI tự embed khi thêm. Chỉ bật `--reindex` khi
 * thực sự cần nạp lại toàn bộ.
 */
class Deploy extends Command
{
    protected $signature = 'app:deploy
        {--reindex : Nạp lại vector RAG (messaging:kb-reindex + visualsearch:reindex) — chỉ 1 lần khi cần}
        {--fresh : Recreate collection Qdrant (khi đổi embedding model) — chỉ áp dụng khi --reindex}
        {--tenant= : Giới hạn reindex trong 1 tenant}';

    protected $description = 'Chạy các bước sau redeploy (migrate + tùy chọn reindex + restart queue) trong 1 lệnh.';

    public function handle(): int
    {
        $this->components->info('Deploy: bắt đầu các bước sau redeploy');

        // 1) Migrate — idempotent, no-op nếu không có migration pending.
        $this->components->task('migrate --force', fn () => $this->call('migrate', ['--force' => true]) === self::SUCCESS);

        // 2) Reindex (tùy chọn) — backfill vector RAG. Chỉ khi --reindex.
        if ($this->option('reindex')) {
            $args = [];
            if ($this->option('tenant') !== null) {
                $args['--tenant'] = $this->option('tenant');
            }
            if ($this->option('fresh')) {
                $args['--fresh'] = true;
            }
            $this->components->task('messaging:kb-reindex (RAG text)', fn () => $this->call('messaging:kb-reindex', $args) === self::SUCCESS);
            $this->components->task('visualsearch:reindex (ảnh CLIP)', fn () => $this->call('visualsearch:reindex', $args) === self::SUCCESS);
        } else {
            $this->components->info('Bỏ qua reindex (thêm --reindex nếu cần nạp lại vector).');
        }

        // 3) Báo worker nạp code mới.
        $this->components->task('queue:restart', fn () => $this->call('queue:restart') === self::SUCCESS);

        $this->newLine();
        $this->components->info('Deploy: xong.');

        return self::SUCCESS;
    }
}
