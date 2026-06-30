<?php

use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

/**
 * Bơm feature mới `messaging_zalo` (Nhắn tin Zalo OA — SPEC 0039) vào các gói HIỆN CÓ
 * khi deploy. PHẪU THUẬT: chỉ THÊM cờ `messaging_zalo` (= giá trị `messaging_inbox` của gói,
 * vì cùng phân bố Free off / Basic+Pro on) nếu CHƯA có — KHÔNG re-seed toàn bộ để tránh
 * ghi đè giá/hạn mức đã tuỳ chỉnh trên prod. Idempotent. Bỏ qua khi chạy test. SPEC 2026-06-30.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (App::runningUnitTests()) {
            return;
        }

        foreach (Plan::all() as $plan) {
            $features = (array) $plan->features;
            if (! array_key_exists('messaging_zalo', $features)) {
                $features['messaging_zalo'] = (bool) ($features['messaging_inbox'] ?? false);
                $plan->features = $features;
                $plan->save();
            }
        }
    }

    public function down(): void
    {
        // Catalog data — không revert.
    }
};
