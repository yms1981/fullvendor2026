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
        Schema::create('format_date', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('format_php', 50)->nullable();
            $table->string('format_js', 50)->nullable();
            $table->integer('language_id')->nullable();
            $table->string('date_firstmonth', 50)->nullable();
            $table->string('default', 50)->nullable();
            $table->string('month_year', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('format_date');
    }
};
