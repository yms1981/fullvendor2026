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
        Schema::create('units', function (Blueprint $table) {
            $table->bigIncrements('unit_id')->primary();
            $table->string('unit', 50);
            $table->string('description', 500);
            $table->integer('company_id');
            $table->integer('status')->nullable();
            $table->date('created')->nullable();
            $table->integer('default')->nullable()->default(0);
            $table->integer('decimales')->nullable();
            $table->string('formato', 50)->nullable();
            $table->bigInteger('id_kor')->nullable();

            //$table->primary(['unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
