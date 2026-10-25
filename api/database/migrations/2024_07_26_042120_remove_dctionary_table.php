<?php

use Illuminate\Database\Migrations\Migration;



return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('dictionary');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('dictionary', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->integer('id_di', true);
            $table->string('mdl', 45)->nullable();
            $table->string('entry', 35)->nullable();
            $table->string('spa', 245)->nullable();
            $table->string('eng', 245)->nullable();
        });
    }
};
