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
        Schema::create('currency_formats', function (Blueprint $table) {
            $table->string('CurrencyName', 100);
            $table->string('CurrencyCode', 100);
            $table->string('CurrencyFormat', 100);
            $table->string('currOutput', 100)->nullable();
            $table->string('CodeRegional', 100)->nullable();
            $table->integer('format_id', true);
            $table->integer('order')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_formats');
    }
};
