<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('monthly_leads_viewed')->default(0)->after('leads_viewed_used');
            $table->timestamp('last_leads_reset_at')->nullable()->after('monthly_leads_viewed');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['monthly_leads_viewed', 'last_leads_reset_at']);
        });
    }
};
