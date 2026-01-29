<?php

// ============================================
// Customer → Business referral (customer refers a business; 10% commission on payments)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('referral_code')->index();
            $table->enum('status', ['pending', 'qualified'])->default('pending');
            $table->timestamps();

            $table->unique('referred_business_id');
            $table->index(['referrer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_referrals');
    }
};
