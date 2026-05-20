<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `message_attachments` — 1-n với messages. File thực ở MinIO theo prefix
 * `tenants/{id}/messaging/{yyyy/mm}/{conversation_id}/{uuid}.{ext}`. SPEC-0024 §5.4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('message_id');
            $table->string('kind', 16);                                // image|video|file|audio
            $table->string('mime', 128);
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('storage_path', 512)->nullable();           // MinIO key
            $table->string('external_url', 1024)->nullable();          // URL gốc từ sàn (cache để re-fetch)
            $table->string('checksum', 64)->nullable();                // sha256 hex
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->string('filename', 255)->nullable();
            $table->string('status', 16)->default('pending');          // pending|downloaded|failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'message_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
