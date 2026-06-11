<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_push_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('product_push_batch_id')->index();
            $table->foreignId('listing_draft_id')->index();
            $table->string('status', 16)->default('queued');
            $table->string('step_label')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->json('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_push_jobs');
    }
};
