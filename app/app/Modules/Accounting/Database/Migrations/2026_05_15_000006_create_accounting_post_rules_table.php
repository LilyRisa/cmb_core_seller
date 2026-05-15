<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.1 — SPEC 0019: mapping rule cho auto-post (tenant chỉnh được qua UI).
 *
 * Tenant onboard ⇒ seeder áp default mapping theo TT133. Tenant đổi mapping ⇒ chỉ ảnh hưởng entry mới
 * (entry cũ đã immutable). Snapshot rule vào `journal_entries.meta`? — không cần; audit log đủ.
 *
 * `event_key` chuẩn hoá: `{source_module}.{source_type}[.{kind}]` ⇒ dễ extend.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_post_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('event_key', 64);
            $table->string('debit_account_code', 16);
            $table->string('credit_account_code', 16);
            $table->boolean('is_enabled')->default(true);
            $table->string('notes', 500)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_post_rules');
    }
};
