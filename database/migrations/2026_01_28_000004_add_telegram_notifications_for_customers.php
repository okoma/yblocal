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
            // Customer Telegram notification preferences
            $table->boolean('notify_review_reply_received_telegram')->default(false)
                ->after('notify_quote_updates_app')
                ->comment('Telegram when business replies to customer review');
            
            $table->boolean('notify_inquiry_response_received_telegram')->default(false)
                ->after('notify_review_reply_received_telegram')
                ->comment('Telegram when business responds to customer inquiry');
            
            $table->boolean('notify_saved_business_updates_telegram')->default(false)
                ->after('notify_inquiry_response_received_telegram')
                ->comment('Telegram about updates from saved businesses');
            
            $table->boolean('notify_promotions_customer_telegram')->default(false)
                ->after('notify_saved_business_updates_telegram')
                ->comment('Telegram about special offers from businesses');
            
            $table->boolean('notify_newsletter_customer_telegram')->default(false)
                ->after('notify_promotions_customer_telegram')
                ->comment('Telegram platform newsletter and updates');
            
            $table->boolean('notify_quote_responses_telegram')->default(false)
                ->after('notify_newsletter_customer_telegram')
                ->comment('Telegram when businesses submit quotes for customer requests');
            
            $table->boolean('notify_quote_updates_telegram')->default(false)
                ->after('notify_quote_responses_telegram')
                ->comment('Telegram when quotes are shortlisted/accepted/rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'notify_review_reply_received_telegram',
                'notify_inquiry_response_received_telegram',
                'notify_saved_business_updates_telegram',
                'notify_promotions_customer_telegram',
                'notify_newsletter_customer_telegram',
                'notify_quote_responses_telegram',
                'notify_quote_updates_telegram',
            ]);
        });
    }
};
