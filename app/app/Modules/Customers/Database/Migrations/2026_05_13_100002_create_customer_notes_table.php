<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customer_notes — append-only notes on a customer (manual by staff, or auto when
 * a reputation threshold trips). No soft delete. `dedupe_key` makes auto-notes
 * idempotent per threshold bucket. See SPEC 0002 §4.5, §5.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('customer_id');
            $table->foreignId('author_user_id')->nullable();    // null = system/auto note
            $table->string('kind', 32)->default('manual');      // manual | auto.* | system.merge
            $table->string('severity', 8)->default('info');     // info | warning | danger
            $table->text('note');
            $table->foreignId('order_id')->nullable();
            $table->string('dedupe_key', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'customer_id', 'id']);
            $table->unique(['customer_id', 'dedupe_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
    }
};
