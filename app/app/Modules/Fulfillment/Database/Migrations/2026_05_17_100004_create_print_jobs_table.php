<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * print_jobs — a generated PDF (bulk shipping label / picking list / packing list).
 * Minimal v1 (SPEC 0006 §5); the 90-day retention columns (`expires_at`, `purged_at`)
 * and the `order_print_documents` lookup table come with the retention spec — see
 * docs/03-domain/fulfillment-and-printing.md §8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('type');                    // label | picking | packing
            $table->json('scope');                     // { order_ids?: [], shipment_ids?: [] }
            $table->string('file_url')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('status')->default('pending'); // pending|processing|done|error
            $table->string('error')->nullable();
            $table->json('meta')->nullable();          // pages, skipped[], ...
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
