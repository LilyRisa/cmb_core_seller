<?php

namespace CMBcoreSeller\Modules\Channels\Console\Commands;

use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Giai đoạn giữa (design 2026-07-14 §2) — chạy 1 LẦN sau migration giai đoạn 1
 * (`2026_07_14_100000_add_dedupe_status_key_to_webhook_events_table`), TRƯỚC migration giai đoạn 2
 * (`2026_07_14_100001_add_dedupe_unique_to_webhook_events_table`):
 *   1. Xoá row TRÙNG THẬT theo khoá dedupe HIỆU LỰC (provider, event_type, external_id,
 *      external_shop_id, dedupe_status_key ?? (order_raw_status ?? '')) — giữ lại id nhỏ nhất mỗi
 *      nhóm — dọn sạch TRƯỚC khi ghi cột, để giai đoạn 2 thêm unique constraint an toàn.
 *   2. Backfill `dedupe_status_key` cho row cũ còn lại (order_raw_status ?? '').
 * Idempotent — chạy lại không đổi gì nếu đã sạch.
 *
 * Thứ tự CỐ Ý ngược lại thời điểm task được viết ra: nếu backfill (ghi cột) chạy TRƯỚC dedupe
 * (xoá row), thì khi Task 6 đã thêm unique constraint thật vào DB, chính lần ghi `dedupe_status_key`
 * của row trùng thứ 2 trong một cặp trùng sẽ tự đụng constraint đó giữa chừng — trước khi vòng dedupe
 * kịp xoá nó. Chạy dedupe trước (dùng khoá HIỆU LỰC — tức giá trị SẼ được backfill, không phải giá
 * trị cột hiện tại có thể vẫn NULL) loại bỏ trùng lặp trước khi có bất kỳ ghi nào, nên an toàn dù
 * chạy trên DB đã có hay chưa có unique constraint. Xem WebhookDedupeUniqueConstraintTest (Task 6) +
 * BackfillWebhookDedupeKeyTest — cả hai đều chạy dưới RefreshDatabase nên constraint luôn có mặt.
 *
 * Ghi chú chunkById: vòng dedupe bên dưới xoá row NGAY TRONG callback của chunkById. An toàn vì
 * chunkById lấy con trỏ phân trang (`WHERE id > last_id`) từ collection ĐÃ nạp vào bộ nhớ TRƯỚC khi
 * callback chạy — không re-query giữa chừng như `chunk()` (offset-based). Ta chỉ xoá row đã nằm trong
 * chunk hiện tại (id <= con trỏ), không bao giờ xoá row thuộc chunk tương lai, nên `WHERE id > last_id`
 * của lần gọi kế tiếp không bị lệch. Đây cũng là lý do Laravel khuyến nghị chunkById (thay vì chunk)
 * khi xoá/sửa trong lúc phân trang.
 */
class BackfillWebhookDedupeKey extends Command
{
    protected $signature = 'webhooks:backfill-dedupe-key';

    protected $description = 'Backfill dedupe_status_key + xoá row webhook_events trùng thật (design 2026-07-14, chạy trước migration giai đoạn 2)';

    public function handle(): int
    {
        $removed = 0;
        /** @var array<string, int> $seen khoá dedupe hiệu lực => id nhỏ nhất đã thấy */
        $seen = [];
        WebhookEvent::query()->orderBy('id')
            ->chunkById(500, function (Collection $rows) use (&$seen, &$removed) {
                foreach ($rows as $row) {
                    // Khoá HIỆU LỰC — giá trị dedupe_status_key SẼ có sau bước backfill kế tiếp (row cũ
                    // còn NULL thì coi như order_raw_status ?? ''), để phát hiện trùng ĐÚNG trước khi ghi.
                    $effectiveDedupeKey = $row->dedupe_status_key ?? ($row->order_raw_status ?? '');
                    $key = json_encode([
                        $row->provider, $row->event_type, (string) $row->external_id,
                        (string) $row->external_shop_id, $effectiveDedupeKey,
                    ]);
                    if (isset($seen[$key])) {
                        $row->delete();
                        $removed++;

                        continue;
                    }
                    $seen[$key] = $row->id;
                }
            });

        $this->info("Đã xoá {$removed} row trùng thật (giữ id nhỏ nhất mỗi nhóm).");

        $backfilled = 0;
        WebhookEvent::query()->whereNull('dedupe_status_key')->orderBy('id')
            ->chunkById(500, function (Collection $rows) use (&$backfilled) {
                foreach ($rows as $row) {
                    $row->forceFill(['dedupe_status_key' => $row->order_raw_status ?? ''])->save();
                    $backfilled++;
                }
            });
        $this->info("Đã backfill dedupe_status_key cho {$backfilled} row.");

        return self::SUCCESS;
    }
}
