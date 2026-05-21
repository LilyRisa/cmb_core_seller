<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SPEC 2026-05-21: cờ nhận diện SĐT trong hội thoại (lọc + thẻ SĐT). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('has_phone')->default(false)->after('manually_unread');
            $table->string('detected_phone', 32)->nullable()->after('has_phone');
            $table->index(['tenant_id', 'has_phone']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'has_phone']);
            $table->dropColumn(['has_phone', 'detected_phone']);
        });
    }
};
