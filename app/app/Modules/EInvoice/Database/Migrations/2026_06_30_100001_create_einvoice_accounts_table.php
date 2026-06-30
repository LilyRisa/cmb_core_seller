<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** einvoice_accounts — credentials per-tenant cho nhà cung cấp HĐĐT (MISA...). SPEC 0041. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('einvoice_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('provider');                       // 'misa'
            $table->string('name');                           // alias gợi nhớ
            $table->text('credentials')->nullable();          // encrypted:array (appid/taxcode/username/password)
            $table->boolean('is_invoice_with_code')->nullable(); // cache từ company info
            $table->string('default_mode')->default('hsm');   // 'hsm' | 'mtt' — mặc định đơn manual
            $table->json('templates')->nullable();            // {hsm:{template_id,inv_series}, mtt:{...}}
            $table->json('seller_info')->nullable();          // thông tin người bán mặc định
            $table->json('auto_issue')->nullable();           // cấu hình tự động (Phần B/P2)
            $table->json('meta')->nullable();                 // last_verified_at...
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'provider', 'name']);
            $table->index(['tenant_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('einvoice_accounts');
    }
};
