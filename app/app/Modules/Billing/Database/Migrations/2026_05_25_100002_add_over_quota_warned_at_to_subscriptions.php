<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0020 — đánh dấu lần đầu phát hiện tenant vượt hạn mức (over-quota).
 * Sau `config('billing.over_quota_grace_hours', 48)` giờ vẫn còn vượt ⇒
 * middleware `plan.over_quota_lock` khoá mọi POST/PATCH/DELETE.
 *
 * Scheduler `subscriptions:check-over-quota` set/clear field này hằng giờ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('over_quota_warned_at')->nullable()->after('ended_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['over_quota_warned_at']);
            $table->dropColumn('over_quota_warned_at');
        });
    }
};
