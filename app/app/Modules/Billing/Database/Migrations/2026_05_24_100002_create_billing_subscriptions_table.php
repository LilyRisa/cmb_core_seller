<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.4 — Bảng `subscriptions` — BelongsToTenant. Mỗi tenant tại 1 thời điểm chỉ có 1
 * subscription ở trạng thái "alive" (trialing/active/past_due). Sau khi cancelled/expired
 * có thể tồn tại nhiều row lịch sử; partial unique đảm bảo tính duy nhất hàng "alive".
 *
 * State machine §SPEC 0018 §4.5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('plan_id');
            $table->string('status', 16)->index();           // trialing|active|past_due|cancelled|expired
            $table->string('billing_cycle', 8);              // monthly|yearly|trial
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end')->index();
            $table->timestamp('cancel_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        // 1 active subscription per tenant. Postgres support partial index;
        // SQLite (test) also supports `WHERE` in CREATE UNIQUE INDEX since 3.8.
        $driver = DB::getDriverName();
        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement(
                "CREATE UNIQUE INDEX subscriptions_one_alive_per_tenant ON subscriptions (tenant_id) ".
                "WHERE status IN ('trialing','active','past_due')"
            );
        } else {
            // MySQL fallback: app-level check trong service.
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->index(['tenant_id', 'status'], 'subscriptions_tenant_status_idx2');
            });
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS subscriptions_one_alive_per_tenant');
        }
        Schema::dropIfExists('subscriptions');
    }
};
