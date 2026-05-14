<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.4 — Bảng `plans` (gói thuê bao) — KHÔNG tenant-scoped (chia sẻ toàn hệ thống).
 *
 * Code & giá & hạn mức & feature flags lưu trong DB (admin sửa được; không hardcode).
 * Seeder `BillingPlanSeeder` upsert 4 gói chuẩn: trial / starter / pro / business.
 *
 * Xem SPEC 0018 §5.1; docs/02-data-model/overview.md §Billing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();           // 'trial' | 'starter' | 'pro' | 'business'
            $table->string('name', 120);                    // hiển thị
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            // Tiền: bigint VND đồng (docs/02-data-model/overview.md §1.4). Trial = 0.
            $table->bigInteger('price_monthly')->default(0);
            $table->bigInteger('price_yearly')->default(0);
            $table->string('currency', 3)->default('VND');
            $table->unsignedSmallInteger('trial_days')->default(0);
            // Hạn mức: {max_channel_accounts:int} — `-1` = không giới hạn (gói Enterprise tuỳ chỉnh).
            $table->json('limits');
            // Feature flags: {procurement, fifo_cogs, profit_reports, finance_settlements, demand_planning, mass_listing, automation_rules, priority_support}.
            $table->json('features');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
