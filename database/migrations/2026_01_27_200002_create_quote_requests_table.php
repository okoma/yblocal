<?php

// ============================================
// database/migrations/2026_01_27_200002_create_quote_requests_table.php
// Quote requests created by customers
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            
            // Customer who created the request
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Optional pre-assigned business
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('set null');
            
            // Category and location
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('state_location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('city_location_id')->nullable()->constrained('locations')->onDelete('cascade');
            
            // Request details
            $table->string('title');
            $table->text('description');
            
            // Optional budget range
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            
            // Status and expiration
            $table->enum('status', ['open', 'closed', 'expired', 'accepted'])->default('open');
            $table->timestamp('expires_at')->nullable();
            
            // Attachments (JSON array of file paths)
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'expires_at']);
            $table->index(['category_id', 'state_location_id', 'city_location_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
