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
        Schema::table('user_preferences', function (Blueprint $table) {
            // Business: Claim notifications (email)
            $table->boolean('notify_claim_submitted')->default(true)
                ->after('notify_campaign_updates')
                ->comment('Email when business claim is submitted');
            
            $table->boolean('notify_claim_approved')->default(true)
                ->after('notify_claim_submitted')
                ->comment('Email when business claim is approved');
            
            $table->boolean('notify_claim_rejected')->default(true)
                ->after('notify_claim_approved')
                ->comment('Email when business claim is rejected');
            
            // Business: Quote request notifications (email)
            $table->boolean('notify_new_quote_requests')->default(true)
                ->after('notify_claim_rejected')
                ->comment('Email when new quote requests match business');
            
            // Business: Business reported notifications (email)
            $table->boolean('notify_business_reported')->default(true)
                ->after('notify_new_quote_requests')
                ->comment('Email when business is reported');
            
            // Business: Claim notifications (telegram)
            $table->boolean('notify_claim_submitted_telegram')->default(false)
                ->after('notify_campaign_updates_telegram')
                ->comment('Telegram when business claim is submitted');
            
            $table->boolean('notify_claim_approved_telegram')->default(false)
                ->after('notify_claim_submitted_telegram')
                ->comment('Telegram when business claim is approved');
            
            $table->boolean('notify_claim_rejected_telegram')->default(false)
                ->after('notify_claim_approved_telegram')
                ->comment('Telegram when business claim is rejected');
            
            // Business: Quote request notifications (telegram)
            $table->boolean('notify_new_quote_requests_telegram')->default(false)
                ->after('notify_claim_rejected_telegram')
                ->comment('Telegram when new quote requests match business');
            
            // Business: Business reported notifications (telegram)
            $table->boolean('notify_business_reported_telegram')->default(false)
                ->after('notify_new_quote_requests_telegram')
                ->comment('Telegram when business is reported');
            
            // Customer: Quote response notifications (email)
            $table->boolean('notify_quote_responses')->default(true)
                ->after('notify_promotions_app')
                ->comment('Email when businesses submit quotes for customer requests');
            
            // Customer: Quote update notifications (email)
            $table->boolean('notify_quote_updates')->default(true)
                ->after('notify_quote_responses')
                ->comment('Email when quotes are shortlisted/accepted/rejected');
            
            // Customer: Quote response notifications (in-app)
            $table->boolean('notify_quote_responses_app')->default(true)
                ->after('notify_quote_updates')
                ->comment('In-app notification when businesses submit quotes');
            
            // Customer: Quote update notifications (in-app)
            $table->boolean('notify_quote_updates_app')->default(true)
                ->after('notify_quote_responses_app')
                ->comment('In-app notification when quotes are shortlisted/accepted/rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                // Business email
                'notify_claim_submitted',
                'notify_claim_approved',
                'notify_claim_rejected',
                'notify_new_quote_requests',
                'notify_business_reported',
                // Business telegram
                'notify_claim_submitted_telegram',
                'notify_claim_approved_telegram',
                'notify_claim_rejected_telegram',
                'notify_new_quote_requests_telegram',
                'notify_business_reported_telegram',
                // Customer
                'notify_quote_responses',
                'notify_quote_updates',
                'notify_quote_responses_app',
                'notify_quote_updates_app',
            ]);
        });
    }
};
