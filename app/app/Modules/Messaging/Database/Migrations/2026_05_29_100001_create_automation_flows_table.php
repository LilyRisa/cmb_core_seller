<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `automation_flows` — kịch bản tự động dạng đồ thị node (Flow Builder S1).
 * `graph` jsonb do canvas (S3) sinh; người dùng không sửa JSON tay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('name');
            $table->string('provider', 32)->default('facebook_page');
            $table->string('status', 16)->default('draft');        // draft|active|paused|archived
            $table->string('trigger_type', 32);                    // comment_on_post|comment_any|inbox_first_message|inbox_keyword|inbox_any
            $table->json('trigger_config')->nullable();
            $table->json('graph')->nullable();                     // { nodes:[], edges:[] }
            $table->unsignedInteger('version')->default(1);
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'provider', 'status', 'enabled']);
            $table->index(['tenant_id', 'trigger_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_flows');
    }
};
