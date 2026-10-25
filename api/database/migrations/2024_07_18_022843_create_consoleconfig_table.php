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
        Schema::create('consoleconfig', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('token', 500)->nullable()->unique('token');
            $table->bigInteger('company_id')->nullable();
            $table->string('username', 200)->nullable();
            $table->string('password', 200)->nullable();
            $table->string('email', 200)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('telephone', 500)->nullable();
            $table->string('app', 500)->nullable();
            $table->string('rol', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consoleconfig');
    }
};
