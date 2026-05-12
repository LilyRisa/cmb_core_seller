<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customers — the tenant's internal customer registry. Matched across orders by
 * normalized phone (`phone_hash = sha256(normalized)`). PII (`phone`, `email`) is
 * encrypted at the application layer. See SPEC 0002 §5.1, docs/03-domain/customers-and-buyer-reputation.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->char('phone_hash', 64);
            $table->text('phone');                              // encrypted cast
            $table->string('name')->nullable();
            $table->text('email')->nullable();                  // encrypted cast
            $table->char('email_hash', 64)->nullable();
            $table->json('addresses_meta')->nullable();         // up to 5 distinct recent addresses
            $table->json('lifetime_stats');
            $table->smallInteger('reputation_score')->default(100);
            $table->string('reputation_label', 16)->default('ok');   // ok|watch|risk|blocked (denormalized)
            $table->json('tags');
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('blocked_at')->nullable();
            $table->foreignId('blocked_by_user_id')->nullable();
            $table->string('block_reason')->nullable();
            $table->text('manual_note')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->foreignId('merged_into_customer_id')->nullable();
            $table->timestamp('pii_anonymized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'phone_hash']);
            $table->index(['tenant_id', 'last_seen_at']);
            $table->index(['tenant_id', 'reputation_label']);
            $table->index(['tenant_id', 'is_blocked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
