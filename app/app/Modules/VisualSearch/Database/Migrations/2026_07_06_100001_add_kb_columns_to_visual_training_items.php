<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visual_training_items', function (Blueprint $table) {
            $table->text('content_text')->nullable()->after('description');
            $table->string('source', 16)->default('inline')->after('content_text'); // inline|url|upload
            $table->string('url')->nullable()->after('source');
            $table->string('storage_path')->nullable()->after('url');
            $table->string('provider', 32)->default('facebook_page')->after('storage_path');
            $table->string('kb_status', 16)->default('pending')->after('provider'); // pending|ready|failed
            $table->unsignedInteger('chunk_count')->default(0)->after('kb_status');
            $table->string('embedding_provider_code', 64)->nullable()->after('chunk_count');
            $table->string('embedding_model', 128)->nullable()->after('embedding_provider_code');
            $table->unsignedSmallInteger('embedding_version')->default(0)->after('embedding_model');
            $table->timestamp('kb_indexed_at')->nullable()->after('embedding_version');
            $table->index(['tenant_id', 'kb_status', 'provider'], 'vti_kb_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('visual_training_items', function (Blueprint $table) {
            $table->dropIndex('vti_kb_scope_idx');
            $table->dropColumn(['content_text', 'source', 'url', 'storage_path', 'provider',
                'kb_status', 'chunk_count', 'embedding_provider_code', 'embedding_model',
                'embedding_version', 'kb_indexed_at']);
        });
    }
};
