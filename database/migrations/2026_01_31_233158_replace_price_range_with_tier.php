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
        Schema::table('businesses', function (Blueprint $table) {
            // Drop old price range columns if they exist
            if (Schema::hasColumn('businesses', 'min_price')) {
                $table->dropColumn('min_price');
            }
            if (Schema::hasColumn('businesses', 'max_price')) {
                $table->dropColumn('max_price');
            }
            if (Schema::hasColumn('businesses', 'price_currency')) {
                $table->dropColumn('price_currency');
            }
            
            // Add new price tier column
            $table->string('price_tier')->nullable()->after('premium_until')->comment('Price tier: budget, affordable, premium, luxury');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('price_tier');
            
            // Restore old columns on rollback
            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('max_price', 12, 2)->nullable();
            $table->string('price_currency', 3)->default('NGN');
        });
    }
};
