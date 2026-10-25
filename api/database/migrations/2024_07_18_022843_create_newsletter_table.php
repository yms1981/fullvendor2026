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
        Schema::create('newsletter', function (Blueprint $table) {
            $table->bigInteger('newsletterId', true);
            $table->string('email', 500)->nullable();
            $table->timestamp('created')->nullable();
            $table->tinyInteger('status')->nullable()->default(1)->comment('1-Subscribed, 0-Unsubscribed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter');
    }
};
