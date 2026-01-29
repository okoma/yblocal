<?php

// ============================================
// Customer referral commission withdrawal requests
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_referral_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_referral_wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 14, 2);
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number', 20);
            $table->string('sort_code', 20)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_referral_withdrawals');
    }
};
