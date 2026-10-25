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
        Schema::create('dictionary', function (Blueprint $table) {
            $table->integer('id_di', true);
            $table->string('mdl', 45)->nullable();
            $table->string('entry', 35)->nullable();
            $table->string('spa', 245)->nullable();
            $table->string('eng', 245)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dictionary');
    }
};
