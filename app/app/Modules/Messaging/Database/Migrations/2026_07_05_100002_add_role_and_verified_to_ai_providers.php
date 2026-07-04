<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->string('role', 20)->default('chat')->after('adapter');
            $table->boolean('vision_verified')->nullable()->after('role');
            $table->timestamp('vision_verified_at')->nullable()->after('vision_verified');
            $table->string('vision_verify_error', 255)->nullable()->after('vision_verified_at');
            $table->boolean('transcription_verified')->nullable()->after('vision_verify_error');
            $table->timestamp('transcription_verified_at')->nullable()->after('transcription_verified');
            $table->string('transcription_verify_error', 255)->nullable()->after('transcription_verified_at');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropColumn(['role','vision_verified','vision_verified_at','vision_verify_error','transcription_verified','transcription_verified_at','transcription_verify_error']);
        });
    }
};
