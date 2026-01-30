<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 50)->index(); // paystack, flutterwave, etc.
            $table->string('event_id')->unique(); // Unique event ID from gateway
            $table->string('event_type', 100)->index(); // charge.success, payment.failed, etc.
            $table->string('reference')->nullable()->index(); // Transaction reference
            $table->foreignId('transaction_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('payload'); // Full webhook payload
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Prevent replay attacks - unique event per gateway
            $table->unique(['gateway', 'event_id']);
            
            // Performance indexes
            $table->index(['gateway', 'event_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
