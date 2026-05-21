<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SPEC 2026-05-21: thẻ hội thoại do tenant tạo (tên + màu), gắn vào conversations.tags (id). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('name', 40);
            $table->string('color', 16)->default('#2563EB'); // hex
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_tags');
    }
};
