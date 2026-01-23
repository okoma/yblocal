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
            // Telegram individual notification toggles
            $table->boolean('notify_new_leads_telegram')->default(false)->after('telegram_chat_id');
            $table->boolean('notify_new_reviews_telegram')->default(false)->after('notify_new_leads_telegram');
            $table->boolean('notify_review_replies_telegram')->default(false)->after('notify_new_reviews_telegram');
            $table->boolean('notify_verifications_telegram')->default(false)->after('notify_review_replies_telegram');
            $table->boolean('notify_premium_expiring_telegram')->default(false)->after('notify_verifications_telegram');
            $table->boolean('notify_campaign_updates_telegram')->default(false)->after('notify_premium_expiring_telegram');
            
            // WhatsApp individual notification toggles (only leads and reviews)
            $table->boolean('notify_new_leads_whatsapp')->default(false)->after('whatsapp_number');
            $table->boolean('notify_new_reviews_whatsapp')->default(false)->after('notify_new_leads_whatsapp');
            
            // WhatsApp verification
            $table->boolean('whatsapp_verified')->default(false)->after('notify_new_reviews_whatsapp');
            $table->string('whatsapp_verification_code')->nullable()->after('whatsapp_verified');
            $table->timestamp('whatsapp_verified_at')->nullable()->after('whatsapp_verification_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'notify_new_leads_telegram',
                'notify_new_reviews_telegram',
                'notify_review_replies_telegram',
                'notify_verifications_telegram',
                'notify_premium_expiring_telegram',
                'notify_campaign_updates_telegram',
                'notify_new_leads_whatsapp',
                'notify_new_reviews_whatsapp',
                'whatsapp_verified',
                'whatsapp_verification_code',
                'whatsapp_verified_at',
            ]);
        });
    }
};
