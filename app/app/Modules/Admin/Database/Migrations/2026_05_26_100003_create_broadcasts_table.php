<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0023 — `broadcasts`: log mỗi lần admin gửi broadcast email cho tenant users.
 * KHÔNG tenant-scoped (admin global).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 255);
            $table->text('body_markdown');
            $table->json('audience');                       // {kind: 'all_owners'|'tenant_ids', tenant_ids?: int[]}
            $table->integer('recipient_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by_user_id');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
