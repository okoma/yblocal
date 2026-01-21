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
        Schema::table('subscriptions', function (Blueprint $table) {
            // Add faqs_used
            $table->integer('faqs_used')->default(0)->after('photos_used');
            
            // Remove branches_used
            $table->dropColumn('branches_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Restore branches_used
            $table->integer('branches_used')->default(0)->after('photos_used');
            
            // Remove faqs_used
            $table->dropColumn('faqs_used');
        });
    }
};
