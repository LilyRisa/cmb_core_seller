<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // API key bên thứ 3 (SPEC 2026-06-26): gắn cứng tenant ⇒ token tự khóa shop (không cần X-Tenant-Id).
            $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('abilities');
            // 'api_key' = key bên thứ 3 (UI quản lý owner-only); null = token mobile/extension (không hiện trong UI).
            $table->string('kind', 24)->nullable()->after('tenant_id');
            // 4 ký tự cuối token plaintext để gợi nhớ (cột `token` là HASH nên không suy ra được).
            $table->string('last_four', 8)->nullable()->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', fn (Blueprint $table) => $table->dropColumn(['tenant_id', 'kind', 'last_four']));
    }
};
