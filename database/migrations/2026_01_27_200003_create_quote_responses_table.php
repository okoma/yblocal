<?php

// ============================================
// database/migrations/2026_01_27_200003_create_quote_responses_table.php
// Quote responses submitted by businesses
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_responses', function (Blueprint $table) {
            $table->id();
            
            // Quote request being responded to
            $table->foreignId('quote_request_id')->constrained()->onDelete('cascade');
            
            // Business submitting the quote
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            // Quote details
            $table->decimal('price', 10, 2);
            $table->string('delivery_time'); // e.g., "2-3 weeks", "1 month"
            $table->text('message'); // Short proposal/message
            
            // Status
            $table->enum('status', ['submitted', 'shortlisted', 'accepted', 'rejected'])->default('submitted');
            
            // Optional attachments (JSON array of file paths)
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['quote_request_id', 'status']);
            $table->index(['business_id', 'created_at']);
            $table->unique(['quote_request_id', 'business_id']); // One quote per business per request
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_responses');
    }
};
