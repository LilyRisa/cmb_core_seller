<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * orders.has_unmapped_sku — cờ RIÊNG cho "đơn còn dòng chưa ghép SKU sàn ↔ master SKU".
 * TÁCH khỏi has_issue: chưa ghép SKU KHÔNG phải lỗi (đơn vẫn in & bàn giao bình thường, chỉ không
 * trừ tồn cho dòng chưa ghép). Trước đây dùng chung has_issue=true + issue_reason='SKU chưa ghép' khiến
 * đơn bị coi là "Có vấn đề" và bị RefreshStuckOrders (điều kiện has_issue) đụng vào. Set bởi
 * OrderInventoryService::reflectUnmappedIssue. Backfill: chuyển đơn đang gắn issue này sang cột mới.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('has_unmapped_sku')->default(false)->after('issue_reason');
            $table->index(['tenant_id', 'has_unmapped_sku']);
        });

        // Backfill: đơn đang bị gắn "SKU chưa ghép" như một lỗi ⇒ chuyển sang cột mới, gỡ khỏi has_issue.
        DB::table('orders')
            ->where('has_issue', true)
            ->where('issue_reason', 'SKU chưa ghép')
            ->update(['has_unmapped_sku' => true, 'has_issue' => false, 'issue_reason' => null]);
    }

    public function down(): void
    {
        // Khôi phục về mô hình cũ (dồn ngược vào has_issue) để rollback không mất thông tin.
        DB::table('orders')
            ->where('has_unmapped_sku', true)
            ->where('has_issue', false)
            ->update(['has_issue' => true, 'issue_reason' => 'SKU chưa ghép']);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'has_unmapped_sku']);
            $table->dropColumn('has_unmapped_sku');
        });
    }
};
