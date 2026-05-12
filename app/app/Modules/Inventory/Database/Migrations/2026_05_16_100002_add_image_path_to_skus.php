<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `skus.image_path` — the object key on the media disk (Cloudflare R2) for the
 * uploaded SKU image. `image_url` (added in the previous migration) stays the
 * public URL the frontend renders; `image_path` is what we need to delete/replace
 * the object. See SPEC 0005 §7 and docs/07-ops/cloudflare-r2-uploads.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
