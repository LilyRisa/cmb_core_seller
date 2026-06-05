<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * audience_templates — tenant-scoped saved detailed-targeting sets
 * (include / narrow / exclude), reusable across ad drafts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('created_by')->nullable();
            $table->string('name');
            $table->json('payload');                 // { include:[], narrow:[], exclude:[] } of {id,name,type}
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_templates');
    }
};
