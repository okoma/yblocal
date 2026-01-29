<?php

// ============================================
// Audit trail for business referral credits (earned, converted to ads/quote/sub)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_referral_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_referral_id')->nullable()->constrained('business_referrals')->onDelete('set null');
            $table->integer('amount');
            $table->string('type', 40)->index();
            $table->unsignedInteger('balance_after');
            $table->string('description')->nullable();
            $table->nullableMorphs('reference');
            $table->timestamps();

            $table->index(['business_id', 'created_at']);
            $table->index(['business_referral_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_referral_credit_transactions');
    }
};
