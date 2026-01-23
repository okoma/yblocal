<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $gateways = [
            [
                'name' => 'Paystack',
                'slug' => 'paystack',
                'display_name' => 'Paystack',
                'description' => 'Pay with Paystack - Cards, Bank Transfer, USSD, and more',
                'is_active' => true,
                'is_enabled' => false,
                'sort_order' => 1,
                'instructions' => null,
                'supported_currencies' => json_encode(['NGN']),
                'supported_payment_methods' => json_encode(['card', 'bank_transfer', 'ussd']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Flutterwave',
                'slug' => 'flutterwave',
                'display_name' => 'Flutterwave',
                'description' => 'Pay with Flutterwave - Multiple payment options',
                'is_active' => true,
                'is_enabled' => false,
                'sort_order' => 2,
                'instructions' => null,
                'supported_currencies' => json_encode(['NGN', 'USD', 'GBP', 'EUR']),
                'supported_payment_methods' => json_encode(['card', 'bank_transfer', 'mobile_money']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank Transfer',
                'slug' => 'bank_transfer',
                'display_name' => 'Bank Transfer',
                'description' => 'Make a direct bank transfer to our account',
                'is_active' => true,
                'is_enabled' => false,
                'sort_order' => 3,
                'instructions' => 'Please make a transfer to the account details provided. Your subscription will be activated once payment is confirmed.',
                'supported_currencies' => json_encode(['NGN']),
                'supported_payment_methods' => json_encode(['bank_transfer']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Wallet Payment',
                'slug' => 'wallet',
                'display_name' => 'Wallet Balance',
                'description' => 'Pay using your wallet balance',
                'is_active' => true,
                'is_enabled' => true,
                'sort_order' => 4,
                'instructions' => 'Payment will be deducted from your wallet balance. Ensure you have sufficient funds.',
                'supported_currencies' => json_encode(['NGN']),
                'supported_payment_methods' => json_encode(['wallet']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        DB::table('payment_gateways')->insert($gateways);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payment_gateways')
            ->whereIn('slug', ['paystack', 'flutterwave', 'bank_transfer', 'wallet'])
            ->delete();
    }
};
