<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('topic')->index();
            $table->json('channels')->nullable(); // e.g. {"mail":true,"telegram":false}
            $table->string('frequency')->default('immediate'); // immediate|daily|weekly|digest
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['notifiable_type', 'notifiable_id', 'topic'], 'notification_pref_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_preferences');
    }
};
