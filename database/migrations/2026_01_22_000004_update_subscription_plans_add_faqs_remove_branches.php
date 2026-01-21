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
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Add max_faqs
            $table->integer('max_faqs')->nullable()->after('max_photos');
            
            // Remove max_branches
            $table->dropColumn('max_branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Restore max_branches
            $table->integer('max_branches')->nullable()->after('max_photos');
            
            // Remove max_faqs
            $table->dropColumn('max_faqs');
        });
    }
};
