<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reclassify sticker CŨ (đã lưu là kind='image') → kind='sticker'.
 *
 * Trước fix, sticker Facebook được chuẩn hoá thành kind='image' xuyên suốt ⇒ FE cho
 * phóng to như ảnh + AI vision nạp cả sticker vào prompt. Sau fix, connector gắn
 * kind='sticker'. Data migration này sửa các bản ghi CŨ để hành vi đồng nhất.
 *
 * Marker tin cậy: `filename = 'sticker'` — chỉ nhánh sticker của FacebookPageConnector
 * đặt tên này (upload outbound dùng tên file gốc của NV). Scope thêm kind='image' để
 * idempotent (chạy lại không đụng bản ghi đã đúng).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Attachment: image + filename='sticker' ⇒ sticker. Đây là cột FE render +
        //    AiSuggestionService lọc (imageUrlsFor/latestInboundImage where kind=image).
        DB::table('message_attachments')
            ->where('kind', 'image')
            ->where('filename', 'sticker')
            ->update(['kind' => 'sticker']);

        // 2) Message kind (nhãn hiển thị khi tin không có body): chỉ tin CHỈ chứa sticker
        //    (1 attachment) mới đổi sang 'sticker' để tránh đụng tin ảnh + sticker lẫn lộn.
        $stickerMessageIds = DB::table('message_attachments')
            ->where('kind', 'sticker')
            ->where('filename', 'sticker')
            ->pluck('message_id')
            ->unique()
            ->all();

        if ($stickerMessageIds !== []) {
            foreach (array_chunk($stickerMessageIds, 1000) as $chunk) {
                DB::table('messages')
                    ->whereIn('id', $chunk)
                    ->where('kind', 'image')
                    ->where('attachments_count', 1)
                    ->update(['kind' => 'sticker']);
            }
        }
    }

    public function down(): void
    {
        // Đưa sticker về image (đảo ngược an toàn — dữ liệu gốc trước fix là 'image').
        DB::table('message_attachments')
            ->where('kind', 'sticker')
            ->where('filename', 'sticker')
            ->update(['kind' => 'image']);

        DB::table('messages')
            ->where('kind', 'sticker')
            ->update(['kind' => 'image']);
    }
};
