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
            $table->decimal('min_price', 12, 2)->nullable()->after('premium_until')->comment('Minimum price for services/products');
            $table->decimal('max_price', 12, 2)->nullable()->after('min_price')->comment('Maximum price for services/products');
            $table->string('price_currency', 3)->default('NGN')->after('max_price')->comment('Currency code (NGN, USD, etc)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'max_price', 'price_currency']);
        });
    }
};
