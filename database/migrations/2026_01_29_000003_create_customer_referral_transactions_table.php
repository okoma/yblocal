<?php

// ============================================
// Audit trail for customer referral wallet (commission, withdrawal, adjustment)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_referral_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_referral_wallet_id')
                ->constrained()
                ->onDelete('cascade')
                ->name('crt_wallet_fk'); // Custom short name
            $table->foreignId('customer_referral_id')
                ->nullable()
                ->constrained('customer_referrals')
                ->onDelete('set null')
                ->name('crt_referral_fk'); // Custom short name
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->onDelete('set null')
                ->name('crt_transaction_fk'); // Custom short name
            $table->decimal('amount', 14, 2);
            $table->enum('type', ['commission', 'withdrawal', 'adjustment'])->index();
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('description')->nullable();
            $table->nullableMorphs('reference');
            $table->timestamps();

            $table->index(['customer_referral_wallet_id', 'created_at']);
            $table->index(['customer_referral_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_referral_transactions');
    }
};