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
        Schema::create('currency', function (Blueprint $table) {
            $table->integer('currency_id', true);
            $table->string('country', 100)->nullable();
            $table->string('currency', 100)->nullable();
            $table->string('code', 100)->nullable();
            $table->string('symbol', 100)->nullable();
            $table->integer('company_id');
            $table->integer('status');
            $table->dateTime('created')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency');
    }
};
