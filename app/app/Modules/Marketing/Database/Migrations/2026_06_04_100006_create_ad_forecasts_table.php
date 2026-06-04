<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ad_forecasts — cached AI forecast/strategy per ad account (one latest row).
 * Generated on-demand only (cooldown-guarded) to save AI quota.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->json('payload');                 // { forecast:{...}, strategy:[...] }
            $table->string('provider_code')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->unique(['ad_account_id']);       // keep one latest forecast per account
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_forecasts');
    }
};
