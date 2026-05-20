<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `message_templates` — mẫu tin trả lời tay nhanh. Body hỗ trợ vars
 * `{{customer.name}}`, `{{order.code}}` etc. Phase S3 sẽ làm Template CRUD UI
 * + variable resolver — S1 chỉ tạo schema để các phần sau wire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 64);
            $table->string('name');
            $table->text('body');
            $table->json('vars')->nullable();                          // declared variables list
            $table->json('attachments')->nullable();                   // [{storage_path, kind, mime}]
            $table->json('scope')->nullable();                         // {providers: ['facebook_page']}
            $table->string('shortcut_key', 32)->nullable();
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
