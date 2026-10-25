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
        Schema::create('keys', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('key', 40);
            $table->integer('level');
            $table->boolean('ignore_limits')->default(false);
            $table->boolean('is_private_key')->default(false);
            $table->text('ip_addresses')->nullable();
            $table->dateTime('date_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keys');
    }
};
