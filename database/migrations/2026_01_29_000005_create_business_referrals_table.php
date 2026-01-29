<?php

// ============================================
// Business → Business referral (business refers another business; credits on sign-up)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('referred_business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('referral_code')->index();
            $table->unsignedInteger('referral_credits_awarded')->default(0);
            $table->enum('status', ['pending', 'credited'])->default('pending');
            $table->timestamps();

            $table->unique('referred_business_id');
            $table->index(['referrer_business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_referrals');
    }
};
