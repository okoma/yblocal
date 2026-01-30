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
        // Add fraud detection fields to business_referrals
        Schema::table('business_referrals', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('referral_code');
            $table->string('device_fingerprint')->nullable()->after('ip_address');
            $table->string('user_agent')->nullable()->after('device_fingerprint');
            $table->boolean('is_suspicious')->default(false)->after('status');
            $table->text('fraud_notes')->nullable()->after('is_suspicious');
            $table->timestamp('verified_at')->nullable()->after('fraud_notes');
            
            $table->index('ip_address');
            $table->index('device_fingerprint');
            $table->index('is_suspicious');
        });

        // Add fraud detection fields to customer_referrals
        Schema::table('customer_referrals', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('referral_code');
            $table->string('device_fingerprint')->nullable()->after('ip_address');
            $table->string('user_agent')->nullable()->after('device_fingerprint');
            $table->boolean('is_suspicious')->default(false)->after('status');
            $table->text('fraud_notes')->nullable()->after('is_suspicious');
            $table->timestamp('verified_at')->nullable()->after('fraud_notes');
            
            $table->index('ip_address');
            $table->index('device_fingerprint');
            $table->index('is_suspicious');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_referrals', function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropIndex(['device_fingerprint']);
            $table->dropIndex(['is_suspicious']);
            
            $table->dropColumn([
                'ip_address',
                'device_fingerprint',
                'user_agent',
                'is_suspicious',
                'fraud_notes',
                'verified_at',
            ]);
        });

        Schema::table('customer_referrals', function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropIndex(['device_fingerprint']);
            $table->dropIndex(['is_suspicious']);
            
            $table->dropColumn([
                'ip_address',
                'device_fingerprint',
                'user_agent',
                'is_suspicious',
                'fraud_notes',
                'verified_at',
            ]);
        });
    }
};
