<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tách per-platform cho AI training + tự động trả lời (đồng bộ với flows đã có cột
 * `provider`). Trước đây tài liệu AI không có chiều nền tảng và rule auto-reply
 * thiếu `filter.providers` ⇒ dữ liệu Facebook "rò" sang tab Zalo OA (và RAG runtime
 * dùng tài liệu FB cho hội thoại Zalo).
 *
 * - `ai_knowledge_documents.provider`: gắn mỗi tài liệu vào đúng 1 nền tảng
 *   (mặc định + backfill `facebook_page` vì trước giờ chỉ có Facebook).
 * - Backfill `auto_reply_rules.filter->providers` rỗng ⇒ `['facebook_page']` để rule
 *   cũ chỉ hiện/chạy cho Facebook (engine đã lọc theo providers; rỗng = mọi provider).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_knowledge_documents', function (Blueprint $table) {
            $table->string('provider', 32)->default('facebook_page')->after('applies_all_pages')->index();
        });

        // Tài liệu cũ đều là Facebook.
        DB::table('ai_knowledge_documents')->update(['provider' => 'facebook_page']);

        // Backfill providers cho rule auto-reply cũ (filter JSON, cross-DB ⇒ duyệt PHP).
        DB::table('auto_reply_rules')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $filter = json_decode((string) ($row->filter ?? '{}'), true);
                $filter = is_array($filter) ? $filter : [];
                $providers = $filter['providers'] ?? [];
                if (! is_array($providers) || $providers === []) {
                    $filter['providers'] = ['facebook_page'];
                    DB::table('auto_reply_rules')->where('id', $row->id)->update(['filter' => json_encode($filter)]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_knowledge_documents', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
        // Không revert backfill providers (an toàn, không mất dữ liệu).
    }
};
