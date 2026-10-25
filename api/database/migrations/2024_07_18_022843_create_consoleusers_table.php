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
        Schema::create('consoleusers', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('email', 500)->index('email');
            $table->string('password', 500)->nullable();
            $table->string('rol', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consoleusers');
    }
};
