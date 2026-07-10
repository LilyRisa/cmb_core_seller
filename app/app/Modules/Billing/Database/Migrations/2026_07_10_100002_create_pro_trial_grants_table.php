<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pro_trial_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique(); // 1 lần/tenant vĩnh viễn
            $table->timestamp('granted_at');
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('previous_plan_id')->nullable();
            $table->string('previous_cycle', 16)->nullable();
            $table->timestamp('previous_period_end')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->string('terms_version', 32);
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_trial_grants');
    }
};
