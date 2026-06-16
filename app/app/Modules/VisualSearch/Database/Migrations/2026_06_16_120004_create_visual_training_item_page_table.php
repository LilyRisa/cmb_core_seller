<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot scoping per-page (SPEC 0035) — item áp dụng cho page (channel_account) nào.
        Schema::create('visual_training_item_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('item_id');
            $table->foreignId('channel_account_id');
            $table->timestamps();
            $table->unique(['item_id', 'channel_account_id'], 'vti_page_unique');
            $table->index('channel_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visual_training_item_page');
    }
};
