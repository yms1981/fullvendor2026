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
        Schema::create('settings', function (Blueprint $table) {
            $table->bigInteger('settingId', true);
            $table->string('logo', 500)->nullable();
            $table->string('favicon', 500)->nullable();
            $table->string('title', 500)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('email', 500)->nullable();
            $table->string('phone', 500)->nullable();
            $table->string('website')->nullable();
            $table->tinyText('facebook')->nullable();
            $table->tinyText('twitter')->nullable();
            $table->tinyText('linkedin')->nullable();
            $table->tinyText('instagram')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
