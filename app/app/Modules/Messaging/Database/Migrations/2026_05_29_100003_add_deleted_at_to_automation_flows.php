<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft delete cho `automation_flows` — xoá ở UI là soft delete (khôi phục được,
 * giữ audit), giống `auto_reply_rules` / `message_templates`. Flow đang xoá KHÔNG
 * khớp trigger nữa (matcher chỉ lấy active); run đang chạy vẫn đọc được graph.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_flows', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('automation_flows', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
