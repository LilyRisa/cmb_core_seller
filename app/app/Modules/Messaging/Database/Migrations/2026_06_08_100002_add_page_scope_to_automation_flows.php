<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0035 — per-page scoping cho kịch bản tự động (automation flows).
 * Cờ `applies_all_pages` + pivot flow↔page. Flow cũ ⇒ áp mọi page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_flows', function (Blueprint $table) {
            $table->boolean('applies_all_pages')->default(false)->after('enabled');
        });

        Schema::create('automation_flow_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('automation_flow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_account_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['automation_flow_id', 'channel_account_id']);
            $table->index('channel_account_id');
        });

        DB::table('automation_flows')->update(['applies_all_pages' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_flow_page');
        Schema::table('automation_flows', function (Blueprint $table) {
            $table->dropColumn('applies_all_pages');
        });
    }
};
