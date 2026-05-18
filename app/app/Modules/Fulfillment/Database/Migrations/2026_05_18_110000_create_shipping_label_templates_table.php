<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template alias cho phiếu giao hàng đơn manual (drag/drop trên Konva editor).
 * Schema JSON versioned ; chỉ áp dụng `type=delivery`. SPEC 0006 §3.3 — custom print templates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_label_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('name', 120);
            $table->string('paper', 16);                            // 'A4'|'A5'|'A6'|'100x150mm'|'80mm'|'custom'
            $table->unsignedSmallInteger('paper_w_mm');
            $table->unsignedSmallInteger('paper_h_mm');              // 0 = khổ cuộn (auto)
            $table->unsignedTinyInteger('schema_version')->default(1);
            $table->json('schema');                                  // { fields: [...] }
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_label_templates');
    }
};
