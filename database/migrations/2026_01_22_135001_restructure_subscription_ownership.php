<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Restructure subscriptions to be business-centric instead of user-centric.
     * Businesses own subscriptions, not users.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // First, make sure all existing subscriptions have a business_id
            // (In production, you'd need to run a data migration first)
            
            // Drop the old foreign key
            $table->dropForeign(['business_id']);
            
            // Make business_id NOT NULL (subscriptions belong to businesses)
            $table->foreignId('business_id')->nullable(false)->change();
            
            // Add the foreign key back
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            
            // Make user_id nullable (keep for reference - who initiated the subscription)
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Revert back to user-centric
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->dropForeign(['business_id']);
            $table->foreignId('business_id')->nullable()->change();
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }
};
