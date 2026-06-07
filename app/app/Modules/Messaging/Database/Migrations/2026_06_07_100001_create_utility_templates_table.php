<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `utility_templates` (SPEC-0032) — mẫu "tin nhắn tiện ích" (Messenger Utility
 * Messages) per-tenant, per-Page. Sau khi Meta khai tử message tag, đây là cách
 * hợp lệ duy nhất gửi tin giao dịch tự động NGOÀI cửa sổ 24h: đăng ký template
 * (category UTILITY) → Meta duyệt → gửi tham chiếu template đã duyệt kèm biến.
 *
 * `external_template_id` = id phía Meta. `status` = draft|pending|approved|rejected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('channel_account_id')->index();   // Page FB sở hữu template
            $table->string('code', 64);                          // vd order_confirmation
            $table->string('name', 160);
            $table->string('language', 8)->default('vi');
            $table->text('body');                                // có {{1}},{{2}}…
            $table->json('buttons')->nullable();                 // [{type,title,url?,payload?}]
            $table->json('variables')->nullable();               // map {{n}} → nguồn (tracking_url…)
            $table->string('external_template_id')->nullable();  // id phía Meta
            $table->string('status', 16)->default('draft');      // draft|pending|approved|rejected
            $table->string('reject_reason')->nullable();
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'channel_account_id', 'code', 'language'], 'utility_templates_unique');
            $table->index(['tenant_id', 'channel_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_templates');
    }
};
