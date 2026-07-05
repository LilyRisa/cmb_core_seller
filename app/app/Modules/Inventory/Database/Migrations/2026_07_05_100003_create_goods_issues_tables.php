<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phiếu XUẤT kho (goods_issues) — đối xứng phiếu nhập (goods_receipts). draft → confirmed
 * (áp sổ cái: on_hand -= qty, movement `goods_issue`) → cancelled. `reason` = lý do xuất
 * (hủy/hỏng/biếu tặng...). Xem docs/superpowers/specs/2026-07-05-warehouse-goods-issue-and-tenant-safe-sku-design.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code');                    // PXK-...
            $table->foreignId('warehouse_id');
            $table->string('reason')->nullable();      // lý do xuất
            $table->string('note')->nullable();
            $table->string('status')->default('draft'); // draft | confirmed | cancelled
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });
        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('goods_issue_id');
            $table->foreignId('sku_id');
            $table->integer('qty');
            $table->index(['tenant_id', 'goods_issue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_issue_items');
        Schema::dropIfExists('goods_issues');
    }
};
