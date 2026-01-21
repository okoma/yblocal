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
            // Telegram notification preferences
            $table->boolean('telegram_notifications')->default(false)->after('email_notifications');
            $table->string('telegram_username')->nullable()->after('telegram_notifications');
            $table->string('telegram_chat_id')->nullable()->after('telegram_username');
            
            // WhatsApp notification preferences (leads only)
            $table->boolean('whatsapp_notifications')->default(false)->after('telegram_chat_id');
            $table->string('whatsapp_number')->nullable()->after('whatsapp_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_notifications',
                'telegram_username',
                'telegram_chat_id',
                'whatsapp_notifications',
                'whatsapp_number',
            ]);
        });
    }
};
