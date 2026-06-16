<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visual_training_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('attributes')->nullable();
            $table->string('ref_code', 64)->nullable();
            $table->string('status', 16)->default('active');   // active | indexing | failed
            $table->boolean('applies_all_pages')->default(true);
            // Ảnh đại diện (cover) — không gắn FK cứng (tránh vòng phụ thuộc với bảng images
            // tạo sau + SQLite). Service tự đảm bảo trỏ về 1 visual_training_images hợp lệ.
            $table->unsignedBigInteger('primary_image_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visual_training_items');
    }
};
