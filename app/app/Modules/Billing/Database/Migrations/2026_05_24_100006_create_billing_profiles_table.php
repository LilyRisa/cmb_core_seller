<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.4 — `billing_profiles` — thông tin xuất hoá đơn của tenant (MST, địa chỉ, email kế toán).
 *
 * 1-1 với tenant; snapshot vào `invoices.customer_snapshot` lúc tạo invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique();
            $table->string('company_name', 255)->nullable();
            $table->string('tax_code', 32)->nullable();
            $table->string('billing_address', 500)->nullable();
            $table->string('contact_email', 191)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_profiles');
    }
};
