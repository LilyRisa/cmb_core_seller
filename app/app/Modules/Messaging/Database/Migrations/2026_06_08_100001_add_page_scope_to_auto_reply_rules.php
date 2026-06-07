<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0035 — per-page scoping cho tự động trả lời.
 *
 * Cờ `applies_all_pages` + pivot rule↔page. Dữ liệu cũ (chưa có page) ⇒
 * `applies_all_pages=true` để GIỮ hành vi "áp tất cả trang" (không gián đoạn).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_reply_rules', function (Blueprint $table) {
            $table->boolean('applies_all_pages')->default(false)->after('enabled');
        });

        Schema::create('auto_reply_rule_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('auto_reply_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_account_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['auto_reply_rule_id', 'channel_account_id']);
            $table->index('channel_account_id');
        });

        // Backward-compat: rule cũ áp mọi page.
        DB::table('auto_reply_rules')->update(['applies_all_pages' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_reply_rule_page');
        Schema::table('auto_reply_rules', function (Blueprint $table) {
            $table->dropColumn('applies_all_pages');
        });
    }
};
