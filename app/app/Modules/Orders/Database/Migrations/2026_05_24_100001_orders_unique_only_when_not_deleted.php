<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Đổi `orders_source_account_external_unique` (source, channel_account_id, external_order_id) từ unique
 * thường ⇒ **partial unique** chỉ áp khi `deleted_at IS NULL`. Lý do:
 *
 *   - Order model dùng SoftDeletes; `xoá kết nối gian hàng` (SPEC trong commit bba66d3) soft-delete toàn
 *     bộ đơn của shop. Khi sàn re-push hoặc user reconnect ⇒ sync upsert thấy không có row active,
 *     INSERT mới ⇒ DB chặn vì row soft-deleted vẫn chiếm key ⇒ `SQLSTATE[23505] Unique violation` ⇒
 *     sync job warning từng đơn, không quay lại được. Xem log:
 *       `sync.upsert_failed ... duplicate key value violates unique constraint "orders_source_account_external_unique"`.
 *   - `OrderUpsertService::doUpsert` đã được sửa để `withTrashed() + restore()`; partial unique index
 *     ở đây là defense-in-depth + đúng nghĩa: 1 cặp key active duy nhất (soft-deleted rows không tính).
 *
 * MySQL: không hỗ trợ partial index ⇒ migration này KHÔNG đụng (vẫn dùng unique thường, code restore là đủ).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }
        // Drop trước (idempotent) — `dropUnique` không hỗ trợ partial nên phải dùng raw.
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_source_account_external_unique');
        DB::statement('DROP INDEX IF EXISTS orders_source_account_external_unique');
        DB::statement('CREATE UNIQUE INDEX orders_source_account_external_unique ON orders (source, channel_account_id, external_order_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }
        DB::statement('DROP INDEX IF EXISTS orders_source_account_external_unique');
        // Khôi phục unique thường — KHÔNG có partial. Lưu ý: nếu lúc rollback có rows trùng key
        // ở dạng soft-deleted + active, migration này sẽ fail; xoá row đã soft-delete trước rồi rollback.
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_source_account_external_unique UNIQUE (source, channel_account_id, external_order_id)');
    }
};
