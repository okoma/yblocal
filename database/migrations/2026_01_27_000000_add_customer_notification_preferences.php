<?php
// ============================================
// Add Customer-specific Notification Preferences
// For regular customers (not business owners)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            // Customer Email Notifications (when businesses interact with them)
            $table->boolean('notify_review_reply_received')->default(true)
                ->after('notify_campaign_updates')
                ->comment('Email when business replies to customer\'s review');
            
            $table->boolean('notify_inquiry_response_received')->default(true)
                ->after('notify_review_reply_received')
                ->comment('Email when business responds to customer\'s inquiry');
            
            $table->boolean('notify_saved_business_updates')->default(true)
                ->after('notify_inquiry_response_received')
                ->comment('Email about updates from saved businesses');
            
            $table->boolean('notify_promotions_customer')->default(true)
                ->after('notify_saved_business_updates')
                ->comment('Email about special offers from businesses');
            
            $table->boolean('notify_newsletter_customer')->default(true)
                ->after('notify_promotions_customer')
                ->comment('Platform newsletter and updates for customers');
            
            // Customer In-App Notifications
            $table->boolean('notify_review_reply_app')->default(true)
                ->after('notify_newsletter_customer')
                ->comment('In-app notification for review replies');
            
            $table->boolean('notify_inquiry_response_app')->default(true)
                ->after('notify_review_reply_app')
                ->comment('In-app notification for inquiry responses');
            
            $table->boolean('notify_saved_business_updates_app')->default(true)
                ->after('notify_inquiry_response_app')
                ->comment('In-app notification for saved business updates');
            
            $table->boolean('notify_promotions_app')->default(false)
                ->after('notify_saved_business_updates_app')
                ->comment('In-app notification for promotions (off by default)');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'notify_review_reply_received',
                'notify_inquiry_response_received',
                'notify_saved_business_updates',
                'notify_promotions_customer',
                'notify_newsletter_customer',
                'notify_review_reply_app',
                'notify_inquiry_response_app',
                'notify_saved_business_updates_app',
                'notify_promotions_app',
            ]);
        });
    }
};
