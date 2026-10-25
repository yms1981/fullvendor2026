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
        Schema::create('languages', function (Blueprint $table) {
            $table->integer('language_id', true);
            $table->char('name', 49)->nullable();
            $table->char('iso_639_1', 2)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('type', 50)->nullable();
            $table->integer('default')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
