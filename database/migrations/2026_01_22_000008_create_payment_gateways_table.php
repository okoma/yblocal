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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Paystack, Flutterwave, Bank Transfer, Wallet Payment
            $table->string('slug')->unique(); // paystack, flutterwave, bank_transfer, wallet
            $table->string('display_name'); // Display name for frontend
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_enabled')->default(false); // Whether it's configured and ready
            $table->integer('sort_order')->default(0);
            
            // Gateway-specific settings (stored as JSON)
            $table->json('settings')->nullable(); // API keys, webhook URLs, etc.
            
            // Configuration fields (common ones)
            $table->string('public_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('callback_url')->nullable();
            
            // Bank Transfer specific
            $table->text('bank_account_details')->nullable(); // JSON: account name, number, bank name
            
            // Status and metadata
            $table->json('supported_currencies')->nullable(); // ['NGN', 'USD', etc.]
            $table->json('supported_payment_methods')->nullable(); // ['card', 'bank_transfer', etc.]
            $table->text('instructions')->nullable(); // Instructions for users (e.g., bank transfer details)
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('slug');
            $table->index('is_active');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
