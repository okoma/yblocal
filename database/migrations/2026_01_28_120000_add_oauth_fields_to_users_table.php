<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('oauth_provider')->nullable()->after('role');
            $table->string('oauth_provider_id')->nullable()->after('oauth_provider');

            $table->index(['oauth_provider', 'oauth_provider_id'], 'users_oauth_provider_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_oauth_provider_lookup');
            $table->dropColumn(['oauth_provider', 'oauth_provider_id']);
        });
    }
};

