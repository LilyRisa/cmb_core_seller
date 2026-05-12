<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** carrier_accounts — a tenant's credentials for a shipping carrier (GHN, …). SPEC 0006 §5. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('carrier');                 // 'manual' | 'ghn' | 'ghtk' | 'jt' | ...
            $table->string('name');                    // human label, e.g. "GHN - kho HN"
            $table->text('credentials')->nullable();   // encrypted:array cast
            $table->string('default_service')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();          // e.g. from_address
            $table->timestamps();

            $table->unique(['tenant_id', 'carrier', 'name']);
            $table->index(['tenant_id', 'carrier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_accounts');
    }
};
