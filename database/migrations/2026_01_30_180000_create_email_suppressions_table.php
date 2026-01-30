<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('reason')->nullable();
            $table->string('source')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['email']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_suppressions');
    }
};
