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
        Schema::create('guest_business_drafts', function (Blueprint $table) {
            $table->id();
            
            // Guest identification
            $table->string('session_id')->index();
            $table->string('guest_email')->nullable()->index();
            $table->string('guest_phone')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            // Form progress
            $table->json('form_data'); // All wizard form data
            $table->integer('current_step')->default(1); // Which step they're on
            
            // Tracking
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('abandoned_email_sent_at')->nullable();
            $table->integer('reminder_count')->default(0);
            
            // Conversion tracking
            $table->boolean('is_converted')->default(false)->index();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('converted_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for abandoned form queries
            $table->index(['is_converted', 'last_activity_at']);
            $table->index(['is_converted', 'abandoned_email_sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_business_drafts');
    }
};
