<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visual_training_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('image_id');          // visual_training_images.id
            $table->string('model', 64);            // = ImageEmbedder::modelKey()
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('collection', 128);      // collection Qdrant thực tế
            $table->string('vector_id', 64);        // point id trong Qdrant (uuid)
            $table->unsignedInteger('dim')->default(0);
            $table->string('status', 16)->default('pending');  // pending | indexed | failed
            $table->text('error')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();
            // 1 ảnh có thể có nhiều embedding song song (model/version khác) — migrate model không phá schema.
            $table->unique(['image_id', 'model', 'version'], 'vte_unique');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visual_training_embeddings');
    }
};
