<?php

// ============================================
// Create business_managers pivot table
// Links users to businesses with permissions
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_managers', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Manager Details
            $table->string('position')->default('Business Manager');
            
            // Permissions (JSON)
            $table->json('permissions')->nullable();
            // Structure: {
            //   "can_edit_business": true,
            //   "can_manage_products": true,
            //   "can_respond_to_reviews": true,
            //   "can_view_leads": true,
            //   "can_respond_to_leads": true,
            //   "can_view_analytics": true,
            //   "can_access_financials": false,
            //   "can_manage_staff": false
            // }
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false); // Primary manager flag
            
            // Timestamps
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            
            // Indexes
            $table->unique(['business_id', 'user_id']); // One manager per business
            $table->index('business_id');
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_managers');
    }
};
