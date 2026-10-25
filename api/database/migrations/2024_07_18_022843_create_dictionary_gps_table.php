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
        Schema::create('dictionary_gps', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('eng', 500)->nullable();
            $table->string('esp', 500)->nullable();
            $table->string('arg', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dictionary_gps');
    }
};
